<?php

namespace Drupal\access_affinitygroup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\views\Views;

/**
 * Provides a button to contact affinity group.
 *
 * @Block(
 *   id = "affinity_bottom_left",
 *   admin_label = "Affinity Bottom left section",
 * )
 */
class AffinityBottomLeft extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    $nid = $node ? $node->id() : 219;
    $query = \Drupal::entityQuery('eventseries')
      ->condition('status', 1)
      ->condition('field_affinity_group_node', $nid, '=')
      ->sort('created', 'DESC');
    $esid = $query->execute();
    foreach ($esid as $es) {
      $query = \Drupal::entityQuery('eventinstance')
        ->condition('status', 1)
        ->condition('eventseries_id', $es, '=')
        ->sort('created', 'DESC');
      $eiid = $query->execute();
    }
    if ($node) {
      // Add additional events added to the affinity group.
      $field_event = $node->get('field_affinity_events')->getValue();
      foreach ($field_event as $event) {
        $eiid[] = $event['target_id'];
      }
    }
    $event_list = [];
    foreach ($eiid as $ei) {
      $event = \Drupal::entityTypeManager()->getStorage('eventinstance')->load($ei);
      $event_status = $event->get('status')->getValue()[0]['value'];
      $event_date = $event->get('date')->getValue()[0]['value'];
      // Setup date in same format as today's date so I can get future events.
      $start_date = date_create($event_date);
      $edate = date_format($start_date, "Y-m-d");
      $date_now = date("Y-m-d");
      if ($event_status && $date_now <= $edate) {
        $series = $event->getEventSeries();
        $series_title = $series->get('title')->getValue()[0]['value'];
        $link = [
          '#type' => 'link',
          '#title' => $series_title,
          '#url' => Url::fromUri('internal:/events/' . $ei),
        ];
        $link_name = \Drupal::service('renderer')->render($link)->__toString();
        $event_list[$ei] = [
          'date' => $event_date,
          'title' => $link_name,
        ];
      }
    }
    // Sort events by date.
    usort($event_list, fn($a, $b) => $a['date'] <=> $b['date']);

    $output = '';
    if (!empty($eiid)) {
      $output = '<h3 class="my-3">Upcoming Events</h3>';
      foreach ($event_list as $e) {
        $start_date = date_create($e['date']);
        $edate = date_format($start_date, "n/d/Y g:i A T");
        $output .= '<p>[' . $edate . '] ' . $e['title'] . '</p>';
      }
    }
    return [
      ['#markup' => $output],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
    }
    else {
      return parent::getCacheTags();
    }
  }

  /**
   *
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
