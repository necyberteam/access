<?php

namespace Drupal\cssn\Controller;

use Drupal\webform\Entity\WebformSubmission;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Controller\ControllerBase;
use Drupal\cssn\Plugin\Util\MatchLookup;
use Drupal\cssn\Plugin\Util\EndUrl;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\user\Entity\User;

/**
 * Controller for Community Persona.
 */
class CommunityPersonaController extends ControllerBase {

  /**
   * List of affinity groups given user has flagged.
   *
   * @return string
   *   List of affinity groups.
   */
  public function affinityGroupList($user, $public = FALSE) {
    $query = \Drupal::database()->select('flagging', 'fl');
    $query->condition('fl.uid', $user->id());
    $query->condition('fl.flag_id', 'affinity_group');
    $query->fields('fl', ['entity_id']);
    $affinity_groups = $query->execute()->fetchCol();
    $user_affinity_groups = "<ul>";
    if ($affinity_groups == NULL && $public === FALSE) {
      $user_affinity_groups = '<p>' . t('You currently are not connected to any Affinity groups. Click below to explore.') . "</p>";
    }
    if ($affinity_groups == NULL && $public === TRUE) {
      $user_affinity_groups = '<p>' . t('Not connected to any Affinity groups.') . "</p>";
    }
    if ($user_affinity_groups == "<ul>") {
      foreach ($affinity_groups as $affinity_group) {
        $query = \Drupal::database()->select('taxonomy_index', 'ti');
        $query->condition('ti.tid', $affinity_group);
        $query->fields('ti', ['nid']);
        $affinity_group_nid = $query->execute()->fetchCol();
        if (isset($affinity_group_nid[0])) {
          $affinity_group_loaded = \Drupal::entityTypeManager()->getStorage('node')->load($affinity_group_nid[0]);
          $url = Url::fromRoute('entity.node.canonical', ['node' => $affinity_group_loaded->id()]);
          $project_link = Link::fromTextAndUrl($affinity_group_loaded->getTitle(), $url);
          $link = $project_link->toString()->__toString();
          $user_affinity_groups .= "<li>$link</li>";
        }
      }
      $user_affinity_groups .= '</ul>';
    }
    return $user_affinity_groups;
  }

  /**
   * Link to Affinity page.
   *
   * @return string
   *   Link to affinity page.
   */
  public function buildAffinityLink() {
    $affinity_url = Url::fromUri('internal:/affinity_groups');
    $affinity_link = Link::fromTextAndUrl('See all Affinity Groups', $affinity_url);
    $affinity_renderable = $affinity_link->toRenderable();
    $build_affinity_link = $affinity_renderable;
    $build_affinity_link['#attributes']['class'] = ['btn', 'btn-primary', 'btn-sm', 'py-1', 'px-2'];
    return $build_affinity_link;
  }

  /**
   * Return list of flagged Expertise.
   *
   * @return string
   *   List of expertise.
   */
  public function mySkills($user, $public = FALSE) {
    $term = \Drupal::database()->select('flagging', 'fl');
    $term->condition('fl.uid', $user->id());
    $term->condition('fl.flag_id', 'skill');
    $term->fields('fl', ['entity_id']);
    $flagged_skills = $term->execute()->fetchCol();
    $my_skills = "";
    if ($flagged_skills == NULL && $public === FALSE) {
      $my_skills = '<p>' . t('You currently have not added any skills. Click Edit expertise to add.') . "</p>";
    }
    if ($flagged_skills == NULL && $public === TRUE) {
      $my_skills = '<p>' . t('No skills added.') . "</p>";
    }
    if ($my_skills == "") {
      foreach ($flagged_skills as $flagged_skill) {
        $term_title = Term::load($flagged_skill)->get('name')->value;
        $my_skills .= "<div class='border border-black m-1 p-1'>";
        $my_skills .= "<a style='text-transform: inherit;' class='btn btn-white btn-sm' href='/taxonomy/term/" . $flagged_skill . "'>" . $term_title . "</a>";
        $my_skills .= "</div>";
      }
    }
    return $my_skills;
  }

