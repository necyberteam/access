<?php

namespace Drupal\access_misc\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Create Ticket Button' Block.
 *
 * @Block(
 *   id = "create_security_ticket_button",
 *   admin_label = "Create Security Ticket button",
 * )
 */
class CreateSecurityTicket extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $username = \Drupal::currentUser()->getAccountName();
    $username = explode('@', $username);
    $username = $username[0];
    $config = \Drupal::configFactory()->getEditable('access_misc.settings');
    $misc_var = $config->get('misc_var') !== '' ? '&' . $config->get('misc_var') : '';
    $access_id = $config->get('access_id_var');
    $link = [
      '#type' => 'inline_template',
      '#template' => '<a class="btn btn-primary" href="https://access-ci.atlassian.net/servicedesk/customer/portal/3/create/26?{{ access_id }}={{ accessid }}{{ custom_misc_var }}">Create a Security Ticket</a>',
      '#context' => [
        'custom_misc_var' => $misc_var,
        'access_id' => $access_id,
        'accessid' => $username,
      ],
    ];

    return $link;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
