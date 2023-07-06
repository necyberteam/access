<?php

namespace Drupal\access_misc\Plugin\Util;

use Drupal\user\Entity\User;

/**
 * Notify people by roles.
 */
class NotifyRoles {

  /**
   * Send an email to all the users with the role roleName
   *  roleName: drupal role - string
   *  subject: email subject
   *  body: content of email
   */
  public function notifyRole($roleName, $subject, $body) {

    // Make destination list of emails of users with role.
    $userIds = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->condition('roles', $roleName)
      ->accessCheck(FALSE)
      ->execute();
    $users = User::loadMultiple($userIds);
    $toAddrs = '';
    $userCount = count($users);
    if ($userCount == 0) {
      return;
    }

    $iterate = 0;
    foreach ($users as $user) {
      $iterate++;
      $toAddrs .= $user->get('mail')->getString();
      if ($userCount != $iterate) {
        $toAddrs .= ",";
      }
    }

    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $params = [
      'id' => 'notify_role',
      'reply-to' => NULL,
      'subject' => $subject,
      'langcode' => $langcode,
      'body' => $body,
    ];

    $mailManager = \Drupal::service('plugin.manager.mail');

    $result = $mailManager->mail('access_misc', 'notify_role', $toAddrs, $langcode, $params, NULL, TRUE);

    if ($result === FALSE || (array_key_exists('result', $result) && !$result['result'])) {
      \Drupal::logger('access_misc')->error("Error sending NotifyRoles email to " . $toAddrs);
    }
  }
}
