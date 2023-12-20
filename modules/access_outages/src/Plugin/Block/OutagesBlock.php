<?php

namespace Drupal\access_outages\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides an 'Outages' Block.
 *
 * @Block(
 *   id = "outages_block",
 *   admin_label = @Translation("Outages block"),
 *   category = @Translation("ACCESS"),
 * )
 */
class OutagesBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'outages_block',
      '#data' => [],
    ];
  }

}
