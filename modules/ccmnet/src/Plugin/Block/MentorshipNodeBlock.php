<?php

namespace Drupal\ccmnet\Plugin\Block;

use Drupal\node\NodeInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Cache\Cache;

/**
 * Access Mentorship Node Block.
 *
 * @Block(
 *   id = "mentorship_node_block",
 *   admin_label = @Translation("Access Mentorship Node Block")
 * )
 */
class MentorshipNodeBlock extends BlockBase implements
  ContainerFactoryPluginInterface {

  /**
   * Invoke renderer.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityInterface;

  /**
   * Invoke renderer.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Invoke renderer.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routMatchInterface;

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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('file_url_generator'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param array $configuration
   *   Configuration array.
   * @param string $plugin_id
   *   Plugin id string.
   * @param mixed $plugin_definition
   *   Plugin Definition mixed.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_interface
   *   Invokes renderer.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   File url generator.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface
    $entity_interface,
    RouteMatchInterface $route_match_interface,
    FileUrlGeneratorInterface $file_url_generator
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityInterface = $entity_interface;
    $this->routMatchInterface = $route_match_interface;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $thisNode = $this->routMatchInterface->getParameter('node');
    if ($thisNode instanceof NodeInterface) {
      $nid = $thisNode->id();
      $node = $this->entityInterface->getStorage('node')->load($nid);
      $state = $node->get('field_me_state')->getValue();
      if ($state) {
        $lookup = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => 'recruiting', 'vid' => 'state']);
        $state_tid = array_keys($lookup)[0];
        $state = $state[0]['target_id'];
        $is_recruiting = strcasecmp($state, $state_tid) == 0 ? TRUE : FALSE;
      }
      $nid = $node->id();
      $interested_users = $node->get('field_match_interested_users')->getValue();
      // Lookup user names from uid.
      $interested_users = $this->getInterestedUsers($interested_users);
      $interested_button = '';
      if ($is_recruiting) {
        $interested_list = $node->get('field_match_interested_users')->getValue();
        $user = \Drupal::currentUser()->id();
        if (array_search($user, array_column($interested_list, 'target_id')) !== FALSE) {
          $uninterested_text = $this->t("I'm no longer Interested");
          $interested_button = "<a class='btn btn-primary' href='/node/$nid/interested'>$uninterested_text</a>";
        }
        else {
          $interested_text = $this->t("I'm Interested");
          $interested_button = $is_recruiting ? "<a class='btn btn-primary' href='/node/$nid/interested'>$interested_text</a>" : '';
        }
      }
      $match_node_block['string'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="">
          <div class="mb-5">
           {{ interested_button | raw }}
          </div>
          {% if interested_users %}
            <div>
              <h3>Interested People</h3>
                <ul>
                {% for interested_user in interested_users %}
                  <li>{{ interested_user }}</li>
                {% endfor %}
                </ul>
            </div>
        {% endif %}',
        '#context' => [
          'interested_button' => $interested_button,
          'interested_users' => $interested_users,
        ],
      ];
      return $match_node_block;
    }
    else {
      return [
        '#markup' => $this->t('Mentorship Node Block - not a mentorship node')
      ];

    }
  }

  /**
   * Get interested users.
   */
  public function getInterestedUsers($interested_users) {
    // Only show interested users to match_sc, match_pm, and admin.
    $accepted_roles = ['administrator', 'ccmnet_pm'];
    $current_user = \Drupal::currentUser();
    $roles = $current_user->getRoles();
    $hide = TRUE;
    foreach ($accepted_roles as $role) {
      if (in_array($role, $roles)) {
        $hide = FALSE;
        break;
      }
    }
    if ($hide) {
      return [];
    };

    $interested_users = array_column($interested_users, 'target_id');
    $users = $this->entityInterface->getStorage('user')->loadMultiple($interested_users);
    $user_names = [];
    foreach ($users as $user) {
      $user_names[] = $user->get('field_user_first_name')->value . ' ' . $user->get('field_user_last_name')->value;
    }
    return $user_names;
  }

  /**
   * Set cache tag by node id.
   */
  public function getCacheTags() {
    // With this when your node change your block will rebuild.
    if ($node = $this->routMatchInterface->getParameter('node')) {
      // If there is node add its cachetag.
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
    } else {
      // Return default tags instead.
      return parent::getCacheTags();
    }
  }

  /**
   * Return cache contexts.
   */
  public function getCacheContexts() {
    // If you depends on \Drupal::routeMatch()
    // you must set context of this block with 'route' context tag.
    // Every new route this block will rebuild.
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }
}
