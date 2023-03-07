<?php

namespace Drupal\cssn\Plugin\Util;

/**
 * Lookup connected Match+ nodes.
 *
 * @MatchLookup(
 *   id = "match_lookup",
 *   title = @Translation("Match Lookup"),
 *   description = @Translation("Lookup Users match+ connections for community
 *   persona.")
 * )
 */
class MatchLookup {
  /**
   * Store matching nodes.
   * $var array
   */
  private $matches;

  /**
   * Array of sorted matches.
   * $var array
   */
  private $matches_sorted;


  /**
   * Function to return matching nodes.
   */
  public function __construct($match_fields, $match_user_id) {
    foreach ($match_fields as $match_field_key => $match_field) {
      $this->runQuery($match_field, $match_field_key, $match_user_id);
    }
  }

  /**
   * Function to Run entity query by type.
   */
  public function runQuery($match_field_name, $match_field, $match_user_id) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'match_engagement')
      ->condition($match_field, $match_user_id)
      ->execute();
    if ($query != NULL) {
      $this->matches[$match_field] = [
        'name' => $match_field_name,
        'nodes' => \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($query)
      ];
    }
  }

  /**
   * Function to lookup nodes and sort array.
   */
  public function sortMatches() {
    $matches = $this->matches;
    $match_array = [];
    foreach ($matches as $match) {
      foreach ($match['nodes'] as $node) {
        $title = $node->getTitle();
        $nid = $node->id();
        $match_name = $match['name'];
        $match_array[$nid] = [
          'name' => $match_name,
          'title' => $title,
          'nid' => $nid,
        ];
      }
    }
    asort($match_array);
    $this->matches_sorted = $match_array;
  }

  /**
   * Function to return styled list.
   */
  public function getMatchList() {
    $this->sortMatches();
    $n = 1;
    $match_link = '';
    foreach ($this->matches_sorted as $match) {
      $stripe_class = $n % 2 == 0 ? 'bg-light' : '';
      $title = $match['title'];
      $nid = $match['nid'];
      $match_name = $match['name'];
      $match_link .= "<li class='d-flex justify-content-between p-3 $stripe_class'><div class='text-truncate' style='max-width: 600px;'><a href='/node/$nid'>$title</a></div><div class='font-weight-bold'>$match_name</div></li>";
      $n++;
    }
    return $match_link;
  }

  /**
   * Function to return matching nodes.
   */
  public function getMatches() {
    return $this->matches;
  }
}
