<?php

namespace Drupal\access_misc\Plugin\Util;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Notify people by roles.
 */
class UserTools {

  /**
   * Run Entity Query.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get emails by role and uids.
   *
   * @param array $roleName
   *   Role id.
   * @param array $uids
   *   User id.
   */
  public function getEmails(array $roleName, array $uids) {
    $users_ids = $uids;
    foreach ($roleName as $role) {
      $userIds = $this->entityTypeManager->getStorage('user')->getQuery()
        ->condition('status', 1)
        ->condition('roles', $role)
        ->accessCheck(FALSE)
        ->execute();
      $userIds = array_map('intval', $userIds);
      $users_ids = array_merge($users_ids, $userIds);
    }
    $users_ids = array_unique($users_ids);
    $users = $this->entityTypeManager->getStorage('user')->loadMultiple($users_ids);
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
    return $toAddrs;
  }

}
