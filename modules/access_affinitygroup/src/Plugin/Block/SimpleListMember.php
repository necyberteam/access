<?php

namespace Drupal\access_affinitygroup\Plugin\Block;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Block for SimpleList user status.
 *
 * @Block(
 *   id = "simple_list_member",
 *   admin_label = "Simple List Member block",
 * )
 */
class SimpleListMember extends BlockBase implements
  ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container pulled in.
   * @param array $configuration
   *   Configuration added.
   * @param string $plugin_id
   *   Plugin_id added.
   * @param mixed $plugin_definition
   *   Plugin_definition added.
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {

    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Construct object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }


  /**
   * {@inheritdoc}
   */
  public function build() {
    $simpleListsApi = new \Drupal\access_affinitygroup\Plugin\SimpleListsApi();
    $msg = "";
    // Load current node.
    $node = \Drupal::routeMatch()->getParameter('node');
    $simple_list_enabled = $node->get('field_use_ext_email_list')->value;

    if (!$simple_list_enabled) {
      return [];
    }

    $group_slug = $node->get('field_group_slug')->value;
    // Get current user email.
    $current_user = \Drupal::currentUser();
    $current_user_email = $current_user->getEmail();
    $user_list = $simpleListsApi->getUserListStatus($group_slug, $current_user_email, $msg);
    $user_list = $user_list ? $user_list : 'none';
    $sl_options = [
      'full' => [
        'title' => 'Receive all emails',
        'url' => '/simplelist/full',
      ],
      'daily' => [
        'title' => 'Daily Digest',
        'url' => '/simplelist/daily',
      ],
      'none' => [
        'title' => 'No emails',
        'url' => '/simplelist/none',
      ],
    ];
    $list_default = $sl_options[$user_list];
    $options = '';
    $path = Xss::filter(\Drupal::service('path.current')->getPath());
    foreach ($sl_options as $key => $value) {
      if ($key != $user_list) {
        $options .= '<li><a href="' . $value['url'] . '?current=' . $user_list . '&redirect=' . $path . '&slug=' . $group_slug . '">' . $value['title'] . '</a></li>';
      }
    }
    $simple['string'] = [
      '#type' => 'inline_template',
      'lib' => [
        '#attached' => [
          'library' => [
            'access_misc/copyclip',
          ],
        ],
      ],
      '#template' => '<div class="bg-light-teal px-4 pt-4 mb-10 block block-layout-builder block-inline-blockbasic">
        <div class="clearfix text-formatted field field--name-body field--type-text-with-summary field--label-hidden field__item">
          <h3 class="border-bottom pb-2 me-3 mr-3 mt-0">
            {{ block_title }}
          </h3>
          <div class="d-flex flex items-center">
            <span class="font-bold text-sm">access-support@lists.com</span>
            <button class="copyclip text-sm ms-4 top-4 right-28 z-10" onclick="copyclip(\'access-support@lists.com\', event)">
              <span class="default-message block leading-5 text-dark-teal">
                <svg class="svg-inline--fa fa-link" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="link" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" data-fa-i2svg=""><path fill="currentColor" d="M579.8 267.7c56.5-56.5 56.5-148 0-204.5c-50-50-128.8-56.5-186.3-15.4l-1.6 1.1c-14.4 10.3-17.7 30.3-7.4 44.6s30.3 17.7 44.6 7.4l1.6-1.1c32.1-22.9 76-19.3 103.8 8.6c31.5 31.5 31.5 82.5 0 114L422.3 334.8c-31.5 31.5-82.5 31.5-114 0c-27.9-27.9-31.5-71.8-8.6-103.8l1.1-1.6c10.3-14.4 6.9-34.4-7.4-44.6s-34.4-6.9-44.6 7.4l-1.1 1.6C206.5 251.2 213 330 263 380c56.5 56.5 148 56.5 204.5 0L579.8 267.7zM60.2 244.3c-56.5 56.5-56.5 148 0 204.5c50 50 128.8 56.5 186.3 15.4l1.6-1.1c14.4-10.3 17.7-30.3 7.4-44.6s-30.3-17.7-44.6-7.4l-1.6 1.1c-32.1 22.9-76 19.3-103.8-8.6C74 372 74 321 105.5 289.5L217.7 177.2c31.5-31.5 82.5-31.5 114 0c27.9 27.9 31.5 71.8 8.6 103.9l-1.1 1.6c-10.3 14.4-6.9 34.4 7.4 44.6s34.4 6.9 44.6-7.4l1.1-1.6C433.5 260.8 427 182 377 132c-56.5-56.5-148-56.5-204.5 0L60.2 244.3z"></path></svg><!-- <i class="fa-solid fa-link"></i> --><br>
                {{ copy }}
              </span>
              <span class="copied-message text-dark-teal hidden d-none">
                <svg class="svg-inline--fa fa-check" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="check" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" data-fa-i2svg=""><path fill="currentColor" d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"></path></svg><!-- <i class="fa-solid fa-check"></i> --><br>
                Copied!
              </span>
            </button>
          </div>
          <details class="bg-white">
            <summary class="bg-yellow font-bold">
              {{ list_default.title }}
            </summary>
            <div>
              <ul class="list-none">
                {{ options | raw }}
              </ul>
            </div>
          </details>
        </div>
      </div>',
      '#context' => [
        'block_title' => t('Member Email List'),
        'copy' => t('Copy'),
        'list_default' => $list_default,
        'options' => $options,
      ],
    ];

    return $simple;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      // Get current user id.
      $current_user = \Drupal::currentUser();
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id() . ':user:' . $current_user->id()]);
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
