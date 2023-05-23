<?php

namespace Drupal\access_misc\Plugin\Util;

use Drupal\user\Entity\User;

/**
 * Notify people by roles.
 */
class NotifyRoles {

  /**
   * Send an email to all the users with the role roleName
   *  roleName: drupal role string
   *  title: email subject
   *  body: content of email
   */
  public function notifyRole($roleName, $subject, $body) {

    // Make destination list of emails of users with role.
    $userIds = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->condition('roles', $roleName)
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

    // Temp.
    \Drupal::logger('access_misc')->error("Sending NotifyRoles email to " . $toAddrs);
    \Drupal::logger('access_misc')->error("sending text is: " . $body);
    /*$params = [];
    $params['to'] = $toAddrs;
    // Send html body.
    //$params['message'] = ['<html><body>HI THERE</body></html>'];
    $params['body'][] = '<html><body>body message</body></html>';
    $params['subject'] = "subject foo";
    $params['title'] = "title foo";
     */
    $langcode = \Drupal::currentUser()->getPreferredLangcode();

    $params = [
      'headers' => [
        'Content-Type' => 'text/html; charset=UTF-8;',
        'Content-Transfer-Encoding' => '8Bit',
      ],
      'id' => 'notify',
      'reply-to' => NULL,
      'subject' => $subject,
      'langcode' => $langcode,
      // The body will be rendered in example_mail().
      'body' => [$body],
    ];

    $mailManager = \Drupal::service('plugin.manager.mail');

    // Temp.
    \Drupal::logger('access_misc')->error("about to send");

    $result = $mailManager->mail('access_misc', 'notify', $toAddrs, $langcode, $params, NULL, TRUE);

    if ($result === FALSE || (array_key_exists('result', $result) && !$result['result'])) {
      \Drupal::logger('access_misc')->error("Error sending NotifyRoles email to " . $toAddrs);
      \Drupal::logger('access_misc')->error($result['result']);
    }
  }

}
