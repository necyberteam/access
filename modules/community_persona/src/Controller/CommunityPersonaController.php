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
    $user_affinity_groups = $affinity_groups == NULL ?
      '<p>' . t('You currently are not connected to any Affinity groups. Click below to explore.') . "</p>"
      :'<ul>';
    if ($user_affinity_groups == "<ul>") {
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
      $user_affinity_groups .= '</ul>';
    }
    $affinity_url = Url::fromUri('internal:/affinity_groups');
    $affinity_link = Link::fromTextAndUrl('See all Affinity Groups', $affinity_url);
    $affinity_renderable = $affinity_link->toRenderable();
    $build_affinity_link = $affinity_renderable;
    $build_affinity_link['#attributes']['class'] = ['btn', 'btn-primary', 'btn-sm'];
    // My Expertise
    $term = \Drupal::database()->select('flagging', 'fl');
    $term->condition('fl.uid', $current_user->id());
    $term->condition('fl.flag_id', 'skill');
    $term->fields('fl', ['entity_id']);
    $flagged_skills = $term->execute()->fetchCol();
    $my_skills = $flagged_skills == NULL ?
      '<p>' . t('You currently have not added any skills. Click Edit expertise to add.') . "</p>"
      :'';
    if ($my_skills == "") {
      foreach ($flagged_skills as $flagged_skill) {
        $term_title = \Drupal\taxonomy\Entity\Term::load($flagged_skill)->get('name')->value;
        $my_skills .= "<div class='border border-black m-1 p-1'>";
        $my_skills .= "<a class='btn btn-white btn-sm' href='/taxonomy/term/" . $flagged_skill . "'>" . $term_title . "</a>";
        $my_skills .= "</div>";
      }
    }
    $edit_skill_url = Url::fromUri('internal:/add-skill');
    $edit_skill_link = Link::fromTextAndUrl('Edit expertise', $edit_skill_url);
    $edit_skill_renderable = $edit_skill_link->toRenderable();
    // My Knowledge Base Contributions
    $ws_query = \Drupal::entityQuery('webform_submission')
    ->condition('uid', $current_user->id())
    ->condition('uri', '/form/resource');
    $ws_results = $ws_query->execute();
    $ws_link = $ws_results == NULL ?
      '<p>' . t('You currently have not contributed to the Knowledge Base. Click below to contribute.') . "</p>"
      :'<ul>';
    if ($ws_link == "<ul>") {
      foreach ($ws_results as $ws_result) {
        $ws = \Drupal\webform\Entity\WebformSubmission::load($ws_result);
        $ws_data = $ws->getData();
        foreach ($ws_data['link_to_resource'] as $resource_link) {
          $resource_title = $resource_link['title'];
          $resource_url = $resource_link['url'];
          $ws_link .= "<li><a href='$resource_url'>$resource_title</a></li>";
        }
      }
      $ws_link .= '</ul>';
    }
    $webform_url = Url::fromUri('internal:/form/resource');
    $webform_link = Link::fromTextAndUrl('Contribute to Knowledge Base', $webform_url);
    $webform_renderable = $webform_link->toRenderable();
    $build_webform_link = $webform_renderable;
    $build_webform_link['#attributes']['class'] = ['btn', 'btn-primary', 'btn-sm'];
    // My Match Engagements
    $query = \Drupal::entityQuery('node');
    $group = $query
      ->orConditionGroup()
      ->condition('field_mentor', $current_user->id())
      ->condition('field_students', $current_user->id())
      ->condition('field_consultant', $current_user->id());
    $results = $query->condition($group)
      ->condition('type', 'match_engagement')
      ->execute();
    $match_engagement_nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($results);
    $match_link = $match_engagement_nodes == NULL ?
      '<p>' . t('You currently have not requested any Match Engagements. Click below to request.') . "</p>"
      :'<ul>';
    if ($match_link == "<ul>") {
      foreach ($match_engagement_nodes as $match_engagement_node) {
        $title = $match_engagement_node->getTitle();
        $nid = $match_engagement_node->id();
        $match_link .= "<li><a href='/node/$nid'>$title</a></li>";
      }
      $match_link .= '</ul>';
    }
    $match_engage_url = Url::fromUri('internal:/matchplus');
    $match_engage_link = Link::fromTextAndUrl('Request an Engagement', $match_engage_url);
    $match_engage_renderable = $match_engage_link->toRenderable();
    $build_match_engage_link = $match_engage_renderable;
    $build_match_engage_link['#attributes']['class'] = ['btn', 'btn-primary', 'btn-sm'];
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
        </div>
        <div class="border border-secondary my-3">
          <div class="text-white p-3 bg-dark d-flex justify-content-between">
            <span class="h4 text-white">{{ ws_title }}</span>
          </div>
          <div class="p-3">
            {{ ws_links|raw }}
            {{ request_webform_link }}
          </div>
        </div>
        <div class="border border-secondary my-3">
          <div class="text-white p-3 bg-dark d-flex justify-content-between">
            <span class="h4 text-white">{{ match_title }}</span>
          </div>
          <div class="p-3">
            {{ match_links|raw }}
            {{ request_match_link }}
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
        'match_title' => t('My Match Engagements'),
        'match_links' => $match_link,
        'request_match_link' => $build_match_engage_link,
        'ws_title' => t('My Knowledge Base Contributions'),
        'ws_links' => $ws_link,
        'request_webform_link' => $build_webform_link,
      ],
    ];

    return $persona_page;
  }

}
