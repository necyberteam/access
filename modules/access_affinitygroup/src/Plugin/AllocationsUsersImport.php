<?php

namespace Drupal\access_affinitygroup\Plugin;

use GuzzleHttp\Client;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\access_misc\Plugin\Util\NotifyRoles;

/**
 * @file
 * Import users using allocations api.
 * The purpose is that each user working with an allocation:
 *  1) should have a user account here
 *  2) their email on constant contact
 *  3) get added to affinity groups: access-support and the one that is associated
 *    with the allocation.
 * This is done during a daily cron job.  We get all the active users from the
 * allocations api. Once we get all the usernames into arrays, we call the api for each
 * one to get the user's current list of resources (aka allocations aka ciders).
 */
/**
 *
 */
class AllocationsUsersImport {
  // These are defaults if not set on form.
  // Default if not set: how many users to process in each batch.
  const DEFAULT_SIZE = 5;
  // Stop processing after this amount. total is > 100k, will be 12k when api updated.
  const DEFAULT_IMPORTLIMIT = 100;
  // If true, don't create new constant contact user or attempt to add to cc list. For dev.
  const DEFAULT_NOCC = TRUE;
  // If true, don't save user detail changes. Needed for dev testing because user details
  // such as name and email are encoded in test db.
  const DEFAULT_NOUSERDETSAVE = TRUE;
  const DEFAULT_STARTAT = 0;
  /**
   * Where to start processing in case we must restart a large operation.
   * These are set in the constant contact form.
   */
  private $batchSize;
  private $batchImportLimit;
  private $batchNoCC;
  private $batchNoUserDetSav;
  private $batchStartAt;
  /**
   * For devtest, put in front of emails and uname for dev; set to '' to use real names.
   */
  private $cDevUname = '';
  /**
   * For $this->collectCronLog for dev status email.
   */
  private $logCronErrors = [];
  private $accessSupportNodeId = 0;

  /**
   * Can start from drush or from form, todo: cron.
   */
  public function startBatch() {
    $this->batchSize = \Drupal::state()->get('access_affinitygroup.allocBatchBatchSize');
    if (empty($this->batchSize)) {
      $this->batchSize = self::DEFAULT_SIZE;
    }
    $this->batchImportLimit = \Drupal::state()->get('access_affinitygroup.allocBatchImportLimit');
    if (empty($this->batchImportLimit)) {
      $this->batchImportLimit = self::DEFAULT_IMPORTLIMIT;
    }
    $this->batchStartAt = \Drupal::state()->get('access_affinitygroup.allocBatchStartAt');
    if (empty($this->batchStartAt)) {
      $this->batchStartAt = self::DEFAULT_STARTAT;
    }
    $this->batchNoCC = \Drupal::state()->get('access_affinitygroup.allocBatchNoCC');
    if (!isset($this->batchNoCC)) {
      $this->batchNoCC = self::DEFAULT_NOCC;
    }
    $this->batchNoUserDetSave = \Drupal::state()->get('access_affinitygroup.allocBatchNoUserDetSave');
    if (!isset($this->batchNoUserDetSave)) {
      $this->batchNoUserDetSave = self::DEFAULT_NOUSERDETSAVE;
    }
    $msg1 = "Batch params size: $this->batchSize processing limit: $this->batchImportLimit noCC: $this->batchNoCC";
    \Drupal::messenger()->addMessage($msg1);
    $this->collectCronLog($msg1, 'i');

    // don't start imports if something is wrong with constant contact connection.
    $cca = new ConstantContactApi();
    if (!$cca->getConnectionStatus()) {
      \Drupal::logger('cron_affinitygroup')->error('Contant Contact not connected.');
      return;
    }

    $portalUserNames = $this->getApiPortalUserNames($this->batchStartAt, $this->batchImportLimit);
    if (empty($portalUserNames)) {
      return;
    }
    $msg1 = "Initial import done: number to process: " . count($portalUserNames) . "; start processing at: $this->batchStartAt";
    \Drupal::messenger()->addMessage($msg1);
    $this->collectCronLog($msg1, 'i');

    // Get access support affinity group node for later.
    $nArray = \Drupal::entityQuery('node')
      ->condition('type', 'affinity_group')
      ->condition('title', 'ACCESS Support')
      ->execute();
    $this->accessSupportNodeId = empty($nArray) ? 0 : array_values($nArray)[0];

    \Drupal::state()->set('access_affinitygroup.allocationsRun', TRUE);

    try {
      $nameCount = count($portalUserNames);
      $batchBuilder = (new Batchbuilder())
        ->setTitle('Importing Allocations')
        ->setInitMessage('Batch is starting for ' . $nameCount)
        ->setProgressMessage('Estimated remaining time: @estimate. Elapsed time : @elapsed.')
        ->setErrorMessage('Batch error.')
        ->setFinishCallback([$this, 'importFinished']);
      // @todo might need a route here
      $batchBuilder->addOperation([$this, 'processChunk'], [$portalUserNames]);

      batch_set($batchBuilder->toArray());
      if (PHP_SAPI == 'cli') {
        drush_backend_batch_process();
      }
      else {

        // don't need this for form; still need to test what is needed when running from cron
        // batch_process();  // some docs say need a return route arg here, some don't.
      }
    }
    catch (\Exception $e) {
      $this->collectCronLog("Exception in allocations batch setup: " . $e->getMessage(), 'err');
      \Drupal::state()->set('access_affinitygroup.allocationsRun', FALSE);
    }
  }

