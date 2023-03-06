<?php

namespace Drupal\access_affinitygroup\Plugin;

/**
 * @file
 * Import users using allocations api.
 */

use GuzzleHttp\Client;
use Drupal\node\Entity\Node;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Serialization\Json;

class AllocationsUsersImport {

  // For devtest, put in front of emails and uname for dev; set to '' to use real names.
  private $cDevUname;

  //for $this->collectCronLog for dev status email.
  private $logCronErrors;

  //for $this->collectCronLog for dev status email.
  private $logCronInfo = [];

  public function __construct() {
    $cDevUname = '';
    $logCronErrors = [];
    $logCronInfo = [];
  }

/**
 * Update current users with their current allocations and set their cider resrouces field (allocations), and
 * add them to associated affinity groups. If user not found in database, we create it here.
 * run from cron
 * ciderName: this is the "Global Resource Id" (from CiDeR), aka "info_resource_id" from xdusage api aka "allocation"
 * Example: anvil-gpu.perdue.xsede.org
 * ciderRefNum: the entity number/ the interal id for the entity of type Access Active Resources from CiDeR
 * Example: '429'
 * variable names beginning with "xd" have to do with the info incoming from the api.
 * Every user will be added to the ACCESS Support affinity group.
 */
function updateUserAllocations()
{
  global $logCronErrors;
  global $logCronInfo;
  $this->collectCronLog('Running updateUserAllocations.', 'i');

  try {
    // get access support affinity group node for later
    $nArray = \Drupal::entityQuery('node')
      ->condition('type', 'affinity_group')
      ->condition('title', 'ACCESS Support')
      ->execute();
    $accessSupportNodeId = empty($nArray) ? 0 :  array_values($nArray)[0];

    $apiBase = 'https://allocations-api.access-ci.org/acdb/xdusage';
    $requestUrl = $apiBase . '/v2/projects?active_only&not_expired';

    $path = \Drupal::service('file_system')->realpath("private://") . '/.keys/secrets.json';
    if (!file_exists($path)) {
        $this->collectCronLog("Unable to get xsede api key.");
      return FALSE;
    }
    $secretsData = json_decode(file_get_contents($path), TRUE);
    $apiKey = $secretsData['xdusage_api_key'];
    $requestOpts = [
      'headers' => [
        'XA-API-KEY' => $apiKey,
        'XA-AGENT'   => 'xdusage',
        'XA-RESOURCE' => 'support.access-ci.org',
        'Content-Type' => 'application/json',
      ],
      'curl' => [CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1],
    ];

    $client = new Client();
    $response = $client->request('GET', $requestUrl, $requestOpts);

    $responseJson = Json::decode((string) $response->getBody());
  } catch (Exception $e) {
      $this->collectCronLog('xdusage api call: ' . $e->getMessage());
    return FALSE;
  }
  $userCount = 0;
  $newUsers = 0;
  $newCCIds = 0;
  try {
    /*
     * Api response is a list of projects. We want the user name and resource
     * from each. Users can be on many projects.
     * Compile a list of portal usernames (duplicated removed) with their
     * associated projects (duplicates removed).
     */
    $xdProjectsArray = $responseJson['result'];
    $xdAllocations = [];

    // Assemble array of users and which resources (allocations) they use, discarding duplicates.
    foreach ($xdProjectsArray as $xdProject) {

      $xdUserName = $xdProject['pi_portal_username'];
      if (array_key_exists($xdUserName, $xdAllocations)) {
        // Have user. add this rid if they don't already have in the list we are gathering for this use.
        if (array_search($xdProject['info_resource_id'], $xdAllocations[$xdUserName]) === FALSE) {
          array_push($xdAllocations[$xdUserName], $xdProject['info_resource_id']);
        }
      } else {
        // User not on list, so add it.
        $xdAllocations[$xdUserName] = [$xdProject['info_resource_id']];
      }
    }

      // For each user: if user does not exist, create the user. First look for the user via
      // the access version of the name, which is their portal name plus @access-ci.org tacked on
      // Once we have a user handle, whether newly created or existing,
      // for each Resource Id (allocation), find related AG. Make sure user is part of that AG.
    $this->collectCronLog('Users from api: count ' . count($xdAllocations), 'i');

    foreach ($xdAllocations as $xdUserName => $xdUserCiderNames) {

      try {
        $userCount++;
        // If ($userCount > 100)  {kint($userCount); die();}
        $accessUserName = $xdUserName . $cDevUname . '@access-ci.org';
          $this->collectCronLog("Processing $userCount: $accessUserName", 'd');
        $userDetails = user_load_by_name($accessUserName);

        // If we already have user, make sure they are all set with the cc id.
        // @todo potentially, will  need to put in a check if they have
        // changed their email or their firstname or lastname, and change it for
        // the drual database.
        $needCCId = TRUE;
        if ($userDetails) {
          $needCCId = needsCCId($userDetails);
        }

        // Did not have the user, so create it. Ongoing, this is much the less frequent case
        // so instead of storing everyone's account info in $users, go back to incoming to find user's info in one of their projects.
        if ($userDetails === FALSE || $needCCId) {
          $xdOneUsersProjects = $this->array_usearch($xdProjectsArray, function ($o) use ($xdUserName) {
            return $o['pi_portal_username'] === $xdUserName;
          });
          // Get just first one.
          $xdOneUsersProjects = reset($xdOneUsersProjects);
        }
        if ($userDetails === FALSE) {
          $userDetails = $this->createUser($xdOneUsersProjects['pi_portal_username'], $xdOneUsersProjects['pi_first_name'], $xdOneUsersProjects['pi_last_name']);
          // Portal email is found and set during create user.
          $portalEmail = $userDetails->getEmail();
          if ($userDetails) {
            $newUsers++;
          }
        }
        if ($needCCId) {
          // Already had user in our DB, but still need to get the email in the api.
          $portalEmail = $this->getEmailFromApi($xdOneUsersProjects['pi_portal_username']);
        }
        if ($needCCId && $userDetails) {
          // Either new user just created, or existing user missing constant contact id.
          if ($this->cronAddToConstantContact($userDetails, $portalEmail,
                                              $xdOneUsersProjects['pi_first_name'],
                                              $xdOneUsersProjects['pi_last_name'])) {
            $newCCIds++;
          }
        }

        // !!(todo - Should really make a lookup table for all cider rid=>entityIds)
        // map the  incoming resource names for this user to array of the internal reference numbers
        // for comparison with those in the user's existing list of resource names
        $xdCiderRefnums = [];
        foreach ($xdUserCiderNames as $ciderName) {

          // Get a list of the corresponding entity ids for the incoming
          // this can be used to compare to the existing.
          // @todo should this be case-sensitive or not.
          $query = \Drupal::entityQuery('node');
          $refnum = $query
            ->condition('type', 'access_active_resources_from_cid')
            ->condition('field_access_global_resource_id', $ciderName)
            ->execute();

          if (empty($refnum)) {
            // collectCronLog("Not found in CiDeR active res: $ciderName ", 'd');.
          } else {
            array_push($xdCiderRefnums, reset($refnum));
          }
        }

        // Now we have a loaded userDetails whether existing or newly created. Check the user's Resources to see if they still match.
        // now from list of resource_id for this user, check each to see which AG they are associated with. build list of AGs for this user.
        $userCiderRefnums = [];
        $userCiderArray = $userDetails->get('field_cider_resources')->getValue();
        foreach ($userCiderArray as $userCider) {
          $userCiderRefnums[] = $userCider['target_id'];
        }

        // Is xd incoming list different from what user already has? if not, reset the user list.
        $intersect = array_intersect(array_values($xdCiderRefnums), $userCiderRefnums);

        if (count($intersect) !== count($xdCiderRefnums) || count($intersect) !== count($userCiderRefnums)) {

          $this->collectCronLog('---updating user ciders; count new: ' . count($xdCiderRefnums) . ' was ' . count($userCiderRefnums), 'i');
          $this->updateUserCiderList($userDetails, $xdCiderRefnums);
        }
        // Finally, check for AG membership in each AG corresponding to the ciderRefnum.
        // Gather a list of associated affinity groups (unique)
        // The Cider Refs might be associated with multiples AGs;
        // an AG has 0 to many Cider Refs.
        $agNodes = [];
        foreach ($xdCiderRefnums as $refnum) {

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
        $agNodes[] = $accessSupportNodeId;

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
      } catch (Exception $e) {
        $this->collectCronLog("Exception for incoming user $xdUserName  " . $e->getMessage());
      }
    } // end foreach xdAllocation (each unique returned from api)
  } catch (Exception $e) {
    $this->collectCronLog("Exception while processing api results at $userCount " . $e->getMessage());
  }
  $this->collectCronLog("Finished Allocation import. Processed: $userCount,  New users: $newUsers, CC Ids added: $newCCIds", 'i');
  return TRUE;
}


/**
 * Send in userdetail to check for absent cc id. If not there, attempt to add.
 * return boolean success.
 */
function cronAddToConstantContact($u, $uEmail, $firstName, $lastName)
{

  $ccId = addUserToConstantContact($uEmail, $firstName, $lastName);
  if (empty($ccId)) {
    $this->collectCronLog("Could not add user to Constant Contact:  $uEmail");
    return FALSE;
  } else {
    $u->set('field_constant_contact_id', $ccId);
    $u->save();
    $this->collectCronLog("Id from Constant Contact:  $uEmail", 'i');
    return TRUE;
  }
}

/**
 * Collect problems adding user, etc  here to send as a dev alert email at end of cron
 * also used for logging both errors and status
 * we might do this in a file.
 */
private function collectCronLog($msg, $logType = 'err')
{
  global $logCronErrors;
  global $logCronInfo;

  if ($logType === 'err') {
    $logCronErrors[] = $msg;
    \Drupal::logger('cron_affinitygroup')->error($msg);
  } elseif ($logType === 'i') {
    \Drupal::logger('cron_affinitygroup')->notice($msg);
    $logCronInfo[] = $msg;
  } else {
    \Drupal::logger('cron_affinitygroup')->debug($msg);
  }
}

/**
 * Send an email with the collected cron errors to users with role: cyberdevs.
 * TODO: This function does not work correctly at this time.
 */
function emailDevCronLog()
{

  global $logCronErrors;
  // Global $logCronInfo;.
  if (empty($logCronErrors) || count($logCronErrors) == 0) {
    return;
  }

  // Make destination list of emails of users with administrator role.
  $userIds = \Drupal::entityQuery('user')
    ->condition('status', 1)
    ->condition('roles', 'cyberdevs')
    ->execute();
  $users = User::loadMultiple($userIds);
  $toAddrs = '';
  $userCount = count($users);
  $iterate = 0;
  foreach ($users as $user) {
    $iterate++;
    $toAddrs .= $user->get('mail')->getString();
    if ($userCount != $iterate) {
      $toAddrs .= ",";
    }
  }

  $params = [];
  $params['to'] = $toAddrs;
  $body = '';
  if (!empty($logCronErrors)) {
    $body = 'ERRORS: ' . implode('\n', $logCronErrors);
  }
  // If (!empty($logCronInfo)) { $body = $body. '\nINFO: ' . implode('\n' , $logCronInfo);}.
  $params['body'] = $body;
  $params['title'] = 'ACCESS CRON: errors during xsede user + allocations import';
  $langcode = \Drupal::currentUser()->getPreferredLangcode();
  $module = 'access_affinitygroup';
  $key = 'access_affinitygroup';
  $mailManager = \Drupal::service('plugin.manager.mail');
  $result = $mailManager->mail($module, $key, $toAddrs, $langcode, $params, NULL, TRUE);
  if (
    $result === FALSE
    || (array_key_exists('result', $result) && !$result['result'])
  ) {
    \Drupal::logger('cron_affinitygroup')->error("Error sending mail to " . $toAddrs);
  }
}

/**
 * For use with updateUserAllocations cron.
 * create the user and call the api to get their email.
 */
function createUser($portalName, $firstName, $lastName)
{
  try {

    // $portalName = $userProject['pi_portal_username'];
    $accessName = $portalName . $cDevUname . '@access-ci.org';

    $uEmail = $this->getEmailFromApi($portalName);

    $u = User::create();
    $x = $u->id();
    $u->set('status', 1);
    $u->setUsername($accessName);
    $u->setEmail($uEmail);

    $u->set('field_user_first_name', $firstName);
    $u->set('field_user_last_name', $lastName);

    $u->save();
    $y = $u->id();
    $this->collectCronLog("User created $accessName - $uEmail ($y)", 'i');
  } catch (Exception $e) {
    $this->collectCronLog("Exception createUser $portalName: " . $e->getMessage());
    $u = FALSE;
  }
  return $u;
}

/**
 *
 */
function getEmailFromApi($portalName)
{
  // Get additional user details from api, bc we need the email.
  $userInfo = get_account_data_from_api($portalName);

  if ($userInfo === FALSE) {
    // Log inability to get email but continue with create.
    $uEmail = '';
    $this->collectCronLog("Could not get user email from API, user: $portalName");
  } else {
    $uEmail = $cDevUname . $userInfo['email'];
  }
  return $uEmail;
}


/**
 * User membership for an affinity group is stored in a per-user flag (not global) on the ag.
 * $agNid - AG node id
 * $blocklist - array of taxonomy ids for blocked ags.
 */
function setUserMembership($agNid, $userDetails, $blockList, $isJoin)
{

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
    subscribeToCCList($agTax->id(), $userDetails);
    $this->collectCronLog("...add member: " . $ag->get('title')->value . ': ' . $userDetails->get('field_user_last_name')->getString(), 'i');
  } else {
    // $this->collectCronLog("...user already a member: ".$ag->get('title')->value, 'i');.
  }
}

/**
 * Reset user's cider list.
 */
function updateUserCiderList($userDetails, $ciderRefnums)
{

  $userDetails->set('field_cider_resources', NULL);
  foreach ($ciderRefnums as $refnum) {
    $userDetails->get('field_cider_resources')->appendItem($refnum);
  }
  $userDetails->save();
}

/**
 *
 */
function array_usearch(array $array, callable $comparitor)
{
  return array_filter(
    $array,
    function ($element) use ($comparitor) {
      if ($comparitor($element)) {
        return $element;
      }
    }
  );
}

}
