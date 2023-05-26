<?php

namespace Drupal\access_affinitygroup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a button to contact affinity group.
 *
 * @Block(
 *   id = "affinity_contact_group",
 *   admin_label = "Affinity Contact Group",
 * )
 */
class AffinityContactGroup extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $nid = \Drupal::routeMatch()->getParameter('node')->id();
    $contact['string'] = [
      '#type' => 'inline_template',
      '#template' => '<a class="bg-secondary btn btn-outline-dark cursor-default text-white m-2" href="/form/affinity-group-contact?nid={{ nid }}">{{ contact_text }}</a>',
      '#context' => [
        'contact_text' => $this->t('Email Affinity Group'),
        'nid' => $nid,
      ],
    ];

    return $contact;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      return Cache::mergeTags(parent::getCacheTags(), array('node:' . $node->id()));
    } else {
      return parent::getCacheTags();
    }
  }

  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), array('route'));
  }

}