  /**
   *
   */
  public function importFinished($success, $results, $operations) {

    \Drupal::state()->set('access_affinitygroup.allocationsRun', FALSE);

    if (empty($results)) {
      $msg1 = "Import finished irregularly; results empty.";
      \Drupal::messenger()->addMessage($msg1);
      $this->collectCronLog("Batch: " . $msg1, 'err');
    }
    else {
      $msg1 = 'Processed ' . $results['totalProcessed'] . ' out of ' . $results['totalExamined'] . ' examined.';
      $msg2 = "New users: " . $results['newUsers'] . " New Constant Contact: " . $results['newCCIds'];

      \Drupal::messenger()->addMessage($msg1);
      $this->collectCronLog("Batch: " . $msg1, 'i');

      \Drupal::messenger()->addMessage($msg2);
      $this->collectCronLog("Batch: " . $msg2, 'i');
    }
    if (!$success) {
      $this->collectCronLog('Batch: Import Allocations problem', 'err');
      \Drupal::messenger()->addMessage('Batch: Import Allocations problem');
    }

    $this->emailDevCronLog($results['logCronErrors']);
  }

  /**
   *
   */
  private function getApiKey() {
    $path = \Drupal::service('file_system')->realpath("private://") . '/.keys/secrets.json';
    if (!file_exists($path)) {

      $this->collectCronLog("Unable to get allocations api key.", 'err');
      return NULL;
    }
    $secretsData = json_decode(file_get_contents($path), TRUE);
    return ($secretsData['ramps_api_key']);
  }

  /**
   *
   */
  private function getApiPortalUserNames($startIndex = 0, $processLimit = NULL) {

    $portalUserNames = [];
    try {
      $requestUrl = 'https://allocations-api.access-ci.org/identity/profiles/v1/people?with_allocations=1';
      $apiKey = $this->getApiKey();
      $requestOpts = [
        'headers' => [
          'XA-API-KEY' => $apiKey,
          'XA-REQUESTER' => 'MATCH',
          'Content-Type' => 'application/json',
        ],
        'curl' => [CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1],
      ];

      $client = new Client();
      $response = $client->request('GET', $requestUrl, $requestOpts);
      $responseJson = Json::decode((string) $response->getBody());
    }
    catch (\Exception $e) {
      $this->collectCronLog('Allocations api: ' . $e->getMessage(), 'err');
      return FALSE;
    }

    $incomingCount = 0;
    $processedCount = 0;

    $this->collectCronLog("Initial API import: total received: " . count($responseJson), 'i');

    try {
      foreach ($responseJson as $aUser) {
        $incomingCount++;
        if ($incomingCount < $startIndex) {
          continue;
        }

        if ($aUser['isSuspended'] || $aUser['isArchived']) {
          continue;
        }
        $processedCount++;
        if ($processLimit != NULL && $processedCount > $processLimit) {
          break;
        }
        $portalUserNames[] = $aUser['username'];
      }
    }
    catch (\Exception $e) {
      $this->collectCronLog('Allocations portal names: ' . $e->getMessage(), 'err');
      return FALSE;
    }
    return $portalUserNames;
  }

