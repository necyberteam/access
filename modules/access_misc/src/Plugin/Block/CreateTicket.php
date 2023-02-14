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
    $username = \Drupal::currentUser()->getAccountName();
    $username = explode('@', $username);
    $username = $username[0];
    $config = \Drupal::configFactory()->getEditable('access_misc.settings');
    $email_var = $config->get('email_var', 'customfield_10026');
    $access_id = $config->get('access_id_var', 'customfield_10027');
    $link = [
      '#type' => 'inline_template',
      '#template' => '<a class="btn btn-primary" href="https://cyberteamportal.atlassian.net/servicedesk/customer/portal?{{ custom_emaiil_var }}={{ email }}&{{ access_id }}={{ accessid }}">Create a Ticket</a>',
      '#context' => [
        'email' => \Drupal::currentUser()->getEmail(),
        'custom_emaiil_var' => $email_var,
        'access_id' => $access_id,
        'accessid' => $username,
      ],
    ];

    return $link;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // Vary caching of this block per user.
    return Cache::mergeContexts(parent::getCacheContexts(), ['user']);
  }

}
