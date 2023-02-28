<?php

namespace Drupal\community_persona\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'Community Persona' Block.
 *
 * @Block(
 *   id = "community_persona_block",
 *   admin_label = "Community persona block",
 * )
 */
class PersonaBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_user = \Drupal::currentUser();
    $user_entity = \Drupal::entityTypeManager()->getStorage('user')->load($current_user->id());
    $first_name = $user_entity->get('field_user_first_name')->value;
    $last_name = $user_entity->get('field_user_last_name')->value;
    $persona_block['string'] = [
      '#type' => 'inline_template',
      '#template' => '<h2>{{ first_name }} {{ last_name }}</h2>',
      '#context' => [
        'first_name' => $first_name,
        'last_name' => $last_name,
      ],
    ];
    return $persona_block;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if ($user = \Drupal::currentUser()) {
      return Cache::mergeTags(parent::getCacheTags(), array('user:' . $user->id()));
    } else {
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), array('user'));
  }
}