  /**
   * This gets called repeatedly by the batch processing.
   */
  public function processChunk($userNames, &$context) {
    if (!isset($context['results']['processed'])) {
      $context['results']['processed'] = [];
      $context['results']['newUsers'] = 0;
      $context['results']['newCCIds'] = 0;
      $context['results']['totalProcessed'] = 0;
      $context['results']['totalExamined'] = 0;
      $context['results']['logCronErrors'] = [];
    }

    if (!$userNames) {
      return;
    }

    $sandbox = &$context['sandbox'];
    if (!$sandbox) {
      $sandbox['progress'] = 0;
      $sandbox['max'] = count($userNames);
      $sandbox['userNames'] = $userNames;
      $sandbox['batchNum'] = 0;
    }

    $sandbox['batchNum']++;
    $context['message'] = "Processing in batch " . $sandbox['batchNum'];
    \Drupal::logger('cron_affinitygroup')->notice('LCE in batch count:' . count($this->logCronErrors));
    $userNameArray = array_splice($sandbox['userNames'], 0, $this->batchSize);

    $apiKey = $this->getApiKey();
    $userCount = 0;
    $newUsers = 0;
    $newCCIds = 0;

    try {
      // Process each user in the chunk
      // call api to get resources, get user, (add user + add to cc if needed)
      // update user's resources if needed, check if they are members of associated ags.
      foreach ($userNameArray as $userName) {

        $context['results']['totalExamined']++;
        $sandbox['progress']++;
        $userCount++;

        try {
          $aUser = $this->allocApiGetResources($userName, $apiKey);

          if (empty($aUser) || count($aUser['resources']) == 0) {
            $this->collectCronLog("!!! " . $sandbox['batchNum'] . " batch at $userCount NO RESOURCES for $userName", 'd');
            // Once the api is updated, this should be rare.
            continue;
          }
          $context['message'] = "Processing in batch " . $sandbox['batchNum'] . " $userName";
          $context['results']['totalProcessed']++;

          $this->collectCronLog($sandbox['batchNum'] . " batch at $userCount PROCESSING $userName", 'd');
          $accessUserName = $userName . '@access-ci.org';
          $userDetails = user_load_by_name($accessUserName);

          // If we already have user, make sure they are all set with the cc id.
          // Check for diffs and update user profile with email, names, citizenship, email, institution.
          $needCCId = TRUE;
          if ($userDetails) {
            $this->userDetailUpdates($userDetails, $aUser);
            $needCCId = needsCCId($userDetails);
          }

          // Did not have the user, so create it.
          // ALERT TODO what if the have a new email in here and it's different than existing one....
          if ($userDetails === FALSE) {
            $userDetails = $this->createAllocUser($accessUserName, $aUser);
            // @todo TAKE OUT
            $this->collectCronLog("...creating $userCount: $userName", 'd');
            if ($userDetails) {
              $newUsers++;
              $context['results']['newUsers']++;
            }
          }

          if ($needCCId && $userDetails) {
            // Either new user just created, or existing user missing constant contact id.
            // @todo TAKE OUT
            $this->collectCronLog("...need CC id: $userName", 'd');

            if (!$this->batchNoCC) {
              if ($this->cronAddToConstantContact($userDetails, $aUser['email'], $aUser['firstName'], $aUser['lastName'])) {
                $newCCIds++;
                $context['results']['newCCIds']++;
              }
            }
          }

          // Find our internal storage ids (node ids) for each of the
          // incoming list of cider names.
          $newCiderRefnums = [];
          foreach ($aUser['resources'] as $cider) {

            $query = \Drupal::entityQuery('node');
            $refnum = $query
              ->condition('type', 'access_active_resources_from_cid')
              ->condition('field_access_global_resource_id', $cider['cider_resource_id'])
              ->execute();

            if (empty($refnum)) {
              $this->collectCronLog("Not found in CiDeR active res:  " . $cider['cider_resource_id'], 'd');
            }
            else {
              $newCiderRefnums[] = reset($refnum);
            }
          }

          // Now we have a loaded userDetails whether existing or newly created. Check the user's Resources to see if they still match.
          // now from list of resource_id for this user, check each to see which AG they are associated with. build list of AGs for this user.
          $userCiderRefnums = [];
          $userCiderArray = $userDetails->get('field_cider_resources')->getValue();
          foreach ($userCiderArray as $userCider) {
            $userCiderRefnums[] = $userCider['target_id'];
          }

          // Is incoming list different from what user already has? if so, reset the user list.
          $intersect = array_intersect(array_values($newCiderRefnums), array_values($userCiderRefnums));
          if (count($intersect) !== count($newCiderRefnums) || count($intersect) !== count($userCiderRefnums)) {

            $this->collectCronLog('---updating user ciders; count new: ' . count($newCiderRefnums) . ' was ' . count($userCiderRefnums), 'd');
            $this->updateUserCiderList($userDetails, $newCiderRefnums);
          }
          // Finally, check for AG membership in each AG corresponding to the ciderRefnum.
          // Gather a list of associated affinity groups (unique)
          // The Cider Refs might be associated with multiples AGs;
          // an AG has 0 to many Cider Refs.
          $agNodes = [];
          foreach ($newCiderRefnums as $refnum) {

            $query = \Drupal::entityQuery('node');
            $agNodeIds = $query
              ->condition('type', 'affinity_group')
              ->condition('field_cider_resources', $refnum)
              ->execute();

            foreach ($agNodeIds as $id) {
              if (!in_array($id, $agNodes)) {
                $agNodes[] = $id;
              }
            }
          }
          // and, we add every user to ACCESS Support AG.
          $agNodes[] = $this->accessSupportNodeId;

          // Now we have agNodes, which is a list of all affinity groups having to do with the user's allocations.
          // set membership will add the user to the group unless they previously blocked automembership to the ag by leaving.
          $userBlockedArray = $userDetails->get('field_blocked_ag_tax')->getValue();
          $userBlockedAgTids = [];
          foreach ($userBlockedArray as $userBlock) {
            $userBlockedAgTids[] = $userBlock['target_id'];
          }

          foreach ($agNodes as $agNid) {
            $this->setUserMembership($agNid, $userDetails, $userBlockedAgTids, TRUE);
          }
        }
        catch (\Exception $e) {
          $this->collectCronLog("Exception for incoming user $userName  " . $e->getMessage(), 'err');
        }
      } // end foreach userName

    }
    catch (\Exception $e) {
      $this->collectCronLog("Exception while processing api results at $userCount " . $e->getMessage(), 'err');
    }

    $this->collectCronLog("Batch " . $sandbox['batchNum'] . ". New users: $newUsers, CC Ids added: $newCCIds / $userCount", 'i');

    \Drupal::logger('cron_affinitygroup')->notice("LCE for " . $sandbox['batchNum'] . " log before add " . count($this->logCronErrors));
    \Drupal::logger('cron_affinitygroup')->notice("LCE for " . $sandbox['batchNum'] . " context before add " . count($context['results']['logCronErrors']));
    $context['results']['logCronErrors'] = array_merge($context['results']['logCronErrors'], $this->logCronErrors);
    \Drupal::logger('cron_affinitygroup')->notice("LCE for " . $sandbox['batchNum'] . " count context after add " . count($context['results']['logCronErrors']));

    // Batch stops when finished is < 1.
    $context['finished'] = $sandbox['progress'] / $sandbox['max'];
    return TRUE;
  }