  /**
   * Return list of Knowledge Contributions.
   *
   * @return string
   *   List of Knowledge Contributions.
   */
  public function knowledgeBaseContrib($user, $public = FALSE) {
    $ws_query = \Drupal::entityQuery('webform_submission')
      ->condition('uid', $user->id())
      ->condition('uri', '/form/resource');
    $ws_results = $ws_query->execute();
    $ws_link = "<ul>";
    if ($ws_results == NULL && $public === FALSE) {
      $ws_link = '<p>' . t('You currently have not contributed to the Knowledge Base. Click below to contribute.') . "</p>";
    }
    if ($ws_results == NULL && $public === TRUE) {
      $ws_link = '<p>' . t('No contributions to the Knowledge Base.') . "</p>";
    }
    if ($ws_link == "<ul>") {
      $ws_link = '<ul>';
      foreach ($ws_results as $ws_result) {
        $ws = WebformSubmission::load($ws_result);
        $url = $ws->toUrl()->toString();
        $ws_data = $ws->getData();
        $ws_link .= '<li><a href=' . $url . '>' . $ws_data['title'] . '</a></li>';
      }
      $ws_link .= '</ul>';
    }
    return $ws_link;
  }

  /**
   * Return list of engagements.
   *
   * @return string
   *   List of engagements.
   */
  public function matchList($user, $public = FALSE) {
    $fields = [
      'field_match_interested_users' => 'Interested',
      'field_mentor' => 'Mentor',
      'field_students' => 'Student',
      'field_consultant' => 'Consultant',
      'field_researcher' => 'Researcher',
    ];
    $matches = new MatchLookup($fields, $user->id());
    // Sort by status.
    $matches->sortStatusMatches();
    $match_list = $matches->getMatchList();
    $match_link = "<ul class='list-unstyled'>";
    if ($match_list == NULL && $public === FALSE) {
      $match_link = '<p>' . t('You currently have not been matched with any Engagements. Click below to find an Engagement.') . "</p>";
    }
    if ($match_list == NULL && $public === TRUE) {
      $match_link = '<p>' . t('No matched Engagements.') . "</p>";
    }
    if ($match_link == "<ul class='list-unstyled'>") {
      $match_link .= $match_list . '</ul>';
    }
    return $match_link;
  }

  /**
   * Return list of Interests.
   *
   * @return string
   *   List of the person's interest.
   */
  public function buildInterests($user, $public = FALSE) {
    $term_interest = \Drupal::database()->select('flagging', 'fl');
    $term_interest->condition('fl.uid', $user->id());
    $term_interest->condition('fl.flag_id', 'interest');
    $term_interest->fields('fl', ['entity_id']);
    $flagged_interests = $term_interest->execute()->fetchCol();
    $my_interests = "";
    if ($flagged_interests == NULL && $public === FALSE) {
      $my_interests = '<p>' . t('You currently have not added any interests. Click Edit interests to add.') . "</p>";
    }
    if ($flagged_interests == NULL && $public === TRUE) {
      $my_interests = '<p>' . t('No interests added.') . "</p>";
    }
    if ($my_interests == "") {
      foreach ($flagged_interests as $flagged_interest) {
        $term_title = Term::load($flagged_interest)->get('name')->value;
        $my_interests .= "<div class='border border-black m-1 p-1'>";
        $my_interests .= "<a style='text-transform: inherit;' class='btn btn-white btn-sm' href='/taxonomy/term/" . $flagged_interest . "'>" . $term_title . "</a>";
        $my_interests .= "</div>";
      }
    }
    return $my_interests;
  }

