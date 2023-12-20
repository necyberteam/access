<?php

namespace Drupal\access_affinitygroup\Plugin;

use Mailgun\Mailgun;

/**
 *
 */
class MailgunApi {

  private $mgClient;
  private $domain;

  /**
   *
   */
  public function __construct() {
    // to: fetch key from store.
    $apiKey = '';
    try {
      $this->mgClient = Mailgun::create($apiKey);
      // @todo
      $this->domain = '';
    }
    catch (\Exception $e) {
      echo 'Error 1: ' . $e->getMessage();
    }
  }

  /**
   *
   */
  public function createList($listAddress, $listName, &$msg) {
    try {
      $listDescription = 'ACCESS Affinity Group email list for members.';
      $accessLevel     = 'members';

      // Issue the call to the client.
      $response = $this->mgClient->mailingList()->create($listAddress, $listName, $listDescription, $accessLevel);
      $msg = $response->getMessage();
      return TRUE;
    }
    catch (\Exception $e) {
      $msg = $e->getMessage();
      return FALSE;
    }
  }

  /**
   * $users:  array of loaded users. (1 - 1000)
   */
  public function addUsers($listAddress, $users, &$msg, &$addedCount) {

    $userId = 0;
    $addedCount = 0;
    try {
      $newMembers = [];
      foreach ($users as $uu) {
        $userId = $uu->id();

        $newMembers[] = [
          'address' => $uu->getEmail(),
          'name' => $uu->getDisplayName(),
          'vars' => ['uid' => $userId, 'uname' => $uu->getAccountName()],
        ];
        $addedCount++;
      }

      $result = $this->mgClient->mailingList()->member()->createMultiple($listAddress, $newMembers);
      $msg = $result->getMessage();
      return TRUE;
    }
    catch (\Exception $e) {
      $msg = $e->getMessage() . " at " . $addedCount;
      return FALSE;
    }
  }

  /**
   * Normalize, truncate title of affinity group to be used as
   * email address for the list
   *
   * @todo truncated length will larger but test domain is really long
   * @todo confirm this is what is wanted. perhaps save a slug somewhere for this
   * type of thing.
   */
  public function makeListAddress($title) {
    // Replace whitespace a -.
    // also want to do ()/.
    $title = preg_replace('/\s+/', '-', strtolower(trim($title)));
    $title = substr($title, 0, 20);
    return ($title . '@' . $this->domain);
  }

}