  /**
   * Send in userdetail to check for absent cc id. If not there, attempt to add.
   * return boolean success.
   */
  private function cronAddToConstantContact($u, $uEmail, $firstName, $lastName) {
    $ccId = addUserToConstantContact($uEmail, $firstName, $lastName, TRUE);
    if (empty($ccId)) {
      $this->collectCronLog("Could not add user to Constant Contact:  $uEmail");
      return FALSE;
    }
    else {
      $u->set('field_constant_contact_id', $ccId);
      $u->save();
      $this->collectCronLog("Id from Constant Contact:  $uEmail", 'd');
      return TRUE;
    }
  }

  /**
   * Collect problems adding user, etc  here to send as a dev alert email at end of cron
   * also used for logging both errors and status
   * we might do this in a file.
   */
  private function collectCronLog($msg, $logType = 'err') {

    if ($logType === 'err') {

      $this->logCronErrors[] = $msg;
      \Drupal::logger('cron_affinitygroup')->error($msg);
    }
    elseif ($logType === 'i') {
      \Drupal::logger('cron_affinitygroup')->notice($msg);
    }
    else {
      // @todo how to turn these off automatically in production after we do initial giant create
      \Drupal::logger('cron_affinitygroup')->debug($msg);
    }
  }

  /**
   * Send an email with the collected cron errors to users with
   * role site_developer.  $errorList is an array of strings with
   * the collected errors.
   */
  private function emailDevCronLog($errorList) {

    \Drupal::logger('cron_affinitygroup')->notice('IN EMAIL DEV CRON LOG count:' . count($errorList));

    if (empty($errorList) || count($errorList) == 0) {
      return;
    }

    $body = 'ERRORS: ' . implode('\n', $errorList);

    // Truncate to something reasonable to email in case of large error set.
    if (strlen($body) > 3000) {
      $body = substr($body, 0, 3000) . "...\nsee logs for more.";
    }
    \Drupal::logger('cron_affinitygroup')->notice('EMAIL body:' . $body);

    $nr = new NotifyRoles();
    $nr->notifyRole('site_developer', 'Errors during allocations import', $body);
  }

