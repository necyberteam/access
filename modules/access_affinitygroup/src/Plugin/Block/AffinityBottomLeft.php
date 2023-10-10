<?php

namespace Drupal\access_affinitygroup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\views\Views;
use Drupal\Core\Cache\Cache;

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
    // Adding a default for layout page.
    $affinity_group_tax = '607';
    if ($node) {
      $field_affinity_group = $node->get('field_affinity_group')->getValue();
      $affinity_group_tax = $field_affinity_group[0]['target_id'];
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
