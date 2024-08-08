<?php

namespace Drupal\access_badges\Plugin;

/**
 * Badges lookup.
 */
class BadgeTid {

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
    $new_to_access = implode('', $tids);
    return $new_to_access;
  }

}
