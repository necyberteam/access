<?php

namespace Drupal\access_match_engagement\Plugin\Block;

use Drupal\node\NodeInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Cache\Cache;

/**
 * Access Match Node Block.
 *
 * @Block(
 *   id = "match_node_block",
 *   admin_label = @Translation("Access Match Node Block")
 * )
 */
class MatchNodeBlock extends BlockBase implements
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
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match_interface
   *   Invokes routeMatch.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_interface, RouteMatchInterface $route_match_interface) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityInterface = $entity_interface;
    $this->routMatchInterface = $route_match_interface;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $thisNode = $this->routMatchInterface->getParameter('node');
    if ($thisNode instanceof NodeInterface) {
      $nid = $thisNode->id();
      $node = $this->entityInterface->getStorage('node')->load($nid);
      $mentor = $node->get('field_mentor')->getValue();
      $mentor = $this->entityInterface->getStorage('user')->load($mentor[0]['target_id']);
      $students = $node->get('field_students')->getValue(); 
      $students = $this->entityInterface->getStorage('user')->load($students[0]['target_id']);
      $image = $node->get('field_project_image')->getValue();
      $img_file = File::load($image[0]['target_id']);
      $image_loaded = '';
      if ($img_file) {
        $uri = $img_file->getFileUri();
        $image_full = Url::fromUri(file_create_url($uri))->toString();
        $alt = $image[0]['alt'];
        $width = $image[0]['width'];
        $height = $image[0]['height'];
        $image_styled = ImageStyle::load('access_match_sidebar')->buildUrl($uri);
        $image_loaded = "<img src='$image_styled' alt='$alt' width='$width' height='$height' style='height: auto;' />";
      }
      $tags = $node->get('field_tags')->getValue();
      $tag_list = '';
      $tag_count = count($tags);
      $tag_iterate = 0;
      foreach ($tags as $key => $tag) {
        $tag_iterate++;
        $tag_id = $tag['target_id'];
        $tag_load = $this->entityInterface->getStorage('taxonomy_term')->load($tag_id)->get('name')->value;
        $tag_list .= "<a href='/taxonomy/term/$tag_id'>$tag_load</a>";
        if ($tag_count > $tag_iterate) {
          $tag_list .= ", ";          
        }
      }
      return [
        '#type' => 'inline_template',
        '#template' => '<div class="p-3">
          <div class="pb-3">{{ image | raw }}</div>
          <div><span class="fw-bold">{{ mentor_label }}:</span> {{ mentor }}</div> 
          <div><span class="fw-bold">{{ students_label }}:</span> {{ students }}</div> 
          <div><span class="fw-bold">{{ tags_label }}:</span> {{ tags | raw }}</div>
        </div>',
        '#context' => [
          'mentor_label' => $this->t('Mentor'),
          'mentor' => $mentor->get('field_user_first_name')->value . ' ' . $mentor->get('field_user_last_name')->value,
          'students_label' => $this->t('Student'),
          'students' => $students->get('field_user_first_name')->value . ' ' . $students->get('field_user_last_name')->value,
          'tags_label' => $this->t('Tags'),
          'tags' => $tag_list,
          'image' => $image_loaded,
        ]
      ];
    }
  }

  /**
   * Set cache tag by node id.
   */
  public function getCacheTags() {
    // With this when your node change your block will rebuild.
    if ($node = $this->routMatchInterface->getParameter('node')) {
      // If there is node add its cachetag.
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
    }
    else {
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

