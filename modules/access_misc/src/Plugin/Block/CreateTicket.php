<?php

namespace Drupal\access_misc\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Create Ticket Button' Block.
 *
 * @Block(
 *   id = "create_ticket_button",
 *   admin_label = "Create Ticket button",
 * )
 */
class CreateTicket extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $link = [
      '#type' => 'inline_template',
      '#template' => '<a class="btn btn-primary" href="https://cyberteamportal.atlassian.net/servicedesk/customer/portal?customfield_{{ custom_field_id }}={{ email }}&customerfield_10027={{ accessid }}">Create a Ticket</a>',
      '#context' => [
        'email' => \Drupal::currentUser()->getEmail(),
        'custom_field_id' => '10026',
        'accessid' => \Drupal::currentUser()->getAccountName(),
      ],
    ];

    return $link;
  }

  /**
   * Return no cache.
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
