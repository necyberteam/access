<?php

namespace Drupal\access_affinitygroup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a button to view coordinator documentation.
 *
 * @Block(
 *   id = "affinity_coordinator_documentation",
 *   admin_label = "Affinity Coordinator Documentation",
 * )
 */
class AffinityCoordinatorDocumentation extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    $current_user = \Drupal::currentUser();
    $roles = $current_user->getRoles();
    // Adding a default for layout page.
    $nid = $node ? $node->id() : 291;
    $field_coordinator = $node->get('field_coordinator')->getValue();
    $coordinator = [];
    foreach ($field_coordinator as $key => $value) {
      $coordinator[] = $value['target_id'];
    }
    $contact = [
      ['#markup' => ''],
    ];
    if (in_array('administrator', $roles) || in_array($current_user->id(), $coordinator)) {
      $contact['string'] = [
        '#type' => 'inline_template',
        '#template' => '<a class="btn btn-outline-dark cursor-default mx-0 my-2" target="_blank" href="{{ link }}">{{ coordinator_text }}</a>',
        '#context' => [
          'coordinator_text' => $this->t('Coordinator Documentation'),
          'link' => 'https://access-ci.atlassian.net/wiki/spaces/ACCESSdocumentation/pages/467112960/Affinity+Group+Coordinator+Notes',
        ],
      ];
    }

    return $contact;
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
