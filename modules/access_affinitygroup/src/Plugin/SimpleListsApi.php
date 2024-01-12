<?php

namespace Drupal\access_affinitygroup\Plugin;

use Drupal\access_misc\Plugin\Util\NotifyRoles;

/**
 *
 */
class SimpleListsApi {

  private $domain;
  private $apiKey;
  const  MAX_ADDRESS_LEN = 60;

  /**
   *
   */
  public function __construct() {
    try {
      $errmsg = NULL;
      $this->apiKey = '';

      $this->domain = 'list.connectci.org';
      $this->apiKey = \Drupal::service('key.repository')->getKey('simplelists')->getKeyValue();

      if (empty($this->apiKey)) {
        $errmsg = 'Simplelists key missing.';
      }
      $this->apiKey .= ':';
    }
    catch (\Exception $e) {
      $errmsg = 'Simplelists error: ' . $e->getMessage();
    }
    if ($errmsg <> NULL) {
      \Drupal::logger('access_affinitygroup')->error($errmsg);
      $nr = new NotifyRoles();
      $nr->notifyRole('site_developer', 'Simplelists problem', $errmsg);
      \Drupal::messenger()->addMessage($errmsg);
    }
  }

  /**
   *
   */
  public function getDomain() {
    return $this->domain;
  }

