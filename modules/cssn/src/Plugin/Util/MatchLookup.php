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
  public function __construct($match_fields, $match_user_id, $public = FALSE) {
    // If not public, add engagements authored by User.
    if (!$public) {
      $query = \Drupal::database()->select('node_field_data', 'nfd');
      $query->fields('nfd', ['nid']);
      $query->condition('nfd.type', 'match_engagement');
      $query->condition('nfd.uid', $match_user_id);
      $result = $query->execute()->fetchAll();
      $nids = array_column($result, 'nid');
      $this->matches['author'] = [
        'name' => 'Author',
        'nodes' => \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids),
      ];
    }
    foreach ($match_fields as $match_field_key => $match_field) {
      $this->runQuery($match_field, $match_field_key, $match_user_id);
    }
    $this->gatherMatches();
  }

  /**
   * Function to Run entity query by type.
   */
  public function runQuery($match_field_name, $match_field, $match_user_id) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'match_engagement')
      ->condition($match_field, $match_user_id)
      ->accessCheck(FALSE)
      ->execute();
    if ($query != NULL) {
      $this->matches[$match_field] = [
        'name' => $match_field_name,
        'nodes' => \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($query),
      ];
    }
  }

  /**
   * Function to lookup nodes and sort array.
   */
  public function gatherMatches() {
    $matches = $this->matches;
    $match_array = [];
    if ($matches == NULL) {
      return;
    }
    foreach ($matches as $match) {
      foreach ($match['nodes'] as $node) {
        $title = $node->getTitle();
        $nid = $node->id();
        $match_name = $match['name'];
        $field_status = $node->get('field_status')->getValue();
        $field_status = !empty($field_status) ? $field_status : '';
        $match_array[$nid] = [
          'status' => $field_status,
          'name' => $match_name,
          'title' => $title,
          'nid' => $nid,
        ];
      }
    }
    $this->matches_sorted = $match_array;
  }

  /**
   * Function to sort by status.
   */
  public function sortStatusMatches() {
    $matches = $this->matches_sorted;
    $draft = $this->arrayPickSort($matches, 'draft');
    $in_review = $this->arrayPickSort($matches, 'in_review');
    $accepted = $this->arrayPickSort($matches, 'accepted');
    $recruiting = $this->arrayPickSort($matches, 'recruiting');
    $reviewing = $this->arrayPickSort($matches, 'reviewing_applicants');
    $in_progress = $this->arrayPickSort($matches, 'in_progress');
    $finishing = $this->arrayPickSort($matches, 'finishing_up');
    $completed = $this->arrayPickSort($matches, 'complete');
    $on_hold = $this->arrayPickSort($matches, 'on_hold');
    $halted = $this->arrayPickSort($matches, 'halted');
    // Combine all of the arrays.
    $matches_sorted = $draft + $in_review + $accepted + $recruiting + $reviewing + $in_progress + $finishing + $completed + $on_hold + $halted;
    $this->matches_sorted = $matches_sorted;
  }

  /**
   * Function to pick out a status into an array and sort by title.
   */
  public function arrayPickSort($array, $sortby) {
    $sorted = [];
    if ($array == NULL) {
      return;
    }
    foreach ($array as $key => $value) {
      if ($value['status'] && $value['status'][0]['value'] == $sortby) {
        $sorted[$key] = $value;
      }
    }
    uasort($sorted, function ($a, $b) {
      return strnatcmp($a['title'], $b['title']);
    });
    return $sorted;
  }

  /**
   * Function to return styled list.
   */
  public function getMatchList() {
    $n = 1;
    $match_link = '';
    if ($this->matches_sorted == NULL) {
      return;
    }
    foreach ($this->matches_sorted as $match) {
      $stripe_class = $n % 2 == 0 ? 'bg-light bg-light-teal' : '';
      $title = $match['title'];
      $nid = $match['nid'];
      $match_status = $match['status'];
      $match_translated_status = [
        'draft' => 'Draft',
        'in_review' => 'In Review',
        'accepted' => 'Accepted',
        'recruiting' => 'Recruiting',
        'reviewing_applicants' => 'Reviewing Applicants',
        'in_progress' => 'In-Progress',
        'finishing_up' => 'Finishing Up',
        'complete' => 'Complete',
        'on_hold' => 'On Hold',
        'halted' => 'Halted',
      ];
      if ($match_status) {
        $set_status = $match_status[0]['value'];
        $match_status = $match_translated_status[$set_status];
      }
      $match_name = $match['name'];
      if (($match_status == 'Recruiting' && $match_name == 'Interested') || $match_name != 'Interested') {
        $lowercase = lcfirst($match_name);
        $first_letter = substr($lowercase, 0, 1);
        $match_name = "<div data-tippy-content='$match_name'>
          <i class='text-dark text-dark-teal text-2xl fa-solid fa-circle-$first_letter h2'></i>
        </div>";
        $match_link .= "<li class='d-flex flex p-3 $stripe_class'>
          <div class='text-truncate' style='width: 400px;'>
            <a href='/node/$nid'>$title</a>
          </div>
          <div class='font-weight-bold ms-5'>
            $match_name
          </div>
          <div class='ms-2'>
            $match_status
          </div>
        </li>";
        $n++;
      }
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
