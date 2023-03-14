<?php
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
namespace Drupal\access_affinitygroup\Plugin;

use GuzzleHttp\Client;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Batch\BatchBuilder;

class AllocationsUsersImport
{
    // these are defaults if not set on form.
    const DEFAULT_SIZE = 5;        // default if not set: how many users to process in each batch
    const DEFAULT_IMPORTLIMIT = 100;  // stop after this amount. total is > 100k, will be 12k when api updated
    const DEFAULT_NOCC = true;   // if true, don't create new constant contact user. for dev

    private $batchSize;
    private $batchImportLimit;
    private $batchNoCC;

    // For devtest, put in front of emails and uname for dev; set to '' to use real names.
    private $cDevUname;
    //for $this->collectCronLog for dev status email.
    private $logCronErrors = [];
    public function __construct()
    {
        $cDevUname = '';
        $logCronErrors = [];
    }

    // can start from drush or from form, todo: cron
    function startBatch()
    {
        $this->batchSize = \Drupal::state()->get('access_affinitygroup.allocBatchBatchSize');
        if (empty($this->batchSize)) {
            $this->batchSize = self::DEFAULT_SIZE;
        }
        $this->batchImportLimit = \Drupal::state()->get('access_affinitygroup.allocBatchImportLimit');
        if (empty($this->batchImportLimit)) {
            $this->batchImportLimit = self::DEFAULT_IMPORTLIMIT;
        }
        $this->batchNoCC = \Drupal::state()->get('access_affinitygroup.allocBatchNoCC');
        if (!isset($this->batchNoCC)) {
            $this->batchNoCC = self::DEFAULT_NOCC;
        }

        $msg1 = "Batch params size: $this->batchSize limit: $this->batchImportLimit noCC: $this->batchNoCC";
        \Drupal::messenger()->addMessage($msg1);
        $this->collectCronLog($msg1, 'i');

        $portalUserNames = $this->importUserAllocationsInit();
        $nameCount = count($portalUserNames);

        $msg1 = "Initial import done; processing  " . count($portalUserNames);
        \Drupal::messenger()->addMessage($msg1);
        $this->collectCronLog($msg1, 'i');

        $batchBuilder = (new Batchbuilder())
            ->setTitle('Importing Allocations')
            ->setInitMessage('Batch is starting for '. $nameCount)
            ->setProgressMessage('Estimated remaining time: @estimate. Elapsed time : @elapsed.')
            ->setErrorMessage('Batch error.')
            ->setFinishCallback([$this, 'importFinished']);
            // todo might need a route here

        $batchBuilder->addOperation([$this, 'processChunk'], [$portalUserNames]);

        batch_set($batchBuilder->toArray());
        if(PHP_SAPI == 'cli') {
            drush_backend_batch_process();
        } else {
            // don't need this for form; still need to test what is needed when running from cron
            //batch_process();  // some docs say need a return route arg here, some don't
        }
    }

    function importFinished($success, $results, $operations)
    {
        if (!$success) {
            $this->collectCronLog('Batch: Import Allocations problem', 'err');
            \Drupal::messenger()->addMessage('Batch: Import Allocations problem');
            $return;
        }
        $processed = count($results['processed']);
        $msg1 = "Processed $processed; total " . $results['totalProcessed'];
        $msg2 = "New users: " . $results['newUsers'] . " New Constant Contact: " . $results['newCCIds'];

        \Drupal::messenger()->addMessage($msg1);
        $this->collectCronLog("Batch: ".$msg1, 'i');

        \Drupal::messenger()->addMessage($msg2);
        $this->collectCronLog("Batch: ".$msg2, 'i');

    }

    function getApiKey()
    {
        $path = \Drupal::service('file_system')->realpath("private://") . '/.keys/secrets.json';
        if (!file_exists($path)) {

            $this->collectCronLog("Unable to get allocations api key.", 'err');
            return null;
        }
        $secretsData = json_decode(file_get_contents($path), true);
        return ( $secretsData['ramps_api_key']);
    }

    function importUserAllocationsInit()
    {
        $this->collectCronLog('Running importUserAllocations.', 'i');
        try {
            $requestUrl = 'https://allocations-api.access-ci.org/identity/profiles/v1/people';
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

        } catch (Exception $e) {
            $this->collectCronLog('allocations api ex: ' . $e->getMessage(), 'err');
            return false;
        }

        $incomingCount = 0;
        $processedCount = 0;
        $portalUserNames = [];

        $chunk = [];
        try {
            // chunk the list of user names
            foreach ($responseJson as $aUser) {
                $incomingCount++;
                if ($aUser['isSuspended'] || $aUser['isArchived']) {
                    continue;
                }

                $processedCount++;

                // todo - temp limit for dev
                if ($processedCount > $this->batchImportLimit) {
                    break; //exit for loop
                }

                $portalUserNames[] = $aUser['username'];
            }
        }catch (Exception $e) {
            $this->collectCronLog('importAllocations: ' . $e->getMessage(), 'err');
        }
        return $portalUserNames;
    }

