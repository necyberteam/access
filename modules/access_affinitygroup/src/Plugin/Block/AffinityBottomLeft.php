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
    $output = '';

    $node = \Drupal::routeMatch()->getParameter('node');
    $nid = $node ? $node->id() : 219;
    $query = \Drupal::entityQuery('eventseries')
      ->condition('status', 1)
      ->condition('field_affinity_group_node', $nid, '=')
      ->accessCheck(TRUE)
      ->sort('created', 'DESC');
    $esid = $query->execute();
    foreach ($esid as $es) {
      $query = \Drupal::entityQuery('eventinstance')
        ->condition('status', 1)
        ->condition('eventseries_id', $es, '=')
        ->accessCheck(TRUE)
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
    $view = Views::getView('affinity_group_recurring_events');
    $view->setDisplay('block_1');
    $view->setArguments([$affinity_group_tax]);
    $view->execute();
    $rendered = $view->render();
    if ($rendered) {
      $view_output = \Drupal::service('renderer')->render($rendered)->__toString();
      $strip = preg_replace('/\s+/', '', $view_output);
      // Don't show empty view.
      if ($strip != '<divclass="bg-md-tealp-4text-white-erfont-boldmb-6mt-4viewview-affinity-group-recurring-eventsview-id-affinity_group_recurring_eventsview-display-id-block_1"></div>') {
        $output .= $view_output;
      }
    }

    /**
    * Grab node id.
    */
    $node = \Drupal::routeMatch()->getParameter('node');

    /**
    * Adding a default for layout page.
    */
    $nid = $node ? $node->id() : 291;

    /**
    * Load Announcement view.
    */
    $announcement_view = Views::getView('access_news');
    $announcement_view->setDisplay('block_2');
    $announcement_view->setArguments([$nid]);
    $announcement_view->execute();
    $announcement_list = $announcement_view->render();
    $output .= \Drupal::service('renderer')->render($announcement_list);

    $output = '';

    if (!empty($eiid)) {
      $event_list = [];
      foreach ($eiid as $ei) {
        $event = \Drupal::entityTypeManager()->getStorage('eventinstance')->load($ei);
        $date_now = date("Y-m-d");
        if ($event) {
          $event_status = $event->get('status')->getValue()[0]['value'];
          $event_date = $event->get('date')->getValue()[0]['value'];
          // Setup date in same format as today's date so I can get future events.
          $start_date = date_create($event_date);
          $edate = date_format($start_date, "Y-m-d");
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
        else {
          $single_event = \Drupal::entityTypeManager()->getStorage('eventseries')->load($ei);
          $single_event_status = $single_event->get('status')->getValue()[0]['value'];
          $single_event_date = $single_event->getSeriesStart()->__toString();
          $start_date = date_create($single_event_date);
          $single_event_date = date_format($start_date, "Y-m-d");
          if ($single_event_status && $date_now <= $single_event_date) {
            $series = $single_event;
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
      }
      // Sort events by date.
      usort($event_list, fn($a, $b) => $a['date'] <=> $b['date']);

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
