<?php

namespace Drupal\community_persona\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Controller for Community Persona.
 */
class CommunityPersonaController extends ControllerBase {

  /**
   * Build content to display on page.
   */
  public function communityPersona() {
    $current_user = \Drupal::currentUser();
    $query = \Drupal::database()->select('flagging', 'fl');
    $query->condition('fl.uid', $current_user->id());
    $query->condition('fl.flag_id', 'affinity_group');
    $query->fields('fl', ['entity_id']);
    $affinity_groups = $query->execute()->fetchCol();
    $user_affinity_groups = '<ul>';
    foreach ($affinity_groups as $affinity_group) {
      $query = \Drupal::database()->select('taxonomy_index', 'ti');
      $query->condition('ti.tid', $affinity_group);
      $query->fields('ti', ['nid']);
      $affinity_group_nid = $query->execute()->fetchCol();
      $affinity_group_loaded = \Drupal::entityTypeManager()->getStorage('node')->load($affinity_group_nid[0]);
      $url = Url::fromRoute('entity.node.canonical', array('node' => $affinity_group_loaded->id()));
      $project_link = Link::fromTextAndUrl($affinity_group_loaded->getTitle(), $url);
      $link = $project_link->toString()->__toString();
      $user_affinity_groups .= "<li>$link</li>";
    }
    $affinity_url = Url::fromUri('internal:/affinity-groups');
    $affinity_link = Link::fromTextAndUrl('See all Affinity Groups', $affinity_url);
    $affinity_renderable = $affinity_link->toRenderable();
    $build_affinity_link = $affinity_renderable;
    $build_affinity_link['#attributes']['class'] = ['btn', 'btn-primary', 'btn-sm'];
    $user_affinity_groups .= '</ul>';
    // My Expertise
    $term = \Drupal::database()->select('flagging', 'fl');
    $term->condition('fl.uid', $current_user->id());
    $term->condition('fl.flag_id', 'skill');
    $term->fields('fl', ['entity_id']);
    $flagged_skills = $term->execute()->fetchCol();
    $my_skills = '';
    foreach ($flagged_skills as $flagged_skill) {
      $my_skills .= "<div class='border border-black m-1 p-1'>";
      $my_skills .= \Drupal\taxonomy\Entity\Term::load($flagged_skill)->get('name')->value;
      $my_skills .= "</div>";
    }
    $edit_skill_url = Url::fromUri('internal:/add-skill');
    $edit_skill_link = Link::fromTextAndUrl('Edit expertise', $edit_skill_url);
    $edit_skill_renderable = $edit_skill_link->toRenderable();
    $persona_page['string'] = [
      '#type' => 'inline_template',
      '#template' => '<div class="border border-secondary my-3">
          <div class="text-white h4 p-3 bg-dark">{{ ag_title }}</div>
            <div class="p-3">
              <p>{{ ag_intro }}</p>
              {{ user_affinity_groups|raw }}
              {{ affinity_link }}
            </div>
        </div>
        <div class="border border-secondary my-3">
          <div class="text-white p-3 bg-dark d-flex justify-content-between">
            <span class="h4 text-white">{{ me_title }}</span>
            <span>{{ edit_skill_link }}</span>
          </div>
          <div class="d-flex p-3">
            {{ my_skills|raw }}
          </div>
        </div>',
      '#context' => [
        'ag_title' => t('My Affinity Groups'),
        'ag_intro' => t('Connected with researchers of common interests.'),
        'user_affinity_groups' => $user_affinity_groups,
        'affinity_link' => $build_affinity_link,
        'me_title' => t('My Expertise'),
        'my_skills' => $my_skills,
        'edit_skill_link' => $edit_skill_renderable,
      ],
    ];

    return $persona_page;
  }

}