  /**
   * Returns curl obj
   *  $op: POST/GET/PUT/DELETE
   */
  private function makeCurl($op, $urlsub, $params = '') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.simplelists.com/api/2/' . $urlsub);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $op);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, $this->apiKey);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    return $ch;
  }

  /**
   * Create the list slug@domain
   *  Return boolean status. Fills $msg.
   */
  public function createList($listSlug, &$msg) {
    try {
      $params = 'name=' . $listSlug . '&subject_prefix=ACCESS ' . $listSlug . ': ';
      $ch = $this->makeCurl('POST', 'lists/', $params);
      $response = curl_exec($ch);
      curl_close($ch);

      $deResponse = json_decode($response, TRUE);
      if (isset($deResponse['is_error']) && $deResponse["is_error"]) {
        $msg = $deResponse["message"];
        return FALSE;
      }

      $msg = "List created.";
      return TRUE;
    }
    catch (\Exception $e) {
      $msg = $e->getMessage();
      return FALSE;
    }
  }

  /**
   * Add user, and add to listName if not null. returns new id or null.
   * $listName is slug.
   */
  public function addUser($uid, $userEmail, $firstName, $lastName, $listName, &$msg) {

    try {
      $userEmail = urlencode($userEmail);
      $firstName = urlencode(substr($firstName, 0, 30));
      $lastName = urlencode(substr($lastName, 0, 30));

      $params = "emails=$userEmail&firstname=$firstName&surname=$lastName&notes=uid$uid";
      if (!empty($listName)) {
        $params .= "&lists=$listName";
      }
      $ch = $this->makeCurl('POST', 'contacts/', $params);
      $response = curl_exec($ch);
      curl_close($ch);

      $deResponse = json_decode($response, TRUE);
      if (isset($deResponse['is_error']) && $deResponse['is_error']) {
        $msg = $deResponse['message'];
        return NULL;
      }
      $msg = 'User added.';
      return $deResponse['id'];
    }
    catch (\Exception $e) {
      $msg = $e->getMessage();
      return NULL;
    }
  }

  /**
   * Return simplelists contact id string or else NULL if not found.
   */
  public function getUserIdFromEmail($userEmail, &$msg) {
    try {
      $urlEnd = 'emails/' . urlencode($userEmail) . '/';
      $ch = $this->makeCurl('GET', $urlEnd);
      $response = curl_exec($ch);
      curl_close($ch);

      $deResponse = json_decode($response, TRUE);
      if (isset($deResponse['is_error']) && $deResponse['is_error']) {
        $msg = $deResponse['message'];
        return NULL;
      }

      return $deResponse['contact'];
    }
    catch (\Exception $e) {
      $msg = $e->getMessage();
      return NULL;
    }
  }

  /**
   * With a user email, get listnames for that user.
   * Set listNames if user found, and return simplelist user contact id.
   */
  public function getUserListNames($userEmail, &$listNames, &$msg) {

    try {
      $listNames = [];
      $slContactId = $this->getUserIdFromEmail($userEmail, $msg);
      if ($slContactId != NULL) {
        // Have the user id, so now find what lists they already have.
        $ch = $this->makeCurl('GET', 'contacts/' . $slContactId . '/');
        $response = curl_exec($ch);
        curl_close($ch);

        $deResponse = json_decode($response, TRUE);
        // Sl  found the email object but not the use contact, so call that a no.
        if (isset($deResponse['is_error']) && $deResponse['is_error']) {
          $msg = $deResponse['message'];
          return NULL;
        }

        $listArray = $deResponse['lists'];
        foreach ($listArray as $listObj) {
          $listNames[] = $listObj['list'];
        }
      }
    }
    catch (\Exception $e) {
      $msg = $e->getMessage();
      return NULL;
    }
    return $slContactId;
  }

  /**
   * Return list membership Id for a user/list relation
   * This is different from the user id.
   * Or NULL if not found
   *
   * $userEmail: user's email address
   * $listName: list address slug.
   */
  public function getListMemberId($userEmail, $listName, &$msg) {
    try {
      $slContactId = $this->getUserIdFromEmail($userEmail, $msg);
      if ($slContactId != NULL) {
        $ch = $this->makeCurl('GET', 'contacts/' . $slContactId . '/');
        $response = curl_exec($ch);
        curl_close($ch);
        $deResponse = json_decode($response, TRUE);
        if (isset($deResponse['is_error']) && $deResponse['is_error']) {
          $msg = $deResponse['message'];
          return NULL;
        }
        $listArray = $deResponse['lists'];

        foreach ($listArray as $listObj) {
          if ($listObj['list'] == $listName) {
            return $listObj['id'];
          }
        }
      }
    }
    catch (\Exception $e) {
      $msg = $e->getMessage();
      return NULL;
    }
    return NULL;
  }

  /**
   * UpdateUserToList.
   *
   * @param [type] $simplelistsId:
   *   the user's contact id in simplelists account.
   * @param [type] $listName:
   *   the slug part of the email list address.
   * @param [type] $msg
   *   set with message.
   *
   * @return boolean status
   */
  public function updateUserToList($simplelistsId, $listName, &$msg) {

    try {
      $params = "contact=$simplelistsId&list=$listName";
      $ch = $this->makeCurl('POST', 'membership/', $params);
      $response = curl_exec($ch);
      curl_close($ch);

      $deResponse = json_decode($response, TRUE);
      if (isset($deResponse['is_error']) && $deResponse['is_error']) {
        $msg = $deResponse['message'];
        return FALSE;
      }
      $msg = 'User added to list.';
      return TRUE;
    }
    catch (\Exception $e) {
      $msg = $e->getMessage();
      return TRUE;
    }
  }

  /**
   *
   */
  public function deleteList($listName, &$msg) {
    $msg = '';
    $urlEnd = "lists/$listName/";
    $ch = $this->makeCurl('DELETE', $urlEnd);
    $response = curl_exec($ch);
    curl_close($ch);
    $deResponse = json_decode($response, TRUE);
    if (isset($deResponse['is_error']) && $deResponse['is_error']) {
      $msg = $deResponse['message'];
      return FALSE;
    }
    return TRUE;
  }

  /**
   *
   */
  public function removeUserFromList($userEmail, $listName, &$msg) {

    try {
      $listMemberId = $this->getListMemberId($userEmail, $listName, $msg);
      if ($listMemberId != NULL) {
        $urlEnd = "membership/$listMemberId/";
        $ch = $this->makeCurl('DELETE', $urlEnd);
        curl_exec($ch);
        curl_close($ch);
        return TRUE;
      }
    }
    catch (\Exception $e) {
      $msg = $e->getMessage();
      return FALSE;
    }
  }

  /**
   * Make mailgun email list address from slug (short name)
   * email address for the list.
   * Return full email address, and replace slug with (potentially) fixed-up slug.
   */
  public function makeListAddress(&$slug) {
    // Replace whitespace a -.
    $slug = preg_replace('/\s+/', '-', strtolower(trim($slug)));
    $maxSlugLen = self::MAX_ADDRESS_LEN - strlen($this->domain);
    $slug = substr($slug, 0, $maxSlugLen - 1);
    return ($slug . '@' . $this->domain);
  }

}