  /**
   *
   */
  private function createAllocUser($accessUserName, $aUser) {
    try {
      $u = User::create();
      $u->set('status', 1);
      $u->setUsername($accessUserName);
      $u->setEmail($aUser['email']);

      $u->set('field_user_first_name', $aUser['firstName']);
      $u->set('field_user_last_name', $aUser['lastName']);
      $u->set('field_institution', $aUser['organizationName']);
      $citzenships = $this->formatCitizenships($aUser['citizenships']);
      $u->set('field_citizenships', $citzenships);

      $u->save();
      $y = $u->id();
      $this->collectCronLog("User created: $accessUserName - " . $aUser['email'] . " ($y)", 'i');
    }
    catch (\Exception $e) {
      $this->collectCronLog("Exception createUser $accessUserName: " . $e->getMessage());
      $u = FALSE;
    }
    return $u;
  }

  /**
   * Compare incoming details to see if anything changed. If so, write to user profile.
   * If email or name changed, update in constant contact, if user already has a CC Id.
   */
  private function userDetailUpdates($u, $a) {
    // If  ($aUser['firstName'] , $aUser['lastName'], $aUser['email'].
    try {
      $needProfileUpdate = FALSE;
      $needCCUpdate = FALSE;
      $log = '';

      if ($a['email'] !== $u->getEmail()) {
        $log .= 'email: ' . $u->getEmail() . ' to ' . $a['email'];
        $u->setEmail($a['email']);
        $needProfileUpdate = TRUE;
        $needCCUpdate = TRUE;
      }

      if ($a['firstName'] !== $u->get('field_user_first_name')->getString()) {
        $log .= ' first: ' . $u->get('field_user_first_name')->getString() . ' to ' . $a['firstName'];
        $u->set('field_user_first_name', $a['firstName']);
        $needProfileUpdate = TRUE;
        $needCCUpdate = TRUE;
      }

      if ($a['lastName'] !== $u->get('field_user_last_name')->getString()) {
        $log .= ' last: ' . $u->get('field_user_last_name')->getString() . ' to ' . $a['lastName'];
        $u->set('field_user_last_name', $a['lastName']);
        $needProfileUpdate = TRUE;
        $needCCUpdate = TRUE;
      }

      if ($a['organizationName'] !== $u->get('field_institution')->getString()) {
        $log .= ' org: ' . $u->get('field_institution')->getString() . ' to ' . $a['organizationName'];
        $u->set('field_institution', $a['organizationName']);
        $needProfileUpdate = TRUE;
      }

      $citizenships = $this->formatCitizenships($a['citizenships']);

      if ($citizenships !== $u->get('field_citizenships')->getString()) {
        $log .= ' cit: ' . $u->get('field_citizenships')->getString() . ' to ' . $citizenships;
        $u->set('field_citizenships', $citizenships);
        $needProfileUpdate = TRUE;
      }

      if ($needProfileUpdate) {
        if (!$this->batchNoUserDetSave) {
          $u->save();
        }
        $this->collectCronLog('Updating user ' . $a['username'] . ': ' . $log, 'd');
      }
      // If name or email changed, and user already has constant contact account, update there.
      if ($needCCUpdate) {
        $ccIdField = $u->get('field_constant_contact_id')->getValue();
        if (!empty($ccIdField)) {
          $ccId = $ccIdField[0]['value'];
          if (!empty($ccId)) {
            $cca = new ConstantContactApi();
            $cca->setSupressErrDisplay(TRUE);
            $cca->updateContact($ccId, $a['firstName'], $a['lastName'], $a['email']);
            $this->collectCronLog('CC Account Update for ' . $a['username'] . ' ' . $a['email'] . ' ' . $ccId);
          }
        }
      }
    }
    catch (\Exception $e) {
      $this->collectCronLog('Exception in UserDetailUpdates for ' . $a['username'] . ': ' . $e->getMessage(), 'err');
    }
    // If we have CC id, and email or name changed, update there, too.
    // @todo .
    return;
  }

