<?php

namespace Drupal\access_affinitygroup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\views\Views;
use Drupal\Core\Cache\Cache;

/**
 * Displays Resources for Affinity Group in layout.
 *
 * @Block(
 *   id = "resources_for_affinity_group",
 *   admin_label = "Resources for Affinity Group view",
 * )
 */
class ResourcesForAffinityGroup extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    // Adding a default for layout page.
    $ciLinks = '474';
    if ($node) {
      $ciLinks = '';
      // Load Field field_resources_entity_reference.
      $field_resources_entity_reference = $node->get('field_resources_entity_reference')->getValue();
      foreach ($field_resources_entity_reference as $key => $value) {
        $ciLinks .= $value['target_id'] . ',';
      }
      $ciLinks = rtrim($ciLinks, ',');
    }
    // Load CI Link view.
    $ci_links_view = Views::getView('resources');
    $ci_links_view->setDisplay('block_2');
    $ci_links_view->setArguments([$ciLinks]);
    $ci_links_view->execute();
    $ci_link_table = $ci_links_view->render();
    $rendered = \Drupal::service('renderer')->render($ci_link_table);

    // Grab node id.
    $node = \Drupal::routeMatch()->getParameter('node');
    // Adding a default for layout page.
    $nid = $node ? $node->id() : 291;
    // Load Announcement view.
    $announcement_view = Views::getView('access_news');
    $announcement_view->setDisplay('block_2');
    $announcement_view->setArguments([$nid]);
    $announcement_view->execute();
    $announcement_list = $announcement_view->render();
    $rendered .= \Drupal::service('renderer')->render($announcement_list);

    return [
      ['#markup' => $rendered]
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
