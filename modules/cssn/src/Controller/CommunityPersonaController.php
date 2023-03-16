<?php

namespace Drupal\cssn\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\cssn\Plugin\Util\MatchLookup;
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
    // My Affinity Groups
    $current_user = \Drupal::currentUser();
    $user_entity = \Drupal::entityTypeManager()->getStorage('user')->load($current_user->id());
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
        if (isset($affinity_group_nid[0])) {
          $affinity_group_loaded = \Drupal::entityTypeManager()->getStorage('node')->load($affinity_group_nid[0]);
          $url = Url::fromRoute('entity.node.canonical', array('node' => $affinity_group_loaded->id()));
          $project_link = Link::fromTextAndUrl($affinity_group_loaded->getTitle(), $url);
          $link = $project_link->toString()->__toString();
          $user_affinity_groups .= "<li>$link</li>";
        }
      }
      $user_affinity_groups .= '</ul>';
    }
    $affinity_url = Url::fromUri('internal:/affinity_groups');
    $affinity_link = Link::fromTextAndUrl('See all Affinity Groups', $affinity_url);
    $affinity_renderable = $affinity_link->toRenderable();
    $build_affinity_link = $affinity_renderable;
    $build_affinity_link['#attributes']['class'] = ['btn', 'btn-primary', 'btn-sm', 'py-1', 'px-2'];
    // My Interests
    $term_interest = \Drupal::database()->select('flagging', 'fl');
    $term_interest->condition('fl.uid', $current_user->id());
    $term_interest->condition('fl.flag_id', 'interest');
    $term_interest->fields('fl', ['entity_id']);
    $flagged_interests = $term_interest->execute()->fetchCol();
    $my_interests = $flagged_interests == NULL ?
      '<p>' . t('You currently have not added any interests. Click Edit interests to add.') . "</p>"
      :'';
    if ($my_interests == "") {
      foreach ($flagged_interests as $flagged_interest) {
        $term_title = \Drupal\taxonomy\Entity\Term::load($flagged_interest)->get('name')->value;
        $my_interests .= "<div class='border border-black m-1 p-1'>";
        $my_interests .= "<a style='text-transform: inherit;' class='btn btn-white btn-sm' href='/taxonomy/term/" . $flagged_interest . "'>" . $term_title . "</a>";
        $my_interests .= "</div>";
      }
    }
    $edit_interest_url = Url::fromUri('internal:/add-interest');
    $edit_interest_link = Link::fromTextAndUrl('Edit interests', $edit_interest_url);
    $edit_interest_renderable = $edit_interest_link->toRenderable();
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
        $my_skills .= "<a style='text-transform: inherit;' class='btn btn-white btn-sm' href='/taxonomy/term/" . $flagged_skill . "'>" . $term_title . "</a>";
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
    $build_webform_link['#attributes']['class'] = ['btn', 'btn-primary', 'btn-sm', 'py-1', 'px-2'];
    // My Match Engagements
    $fields = [
      'field_mentor' => 'Mentor',
      'field_students' => 'Student',
      'field_consultant' => 'Consultant',
      'field_researcher' => 'Researcher',
      'field_match_interested_users' => 'Interested',
    ];
    $matches = new MatchLookup($fields, $current_user->id());
    // Sort by status.
    $matches->sortStatusMatches();
    $match_list = $matches->getMatchList();
    $match_link = $match_list == '' ?
      '<p>' . t('You are not currently involved with any MATCH Engagements.') . "</p>"
      :"<ul class='list-unstyled'>";
    if ($match_link == "<ul class='list-unstyled'>") {
      $match_link .= $match_list . '</ul>';
    }
    $match_engage_url = Url::fromUri('internal:/engagements');
    $match_engage_link = Link::fromTextAndUrl('See all engagements', $match_engage_url);
    $match_engage_renderable = $match_engage_link->toRenderable();
    $build_match_engage_link = $match_engage_renderable;
    $build_match_engage_link['#attributes']['class'] = ['btn', 'btn-primary', 'btn-sm', 'py-1', 'px-2'];
    $persona_page['string'] = [
      '#type' => 'inline_template',
      '#template' => '<div class="border border-secondary my-3">
          <div class="text-white h4 py-2 px-3 m-0 bg-dark">{{ ag_title }}</div>
            <div class="p-3">
              <p>{{ ag_intro }}</p>
              {{ user_affinity_groups|raw }}
              {{ affinity_link }}
            </div>
        </div>
        <div class="border border-secondary my-3">
          <div class="text-white py-2 px-3 bg-dark d-flex align-items-center justify-content-between">
            <span class="h4 text-white m-0">{{ mi_title }}</span>
            <span><i class="fa-solid fa-pen-to-square"></i> {{ edit_interest_link }}</span>
          </div>
          <div class="d-flex flex-wrap p-3">
            {{ my_interests|raw }}
          </div>
        </div>
        <div class="border border-secondary my-3">
          <div class="text-white py-2 px-3 bg-dark d-flex align-items-center justify-content-between">
            <span class="h4 text-white m-0">{{ me_title }}</span>
            <span><i class="fa-solid fa-pen-to-square"></i> {{ edit_skill_link }}</span>
          </div>
          <div class="d-flex flex-wrap p-3">
            {{ my_skills|raw }}
          </div>
        </div>
        <div class="border border-secondary my-3">
          <div class="text-white py-2 px-3 bg-dark d-flex align-items-center justify-content-between">
            <span class="h4 m-0 text-white">{{ ws_title }}</span>
          </div>
          <div class="p-3">
            {{ ws_links|raw }}
            {{ request_webform_link }}
          </div>
        </div>
        <div class="border border-secondary my-3">
          <div class="text-white py-2 px-3 bg-dark d-flex align-items-center justify-content-between">
            <span class="h4 m-0 text-white">{{ match_title }}</span>
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
        'mi_title' => t('My Interests'),
        'my_interests' => $my_interests,
        'edit_interest_link' => $edit_interest_renderable,
        'me_title' => t('My Expertise'),
        'my_skills' => $my_skills,
        'edit_skill_link' => $edit_skill_renderable,
        'match_title' => t('My MATCH Engagements'),
        'match_links' => $match_link,
        'request_match_link' => $build_match_engage_link,
        'ws_title' => t('My Knowledge Base Contributions'),
        'ws_links' => $ws_link,
        'request_webform_link' => $build_webform_link,
      ],
    ];

    // Deny any page caching on the current request.
    \Drupal::service('page_cache_kill_switch')->trigger();

    return $persona_page;
  }

}