  /**
   * Citizenships are stored on user as single display string.
   * $citJson: citzenships json section from api.
   */
  private function formatCitizenships($citJson) {

    $citizenships = '';
    $initial = TRUE;
    try {
      foreach ($citJson as $x) {
        if (!$initial) {
          $citizenships .= ', ';
        }
        $citizenships .= $x['countryName'];
        $initial = FALSE;
      }
    }
    catch (\Exception $e) {
    }
    return $citizenships;
  }

  /**
   * User membership for an affinity group is stored in a per-user flag (not global) on the ag.
   * $agNid - AG node id
   * $blocklist - array of taxonomy ids for blocked ags.
   */
  private function setUserMembership($agNid, $userDetails, $blockList, $isJoin) {
    $ag = Node::load($agNid);
    $agTax = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['name' => ($ag->get('title')->value)]);
    $agTax = reset($agTax);

    // For joining only, check if ag is on block list.
    if ($isJoin && in_array($agTax->id(), $blockList)) {
      $this->collectCronLog('...user blocked ' . $agTax->id() . ' ' . $ag->get('title')->value, 'd');
      return;
    }

    $flagService = \Drupal::service('flag');
    // Replace by flag machine name.
    $flag = $flagService->getFlagById('affinity_group');

    // Check if already flagged. If not, set the join flag, and
    // call constant contact api to add to corresponding email group.
    $flagStatus = $flagService->getFlagging($flag, $agTax, $userDetails);
    if (!$flagStatus) {

      $flagService->flag($flag, $agTax, $userDetails);

      if (!$this->batchNoCC) {
        $this->allocSubscribeToCCList($agTax->id(), $userDetails);
      }
      $this->collectCronLog("...add member: " . $ag->get('title')->value . ': ' . $userDetails->get('field_user_last_name')->getString(), 'd');
    }
    else {
      // $this->collectCronLog("...already  member: ".$ag->get('title')->value, 'd');
    }
  }

  /**
   *
   */
  private function allocSubscribeToCCList($taxonomyId, $userDetails) {
    $postJSON = makeListMembershipJSON($taxonomyId, $userDetails);
    if (empty($postJSON)) {
      $this->collectCronLog("...error in subscribe; possible missing CC ID", 'err');
    }
    else {
      $cca = new ConstantContactApi();
      $cca->setSupressErrDisplay(TRUE);
      $cca->apiCall('/activities/add_list_memberships', $postJSON, 'POST');
    }
  }

  /**
   * Reset user's cider list.
   */
  private function updateUserCiderList($userDetails, $ciderRefnums) {
    $userDetails->set('field_cider_resources', NULL);
    foreach ($ciderRefnums as $refnum) {
      // $this->collectCronLog("CIDER setting" . $refnum, 'd');
      $userDetails->get('field_cider_resources')->appendItem($refnum);
    }
    $userDetails->save();
  }

  /**
   *
   */
  private function allocApiGetResources($userName, $apiKey) {
    $responseJson = [];
    try {
      $reqUrl = "https://allocations-api.access-ci.org/identity/profiles/v1/people/$userName?resources=1";
      $requestOpts = [
        'headers' => [
          'XA-API-KEY' => $apiKey,
          'XA-REQUESTER' => 'MATCH',
          'Content-Type' => 'application/json',
        ],
        'curl' => [CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1],
      ];

      $client = new Client();
      $response = $client->request('GET', $reqUrl, $requestOpts);
      $responseJson = Json::decode((string) $response->getBody());
    }
    catch (\Exception $e) {
      $this->collectCronLog("Alloc resources api call $userName:" . $e->getMessage(), 'err');
    }
    return $responseJson;
  }

  /**
   * Sync Affinity Group members with constant contact lists.
   *
   * Normally, the user joining an affinity group triggers a call to
   * Constant Contact to add the user to the corresponsing CC email list.
   * If CC was not hooked up at the time the user joins the AG, they will
   * not be on the CC list. This function syncs the lists.
   */
  public function syncAGandCC($agBegin = 0, $agEnd = 1000) {
    // Get all the Affinity Groups.
    $agCount = 0;
    $this->collectCronLog("sync: ag $agBegin to $agEnd ", 'i');
    $nids = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', 'affinity_group')
      ->execute();
    $nodes = Node::loadMultiple($nids);
    $cca = new ConstantContactApi();

    foreach ($nodes as $node) {
      $agCount += 1;
      if ($agCount < $agBegin || $agCount > $agEnd) {
        continue;
      }
      $ccContacts = [];
      $agContacts = [];
      $agContactsNoCCid = [];

      $agTitle = $node->getTitle();
      try {
        $this->collectCronLog("$agCount: sync $agTitle", 'd');

        // Get constant contact list id.
        $listId = $node->get('field_list_id')->value;
        if (!$listId || strlen($listId) < 1) {
          $this->collectCronLog("!! No list id for $agTitle", 'd');
          continue;
        }

        // Assemble users belonging to this group (each stored on flag on the associated term),.
        $term = $node->get('field_affinity_group');
        $flag_service = \Drupal::service('flag');
        $flags = $flag_service->getAllEntityFlaggings($term->entity);

        foreach ($flags as $flag) {
          $uid = $flag->get('uid')->target_id;
          $user = User::load($uid);
          $field_val = $user->get('field_constant_contact_id')->getValue();
          if (!empty($field_val) && $field_val != 0) {
            $ccId = $field_val[0]['value'];
            $agContacts[] = $ccId;
            // $agContacts[] = substr($ccId, 0, 36);
          }

          else {
            // Users without cc id. might not do anything with this here, not sure yet.
            $agContactsNoCCid[] = $uid;
          }
        }

        $this->collectCronLog("sync: users in this AG with no CC id count : " . count($agContactsNoCCid), 'd');

        // Assemble list of users on the cc list.
        $resp = $cca->apiCall("/contacts?lists=$listId");
        if (empty($resp->contacts)) {
          $this->collectCronLog("sync: $agTitle CC list response: empty.", 'err');
          continue;
        }

        foreach ($resp->contacts as $contact) {
          $ccContacts[] = $contact->contact_id;
        }

        $this->collectCronLog('members cc: ' . count($ccContacts) . ' ag: ' . count($agContacts), 'd');

        $notInCC = array_diff($agContacts, $ccContacts);
        $this->collectCronLog("sync: to be added to list $agTitle count: " . count($notInCC), 'i');

        if (count($notInCC)) {
          $postData = [
            'source' => [
              'contact_ids' => $notInCC,
            ],
            'list_ids' => [$listId],
          ];

          $cca->apiCall('/activities/add_list_memberships', json_encode($postData), 'POST');
        }
      }
      catch (\Exception $e) {
        $this->collectCronLog("Sync: " . $e->getMessage(), 'err');
      }
    } // for each affinity group node
  }

  /**
   * Clean obsolete allocations.
   *
   * We need to periodically clean of lists of allocations from users
   * who no longer have any. When we import users and update their allocations,
   * we only get information about active users, which are the users who do have
   * allocations.  We do not want to check for the absense of a user every time
   * we run the import, so we have this utility function which will run less
   * often.
   * Here, we get the active users list, and find users in our database who are
   * not on this list but who do have have lingering allocations on their profile.
   *
   * start/stop - integer index in list of users where to start and stop processing
   */
  public function cleanObsoleteAllocations($indexStart, $indexStop) {
    $this->collectCronLog("Clean Obs: user count $indexStart, $indexStop ", 'i');
    $obsoleteCount = 0;
    try {
      $portalNames = $this->getApiPortalUserNames();
      $this->collectCronLog("Clean Obs: portal count " . count($portalNames), 'i');
      if ($portalNames) {

        // Make destination list of emails of users with administrator role.
        $userIds = \Drupal::entityQuery('user')
          ->condition('status', 1)
          ->execute();
        $userCount = count($userIds);
        $this->collectCronLog("Clean Obs: user count $userCount ", 'i');

        $index = 0;
        foreach ($userIds as $userId) {

          $index++;
          if ($index < $indexStart) {
            continue;
          }
          if ($index > $indexStop) {
            break;
          }

          $user = User::load($userId);

          $userCiderArray = $user->get('field_cider_resources')->getValue();
          // $this->collectCronLog($index . '. ' . $userId . ' ' . $user->getAccountName() . ' - ' . count($userCiderArray), 'i');
          // If ciders are listed on user's account, see if they are on the allocaions api list
          // of active users.
          if (count($userCiderArray) > 0) {

            // Username without @access-ci.org is the portal name.
            $accountSub = str_replace("@access-ci.org", "", $user->getAccountName());
            if (strlen($accountSub) < strlen($user->getAccountName())) {
              $found = array_search($accountSub, $portalNames);
              if (empty($found)) {
                $this->updateUserCiderList($user, []);
                $this->collectCronLog("Reset obsolete allocation list on " . $user->getAccountName() . " id $userId (at $index)", 'i');
                $obsoleteCount++;
              }
            }
          }
        }
      }
    }
    catch (\Exception $e) {
      $this->collectCronLog("Clean Obs: " . $e->getMessage(), 'err');
    }
    $this->collectCronLog("Finished Cleaning Obsolete: reset allocations on $obsoleteCount users", 'i');
  }

}
