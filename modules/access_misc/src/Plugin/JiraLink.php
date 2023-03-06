<?php

namespace Drupal\access_misc\Plugin;

/**
 * Environment icon to be used on header title.
 *
 * @JiraLink(
 *   id = "jira_link",
 *   title = @Translation("Create Jira Link"),
 *   description = @Translation("Create Jira link")
 * )
 */
class JiraLink {

  /**
   * Store link.
   *
   * @var string
   */
  private $link;


  /**
   * Query block and pull bid via uuid.
   */
  public function __construct($link, $text) {
    if (\Drupal::currentUser()->isAuthenticated()) {
      $username = \Drupal::currentUser()->getAccountName();
      $username = explode('@', $username);
      $username = $username[0];
      $config = \Drupal::configFactory()->getEditable('access_misc.settings');
      $token_data = [
        'user' => \Drupal::currentUser(),
      ];
      $token_service = \Drupal::token();
      $token_options = [
        'clear' => TRUE,
      ];
      $misc_text = $token_service->replace($config->get('misc_var'), $token_data, $token_options);
      $misc_var = $config->get('misc_var') !== '' ? '&' . $misc_text : '';
      $access_id = $config->get('access_id_var');
      $link = [
        '#type' => 'inline_template',
        '#template' => '<a class="btn btn-primary" href="{{ link }}?{{ access_id }}={{ accessid }}{{ custom_misc_var }}">{{ text }}</a>',
        '#context' => [
          'link' => $link,
          'text' => $text,
          'custom_misc_var' => $misc_var,
          'access_id' => $access_id,
          'accessid' => $username,
        ],
        '#attached' => [
          'library' => [
            'access_misc/misc_library',
          ],
        ],
      ];
    } else {
      $link = [
        '#type' => 'inline_template',
        '#template' => '<a class="btn btn-primary" href="/user/login">{{ text }}</a>',
        '#context' => [
          'text' => t('Login to ') . $text,
        ],
      ];
    }
    $this->link = $link;
  }

  /**
   * Get link.
   */
  public function getLink() {
    return $this->link;
  }

}
