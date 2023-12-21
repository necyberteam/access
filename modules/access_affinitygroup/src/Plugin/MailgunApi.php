<?php

namespace Drupal\access_affinitygroup\Plugin;

use Drupal\access_misc\Plugin\Util\NotifyRoles;
use Mailgun\Mailgun;

/**
 * Mailgun functions for creating, deleting, and adding to mailing lists.
 */
class MailgunApi {

  private $mgClient;
  private $domain;
  const  MAX_ADDRESS_LEN = 60;

  /**
   *
   */
  public function __construct() {

    try {
      $apiKey = \Drupal::service('key.repository')->getKey('mailgun')->getKeyValue();
      if (empty($apiKey)) {
        $errmsg = 'Mailgun key missing.';
        \Drupal::logger('access_affinitygroup')->error($errmsg);
        $nr = new NotifyRoles();
        $nr->notifyRole('site_developer', 'Mailgun problem', $errmsg);
        \Drupal::messenger()->addMessage($errmsg);
        return;
      }
      // @todo in production domain does not need to be secret. This is just for testing.
      // $this->domain = 'support.access-ci.org';
      $this->domain = \Drupal::service('key.repository')->getKey('mailgun_domain')->getKeyValue();
      if (empty($this->domain)) {
        $errmsg = 'Mailgun  domain key missing.';
        \Drupal::logger('access_affinitygroup')->error($errmsg);
        \Drupal::messenger()->addMessage($errmsg);
        return;
      }

      $apiKey = urlencode(trim($apiKey));
      $this->mgClient = Mailgun::create($apiKey);
    }
    catch (\Exception $e) {
      $errmsg = 'Mailgun error: ' . $e->getMessage();
      \Drupal::logger('access_affinitygroup')->error($errmsg);
      $nr = new NotifyRoles();
      $nr->notifyRole('site_developer', 'Mailgun problem', $errmsg);
      \Drupal::messenger()->addMessage($errmsg);
    }
  }

  /**
   * Create List:
   * listAddress (the email),
   * listName: users will see this as a from in email readers
   *
   * Return &msg + boolean success.
   */
  public function createList($listAddress, $listName, &$msg) {
    try {
      $listDescription = 'ACCESS Affinity Group email list for members.';
      $accessLevel = 'members';
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
   * DeleteList:  listAddress, return &msg, boolean success.
   */
  public function deleteList($listAddress, &$msg) {
    try {
      $response = $this->mgClient->mailingList()->delete($listAddress);
      $msg = $response->getMessage();
      return TRUE;
    }
    catch (\Exception $e) {
      $msg = $e->getMessage();
      return FALSE;
    }
  }

  /**
   * AddUsers: to listAddress, return &msg, addedCount + boolean success
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
   * RemoveUser: userEmail from listAddress, return &msg  + boolean success.
   */
  public function removeUser($listAddress, $userEmail, &$msg) {
    try {
      $result = $this->mgClient->mailingList()->member()->delete($listAddress, $userEmail);
      $msg = $result->getMessage();
      return TRUE;
    }
    catch (\Exception $e) {
      $msg = $e->getMessage();
      return FALSE;
    }
  }

  /**
   * Make mailgun email list address from slug (short name)
   * email address for the list
   */
  public function makeListAddress($slug) {
    // Replace whitespace a -.
    $slug = preg_replace('/\s+/', '-', strtolower(trim($slug)));
    $maxSlugLen = self::MAX_ADDRESS_LEN - strlen($this->domain);
    $slug = substr($slug, 0, $maxSlugLen - 1);
    return ($slug . '@' . $this->domain);
  }

}