    // this gets called repeatedly by the batch processing
    function processChunk($userNames, &$context)
    {
        if (!isset($context['results']['processed'])) {
            $context['results']['processed'] = [];
            $context['results']['newUsers'] = 0;
            $context['results']['newCCIds'] = 0;
            $context['results']['totalProcessed'] = 0;
        }

        if(!$userNames) {
            return;
        }

        $sandbox = &$context['sandbox'];
        if (!$sandbox) {
            $sandbox['progress']= 0;
            $sandbox['max'] = count($userNames);
            $sandbox['userNames'] = $userNames;
            $sandbox['batchNum'] = 0;
        }

        $sandbox['batchNum']++;
        $userNameArray = array_splice($sandbox['userNames'], 0, $this->batchSize);

        //$userNameArray = ['rpatlyon13'];
        $apiKey = $this->getApiKey();
        $userCount = 0;
        $withCidersCount = 0;
        $totalCiders = 0;
        $newUsers = 0;
        $newCCIds = 0;
        $results = [];
        $resourceArray = [];
        try {
            // get access support affinity group node for later
            $nArray = \Drupal::entityQuery('node')
                ->condition('type', 'affinity_group')
                ->condition('title', 'ACCESS Support')
                ->execute();
            $accessSupportNodeId = empty($nArray) ? 0 :  array_values($nArray)[0];

            // process each user in the chunk
            // call api to get resources, get user, (add user + add to cc if needed)
            // update user's resources if needed, check if they are members of associated ags.
            foreach ($userNameArray as $userName) {

                $context['results']['processed'][] = $userName;
                $context['results']['totalProcessed']++;
                $sandbox['progress']++;

                $userCount++;
                try {
                    $aUser = $this->allocApiGetResources($userName, $apiKey);

                    if (empty($aUser) || count($aUser['resources']) == 0) {
                        // once the api is updated, this should be rare
                        continue;
                    }

                    $this->collectCronLog("$userCount PROCESSING $userName", 'd');
                    // TODO = break if auser is empty or whatever

                    $accessUserName = $userName . '@access-ci.org';
                    $userDetails = user_load_by_name($accessUserName);

                    // If we already have user, make sure they are all set with the cc id.
                    // @todo potentially, will  need to put in a check if they have
                    // changed their email or their firstname or lastname, and change it for
                    // the drual database.
                    $needCCId = true;
                    if ($userDetails) {
                        $needCCId = needsCCId($userDetails);
                    }

                    // Did not have the user, so create it.
                    // ALERT TODO what if the have a new email in here and it's different than existing one....

                    if ($userDetails === false) {
                        $userDetails = $this->createAllocUser($accessUserName, $aUser['firstName'], $aUser['lastName'], $aUser['email']);
                        $this->collectCronLog("...creating $userCount: $userName", 'd');  // TODO TAKE OUT
                        if ($userDetails) {
                            $newUsers++;
                            $context['results']['newUsers']++;
                        }
                    }

                    // TODO - DO WE USE EXISTING EMAIL OR THE NEW ONE HERE
                    if ($needCCId && $userDetails) {
                        // Either new user just created, or existing user missing constant contact id.
                        $this->collectCronLog("...need CC id: $userName", 'd');  // TODO TAKE OUT

                        if (!$this->batchNoCC) {
                            if ($this->cronAddToConstantContact($userDetails, $aUser['email'], $aUser['firstName'], $aUser['lastName'])) {
                                $newCCIds++;
                                $context['results']['newCCIds']++;
                            }
                        }
                    }

                    // find our internal storage ids (node ids) for each of the
                    // incoming list of cider names
                    $newCiderRefnums = [];
                    foreach ($aUser['resources'] as $cider) {

                        $query = \Drupal::entityQuery('node');
                        $refnum = $query
                            ->condition('type', 'access_active_resources_from_cid')
                            ->condition('field_access_global_resource_id', $cider['resource_name'])
                            ->execute();

                        if (empty($refnum)) {
                            $this->collectCronLog("Not found in CiDeR active res:  ". $cider['resource_name'], 'd');
                        } else {
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
                    $agNodes[] = $accessSupportNodeId;

                    // Now we have agNodes, which is a list of all affinity groups having to do with the user's allocations.
                    // set membership will add the user to the group unless they previously blocked automembership to the ag by leaving.
                    $userBlockedArray = $userDetails->get('field_blocked_ag_tax')->getValue();
                    $userBlockedAgTids = [];
                    foreach ($userBlockedArray as $userBlock) {
                        $userBlockedAgTids[] = $userBlock['target_id'];
                    }

                    foreach ($agNodes as $agNid) {
                        $this->setUserMembership($agNid, $userDetails, $userBlockedAgTids, true);
                    }
                } catch (Exception $e) {
                    $this->collectCronLog("Exception for incoming user $userName  " . $e->getMessage(), 'err');
                }
            } // end foreach userName

        } catch (Exception $e) {
            $this->collectCronLog("Exception while processing api results at $userCount " . $e->getMessage(), 'err');
        }

        $this->collectCronLog("Batch " . $sandbox['batchNum']. ". New users: $newUsers, CC Ids added: $newCCIds / $userCount", 'i');

        // batch stops when finished is < 1
        $context['finished'] = $sandbox['progress'] / $sandbox['max'];
        return true;
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
            return false;
        } else {
            $u->set('field_constant_contact_id', $ccId);
            $u->save();
            $this->collectCronLog("Id from Constant Contact:  $uEmail", 'd');
            return true;
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

        if ($logType === 'err') {
            //$logCronErrors[] = $msg;
            \Drupal::logger('cron_affinitygroup')->error($msg);
        } elseif ($logType === 'i') {
            \Drupal::logger('cron_affinitygroup')->notice($msg);
        } else {
            // TODO - how to turn these off automatically in production after we do initial giant create
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
        $result = $mailManager->mail($module, $key, $toAddrs, $langcode, $params, null, true);
        if ($result === false
            || (array_key_exists('result', $result) && !$result['result'])
        ) {
            \Drupal::logger('cron_affinitygroup')->error("Error sending mail to " . $toAddrs);
        }
    }

    function createAllocUser($accessUserName, $firstName, $lastName, $userEmail)
    {
        try {
            $u = User::create();
            $x = $u->id();
            $u->set('status', 1);
            $u->setUsername($accessUserName);
            $u->setEmail($userEmail);

            $u->set('field_user_first_name', $firstName);
            $u->set('field_user_last_name', $lastName);

            $u->save();
            $y = $u->id();
            $this->collectCronLog("User created: $accessUserName - $userEmail ($y)", 'i');
        } catch (Exception $e) {
            $this->collectCronLog("Exception createUser $accessUser: " . $e->getMessage());
            $u = false;
        }
        return $u;
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
            $this->collectCronLog("...add member: " . $ag->get('title')->value . ': ' . $userDetails->get('field_user_last_name')->getString(), 'd');
        } else {
            $this->collectCronLog("...already  member: ".$ag->get('title')->value, 'd'); //todo remove
        }
    }

    /**
     * Reset user's cider list.
     */
    function updateUserCiderList($userDetails, $ciderRefnums)
    {
        $userDetails->set('field_cider_resources', null);
        foreach ($ciderRefnums as $refnum) {
            //$this->collectCronLog("CIDER setting" . $refnum, 'd');
            $userDetails->get('field_cider_resources')->appendItem($refnum);
        }
        $userDetails->save();
    }

    function allocApiGetResources($userName, $apiKey)
    {
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

        } catch (Exception $e) {
            $this->collectCronLog("Alloc resources api call $userName:" . $e->getMessage(), 'err');
        }
        return $responseJson;
    }
    function XXimportUserAllocationsInit()
    {
        $this->collectCronLog('Running importUserAllocations.', 'i');
        try {
            $requestUrl = 'https://allocations-api.access-ci.org/identity/profiles/v1/people';
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

        } catch (Exception $e) {
            $this->collectCronLog('Alloc people api call: ' . $e->getMessage(), 'err');
            return false;
        }

        // get rid of most of these after initial dev, here just for examining input
        $userCount = 0;
        $chunkCount = 0;
        $totalChunks = 0;
        $archivedCount = 0;
        $archivedCountN = 0;
        $suspendedCount = 0;
        $suspendedCountN = 0;
        $processedCount = 0;
        $usersWithAllocsCount = 0;
        $totalCiders = 0;
        $newUsers = 0;
        $newCCIds = 0;
        $userNames = [];

        $chunk = [];
        try {
            // chunk the list of user names
            foreach ($responseJson as $aUser) {
                $userCount++;
                if ($aUser['isSuspended']) {
                    $suspendedCount++;
                } else {
                    $suspendedCountN++;
                }
                if ($aUser['isArchived']) {
                    $archivedCount++;
                } else {
                    $archivedCountN++;
                }

                if ($aUser['isSuspended'] || $aUser['isArchived']) {
                    continue;
                }


                $processedCount++;

                // todo - temp limit for dev
                if ($processedCount > 85) {
                    break; //exit for loop
                }

                $userNames[] = $aUser['username'];

                $chunk[] = $aUser['username'];
                $chunkCount++;

                if ($chunkCount == self::CHUNK_LEN) {
                    // queue it TODO
                    //            $this->processChunk($chunk); // TEMP

                    // /kint($chunk);
                    $chunkCount = 0;
                    $chunk = [];
                    $totalChunks++;
                }
            }

            if ($chunkCount > 0 ) {
                $totalChunks++;
                //TODO qeueue leftoever
            }

        }catch (Exception $e) {
            $this->collectCronLog('importAllocations: ' . $e->getMessage(), 'err');
            //  return false;
        }

        //return "u: $userCount p: $processedCount chunkC: $chunkCount $totalChunks ";
        return $userNames;
    }

}
