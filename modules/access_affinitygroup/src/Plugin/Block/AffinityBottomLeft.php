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
    $output = \Drupal::service('renderer')->render($rendered);

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
