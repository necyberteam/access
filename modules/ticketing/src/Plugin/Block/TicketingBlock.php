<?php

namespace Drupal\ticketing\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Block with the ticketing_block theme.
 * Uses ticketing-block.html.twig which displays choices for type of ticketing,
 * and then goes to the corresponding Jira ticket page.
 *
 * @Block(
 *   id = "ticketing_block",
 *   admin_label = @Translation("Ticketing choices"),
 * )
 */
class TicketingBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'ticketing_block',
      '#data' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if ($user = \Drupal::currentUser()) {
      return Cache::mergeTags(parent::getCacheTags(), ['user:' . $user->id()]);
    }
    else {
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['user']);
  }

}
