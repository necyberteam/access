<?php

namespace Drupal\access_badges\Plugin;

use Drupal\user\Entity\User;

/**
 * Badges lookup.
 */
class BadgeTools {

  /**
   * Loaded User.
   */
  protected $currentUser;

  /**
   * User badges.
   */
  protected $userBadges;

  /**
   * Return skill level image.
   */
  public function getBadgeTid($badge_name) {
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', 'badges');
    $query->condition('name', $badge_name);
    $query->accessCheck(FALSE);
    $tids = $query->execute();
    // There should just be one term.
    $tid = implode('', $tids);
    return $tid;
  }

  /**
   * Return Users that have access-ci in name.
   */
  public function getAccessUsers() {
    // User entity lookup that were created 90 days or less ago and has
    // access-ci.org in their name.
    $query = \Drupal::entityQuery('user');
    $query->condition('created', strtotime('-90 days'), '>');
    $query->condition('name', '%access-ci.org%', 'LIKE');
    $query->accessCheck(FALSE);
    $users = $query->execute();
    return $users;
  }

  /**
   * Return Users that have a certain region/program.
   */
  public function getProgramUsers($program) {
    // User entity lookup that have a certain region/program.
    $query = \Drupal::entityQuery('user');
    $query->condition('field_region', $program);
    $query->accessCheck(FALSE);
    $users = $query->execute();
    return $users;
  }

  /**
   * Load the users badges.
   */
  public function loadUserBadges($user_id) {
    // Lookup user field 'field_user_badges'.
    $this->currentUser = User::load($user_id);
    $this->userBadges = $this->currentUser->get('field_user_badges')->getValue();
  }

  /**
   * Add badges to user.
   */
  public function addUserBadges($badge) {
    $this->userBadges[] = ['target_id' => $badge];
  }

  /**
   * Save user.
   */
  public function saveUserBadges() {
    $this->currentUser->set('field_user_badges', $this->userBadges);
    $this->currentUser->save();
  }


  /**
   * Return the users badges.
   */
  public function getUserBadges() {
    return $this->userBadges;
  }

  /**
   * Return Users that have the affinity group leader role.
   */
  public function getAgRoleUsers() {
    $query = \Drupal::entityQuery('user');
    $query->condition('roles', 'affinity_group_leader');
    $query->accessCheck(FALSE);
    $users = $query->execute();
    return $users;
  }

  /**
   * Check if user has badge, return boolean.
   */
  public function checkBadges($badge, $user) {
    $connection = \Drupal::database();
    $query = $connection->select('user__field_user_badges', 'ufub');
    $query->fields('ufub', ['field_user_badges_target_id']);
    $query->condition('ufub.entity_id', $user);
    $query->condition('ufub.field_user_badges_target_id', $badge);
    $result = $query->execute()->fetchField();

    return $result ? TRUE : FALSE;
  }

  /**
   * Set multiple users badge via the database.
   */
  public function setBadges($badge, $users) {
    $connection = \Drupal::database();

    // Remove all new to access badges to reset.
    $connection->delete('user__field_user_badges')
      ->condition('field_user_badges_target_id', $badge)
      ->execute();

    foreach ($users as $user) {
      // Need to look up delta for $user, if it exists increment by 1.
      $query = $connection->select('user__field_user_badges', 'ufub');
      $query->fields('ufub', ['delta']);
      $query->condition('ufub.entity_id', $user);
      $query->orderBy('delta', 'DESC');
      $delta = $query->execute()->fetchField();
      if ($delta >= 0) {
        $delta++;
      }
      if ($delta == NULL) {
        $delta = 0;
      }

      $connection->insert('user__field_user_badges')
        ->fields([
          'bundle' => 'user',
          'deleted' => 0,
          'entity_id' => $user,
          'revision_id' => $user,
          'langcode' => 'en',
          'delta' => $delta,
          'field_user_badges_target_id' => $badge,
        ])
        ->execute();
    }
  }

  /**
   * Set user badge via saving user.
   */
  public function setUserBadge($badge, $users) {
    foreach ($users as $user) {
      $uid = $user['target_id'];
      $badge_load = $this->loadUserBadges($uid);
      // Check if user has badge.
      $badge_check = $this->checkBadges($badge, [$uid]);
      if (!$badge_check) {
        $badges = $this->getUserBadges();
        // Set badges for user.
        $this->addUserBadges($badge);
        $this->saveUserBadges();
      }
    }
  }

}