  /**
   * Build content to display on page.
   */
  public function communityPersona() {
    // My Affinity Groups.
    $current_user = \Drupal::currentUser();
    // List of affinity groups.
    $user_affinity_groups = $this->affinityGroupList($current_user);
    // Affinity link.
    $build_affinity_link = $this->buildAffinityLink();
    // My Interests.
    $my_interests = $this->buildInterests($current_user);
    // Edit interests link.
    $edit_interest_url = Url::fromUri('internal:/add-interest');
    $edit_interest_link = Link::fromTextAndUrl('Edit interests', $edit_interest_url);
    $edit_interest_renderable = $edit_interest_link->toRenderable();
    // My Expertise.
    $my_skills = $this->mySkills($current_user);
    // Link to add Skills/Expertise.
    $edit_skill_url = Url::fromUri('internal:/add-skill');
    $edit_skill_link = Link::fromTextAndUrl('Edit expertise', $edit_skill_url);
    $edit_skill_renderable = $edit_skill_link->toRenderable();
    // My Knowledge Base Contributions.
    $ws_link = $this->knowledgeBaseContrib($current_user);

    // Link to add Knowledge Base Contribution webform.
    $webform_url = Url::fromUri('internal:/form/resource');
    $webform_link = Link::fromTextAndUrl('Contribute to Knowledge Base', $webform_url);
    $webform_renderable = $webform_link->toRenderable();
    $build_webform_link = $webform_renderable;
    $build_webform_link['#attributes']['class'] = ['btn', 'btn-primary', 'btn-sm', 'py-1', 'px-2'];
    // My Match Engagements.
    $match_link = $this->matchList($current_user);
    // Link to see all Match Engagements.
    $match_engage_url = Url::fromUri('internal:/engagements');
    $match_engage_link = Link::fromTextAndUrl('See all engagements', $match_engage_url);
    $match_engage_renderable = $match_engage_link->toRenderable();
    $build_match_engage_link = $match_engage_renderable;
    $build_match_engage_link['#attributes']['class'] = ['btn', 'btn-primary', 'btn-sm', 'py-1', 'px-2'];
    $persona_page['string'] = [
      '#type' => 'inline_template',
      '#attached' => [
        'library' => [
          'cssn/cssn_library',
        ],
      ],
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

  /**
   * Build public version of community persona page.
   */
  public function communityPersonaPublic() {
    // Get last item in url.
    $end_url = new EndUrl();
    $user_id = $end_url->getUrlEnd();
    $should_user_load = FALSE;
    if (is_numeric($user_id)) {
      $user = User::load($user_id);
      if ($user !== NULL) {
        $should_user_load = TRUE;
      }
      else {
        $should_user_load = FALSE;
      }
    }
    if ($should_user_load) {
      $user_first_name = $user->get('field_user_first_name')->value;
      $user_last_name = $user->get('field_user_last_name')->value;
      // List of affinity groups.
      $user_affinity_groups = $this->affinityGroupList($user, TRUE);
      // My Interests.
      $my_interests = $this->buildInterests($user, TRUE);
      // My Expertise.
      $my_skills = $this->mySkills($user, TRUE);
      // My Knowledge Base Contributions.
      $ws_link = $this->knowledgeBaseContrib($user, TRUE);
      // My Match Engagements.
      $match_link = $this->matchList($user, TRUE);

      $persona_page['#title'] = "$user_first_name $user_last_name";
      $persona_page['string'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="border border-secondary my-3">
            <div class="text-white h4 py-2 px-3 m-0 bg-dark">{{ ag_title }}</div>
              <div class="p-3">
                {{ user_affinity_groups|raw }}
              </div>
          </div>
          <div class="border border-secondary my-3">
            <div class="text-white py-2 px-3 bg-dark d-flex align-items-center justify-content-between">
              <span class="h4 text-white m-0">{{ mi_title }}</span>
            </div>
            <div class="d-flex flex-wrap p-3">
              {{ my_interests|raw }}
            </div>
          </div>
          <div class="border border-secondary my-3">
            <div class="text-white py-2 px-3 bg-dark d-flex align-items-center justify-content-between">
              <span class="h4 text-white m-0">{{ me_title }}</span>
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
            </div>
          </div>
          <div class="border border-secondary my-3">
            <div class="text-white py-2 px-3 bg-dark d-flex align-items-center justify-content-between">
              <span class="h4 m-0 text-white">{{ match_title }}</span>
            </div>
            <div class="p-3">
              {{ match_links|raw }}
            </div>
          </div>',
        '#context' => [
          'ag_title' => t('Affinity Groups'),
          'user_affinity_groups' => $user_affinity_groups,
          'mi_title' => t('Interests'),
          'my_interests' => $my_interests,
          'me_title' => t('Expertise'),
          'my_skills' => $my_skills,
          'ws_title' => t('Knowledge Base Contributions'),
          'ws_links' => $ws_link,
          'match_title' => t('MATCH Engagements'),
          'match_links' => $match_link,
        ],
      ];
      return $persona_page;
    }
    else {
      return [
        '#type' => 'markup',
        '#title' => 'User not found',
        '#markup' => t('No user found at this URL.'),
      ];
    }
  }

}
