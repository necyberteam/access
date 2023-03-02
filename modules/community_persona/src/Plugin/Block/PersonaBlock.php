<?php

namespace Drupal\community_persona\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\Core\Link;

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
    $institution = $user_entity->get('field_institution')->value;
    $roles = $user_entity->getRoles();
    $roles = implode('<br />', $roles);
    $current_user = \Drupal::currentUser();
    $user_entity = \Drupal::entityTypeManager()->getStorage('user')->load($current_user->id());
    $regions = $user_entity->get('field_region')->getValue();
    $terms = [];
    foreach ($regions as $region) {
      $region_tid = $region['target_id'];
      $terms[] = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($region_tid)->getName();
    }
    $program = implode(', ', $terms);
    $ws_query = \Drupal::entityQuery('webform_submission')
    ->condition('uid', $current_user->id())
    ->condition('uri', '/form/join-the-cssn-network');
    $ws_results = $ws_query->execute();
    $cssn_indicator = "";
    if (!empty($ws_results)) {
      $cssn_indicator = "<span class='text-primary'><i class='fa-solid fa-square'></i></span>";
      $cssn = "CSSN Member";
    } else {
      $cssn_url = Url::fromUri('internal:/form/join-the-cssn-network');
      $cssn_link = Link::fromTextAndUrl('Join the CSSN', $cssn_url);
      $cssn_renderable = $cssn_link->toRenderable();
      $cssn = $cssn_renderable;
      $cssn['#attributes']['class'] = ['btn', 'btn-primary', 'btn-sm', 'py-1', 'px-2'];
    }
      $cssn_more_url = Url::fromUri('internal:/cssn');
      $cssn_more_link = Link::fromTextAndUrl('Find out More', $cssn_more_url);
      $cssn_more_renderable = $cssn_more_link->toRenderable();
      $cssn_more = $cssn_more_renderable;
      $cssn_more['#attributes']['class'] = ['text-dark'];

    $persona_block['string'] = [
      '#type' => 'inline_template',
      '#template' => '<div class="p-3">
                        <h2>{{ first_name }} {{ last_name }}</h2>
                        <h4>{{ institution }}</h4>
                        <div class="d-flex justify-content-between">
                          <p>{{ cssn_indicator | raw }} <strong>{{ cssn }}</strong></p>
                          <div><i class="text-dark fa-regular fa-circle-info"></i> {{ cssn_more }}</div>
                        </div>
                        <div class="d-flex justify-content-between border-top border-bottom mb-3 py-3 border-secondary">
                          <div><b>{{ role_text }}:</b><br />{{ roles | raw }}</div>
                          <div><i class="text-dark fa-solid fa-pen-to-square"></i> <a href="#" class="text-dark">Edit Roles</a></div>
                        </div>
                        <p><b>{{ program_text }}:</b><br /> {{ program }}</p>
                      </div>',
      '#context' => [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'institution' => $institution,
        'cssn' => $cssn,
        'cssn_indicator' => $cssn_indicator,
        'cssn_more' => $cssn_more,
        'roles' => $roles,
        'role_text' => t('Roles'),
        'program' => $program,
        'program_text' => t('Programs')
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
