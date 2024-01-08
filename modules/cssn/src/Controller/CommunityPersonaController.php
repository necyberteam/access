<?php

namespace Drupal\cssn\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\cssn\Plugin\Util\EndUrl;
use Drupal\cssn\Plugin\Util\MatchLookup;
use Drupal\cssn\Plugin\Util\ProjectLookup;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Drupal\webform\Entity\WebformSubmission;

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
    $affinity_groups = array_unique($affinity_groups);
    $user_affinity_groups = "<ul>";
    if ($affinity_groups == NULL && $public === FALSE) {
      $user_affinity_groups = '<p>' . t('You currently are not connected to any Affinity groups. Click below to explore.') . "</p>";
    }
    if ($affinity_groups == NULL && $public === TRUE) {
      $user_affinity_groups = '<p>' . t('Not connected to any Affinity groups.') . "</p>";
    }
    if ($user_affinity_groups == '<ul>') {
      $user_affinity_groups = '<ul class="grid grid-cols-2 my-3">';
      foreach ($affinity_groups as $affinity_group) {
        $query = \Drupal::database()->select('taxonomy_index', 'ti');
        $query->condition('ti.tid', $affinity_group);
        $query->fields('ti', ['nid']);
        $affinity_group_nid = $query->execute()->fetchCol();
        if (isset($affinity_group_nid[0])) {
          $affinity_group_loaded = \Drupal::entityTypeManager()->getStorage('node')->load($affinity_group_nid[0]);
          $url = Url::fromRoute('entity.node.canonical', ['node' => $affinity_group_loaded->id()]);
          $class = ['font-bold', 'underline', 'hover--no-underline', 'hover--text-dark-teal'];
          $project_link = Link::fromTextAndUrl($affinity_group_loaded->getTitle(), $url)->toRenderable();
          $project_link['#attributes'] = ['class' => $class];
          $link = \Drupal::service('renderer')->render($project_link);
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
    $affinity_link = Link::fromTextAndUrl('All Affinity Groups', $affinity_url);
    $affinity_renderable = $affinity_link->toRenderable();
    $build_affinity_link = $affinity_renderable;
    $build_affinity_link['#attributes']['class'] = ['btn', 'btn-outline-dark', 'btn-md-teal', 'btn-sm', 'py-1', 'px-2', 'm-0'];
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
        $my_skills .= "<a class='no-underline font-normal mb-1 me-1 mr-1 px-2 py-1 hover--border-dark-teal border' href='/taxonomy/term/" . $flagged_skill . "'>" . $term_title . "</a>";
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
      ->condition('uri', '/form/ci-link')
      ->accessCheck(FALSE);
    $ws_results = $ws_query->execute();
    $ws_link = "<ul>";
    if ($ws_results == NULL && $public === FALSE) {
      $ws_link = '<p>' . t('You currently have not contributed to the Knowledge Base. Click below to contribute.') . "</p>";
    }
    if ($ws_results == NULL && $public === TRUE) {
      $ws_link = '<p>' . t('No contributions to the Knowledge Base.') . "</p>";
    }
    if ($ws_link == "<ul>") {
      $ws_link = "<ul class='list-unstyled list-none m-0 p-0'>";
      $n = 1;
      foreach ($ws_results as $ws_result) {
        $stripe_class = $n % 2 == 0 ? 'bg-light bg-light-teal' : '';
        $ws = WebformSubmission::load($ws_result);
        $url = $ws->toUrl()->toString();
        $ws_data = $ws->getData();
        $ws_link .= '<li class="p-3 ' . $stripe_class . '"><a href=' . $url . ' class="font-bold underline hover--no-underline hover--text-dark-teal">' . $ws_data['title'] . '</a></li>';
        $n++;
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
    $matches = new MatchLookup($fields, $user->id(), $public);
    // Sort by status.
    $matches->sortStatusMatches();
    $match_list = $matches->getMatchList();
    $match_link = "<ul class='list-unstyled m-0 p-0'>";
    if ($match_list == NULL && $public === FALSE) {
      $match_link = '<p>' . t('You currently have not been matched with any Engagements. Click below to find an Engagement.') . "</p>";
    }
    if ($match_list == NULL && $public === TRUE) {
      $match_link = '<p>' . t('No matched Engagements.') . "</p>";
    }
    if ($match_link == "<ul class='list-unstyled m-0 p-0'>") {
      $match_link .= $match_list . '</ul>';
    }
    return $match_link;
  }

  /**
   * Return list of projects.
   *
   * @return string
   *   List of projects.
   */
  public function projectList($user, $public = FALSE) {
    $fields = [
      'email' => 'Project Leader',
      'mentor' => 'Mentor',
      'mentors' => 'Mentor',
      'mentee_s_' => 'Mentee',
      'student' => 'Student-facilitator(s)',
      'students' => 'Student-facilitator(s)',
      'interested_in_project' => 'Interested',
    ];
    $projects = new ProjectLookup($fields, $user->id(), $user->getEmail());
    $projects->sortStatusProjects();
    $project_list = $projects->getProjectList();
    $project_link = "<ul class='list-unstyled list-none m-0 p-0'>";
    if ($project_list == NULL) {
      $project_link = 'na';
    }
    if ($project_link == "<ul class='list-unstyled list-none m-0 p-0'>") {
      $project_link .= $project_list . '</ul>';
    }
    return $project_link;
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
        $my_interests .= "<a class='no-underline font-normal mb-1 me-1 mr-1 px-2 py-1 hover--border-dark-teal border' href='/taxonomy/term/" . $flagged_interest . "'>" . $term_title . "</a>";
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
    $edit_interest_url = Url::fromUri('internal:/community-persona/add-interest');
    $edit_interest_link = Link::fromTextAndUrl('Update interests', $edit_interest_url);
    $edit_interest_renderable = $edit_interest_link->toRenderable();
    $edit_interest_renderable['#attributes']['class'] = ['btn', 'btn-primary', 'btn-sm', 'py-1', 'px-2'];
    // My Expertise.
    $my_skills = $this->mySkills($current_user);
    // Link to add Skills/Expertise.
    $edit_skill_url = Url::fromUri('internal:/community-persona/add-skill');
    $edit_skill_link = Link::fromTextAndUrl('Update expertise', $edit_skill_url);
    $edit_skill_renderable = $edit_skill_link->toRenderable();
    $edit_skill_renderable['#attributes']['class'] = ['btn', 'btn-primary', 'btn-sm', 'py-1', 'px-2'];
    // My Knowledge Base Contributions.
    $ws_link = $this->knowledgeBaseContrib($current_user);

    // Link to add Knowledge Base Contribution webform.
    $webform_url = Url::fromUri('internal:/form/resource');
    $webform_link = Link::fromTextAndUrl('Add CI Link', $webform_url);
    $webform_renderable = $webform_link->toRenderable();
    $build_webform_link = $webform_renderable;
    $build_webform_link['#attributes']['class'] = ['btn', 'btn-outline-dark', 'btn-md-teal', 'btn-sm', 'py-1', 'px-2', 'm-0'];
    // My Match Engagements.
    $match_link = $this->matchList($current_user);
    // Link to see all Match Engagements.
    $match_engage_url = Url::fromUri('internal:/engagements');
    $match_engage_link = Link::fromTextAndUrl('See engagements', $match_engage_url);
    $match_engage_renderable = $match_engage_link->toRenderable();
    $build_match_engage_link = $match_engage_renderable;
    $build_match_engage_link['#attributes']['class'] = ['btn', 'btn-outline-dark', 'btn-md-teal', 'btn-sm', 'py-1', 'px-2', 'm-0'];
    // My Projects.
    $projects = $this->projectList($current_user);

    $persona_page['string'] = [
      '#type' => 'inline_template',
      '#attached' => [
        'library' => [
          'cssn/cssn_library',
        ],
      ],
      '#template' => '<div class="border border-secondary border-md-teal my-3 mb-6">
          <div class="text-white py-2 px-3 bg-dark bg-md-teal text-2xl p-4 d-flex flex align-items-center justify-content-between">
            <span class="h4 text-white m-0">{{ mi_title }}</span>
          </div>
          <div class="d-flex flex flex-wrap p-3">
            {{ my_interests|raw }}
          </div>
          <div class="p-3">{{ edit_interest_link }}</div>
        </div>
        <div class="border border-secondary border-md-teal my-3 mb-6">
          <div class="text-white py-2 px-3 bg-dark bg-md-teal text-2xl p-4 d-flex flex align-items-center justify-content-between">
            <span class="h4 text-white m-0">{{ me_title }}</span>
          </div>
          <div class="d-flex flex flex-wrap p-3">
            {{ my_skills|raw }}
          </div>
          <div class="p-3">{{ edit_skill_link }}</div>
        </div>
        <div class="border border-secondary border-md-teal my-3 mb-6">
          <div class="text-white h4 py-2 px-3 m-0 bg-dark bg-md-teal text-2xl p-4">{{ ag_title }}</div>
            <div class="p-3">
              <p>{{ ag_intro }}</p>
              {{ user_affinity_groups|raw }}
              {{ affinity_link }}
            </div>
        </div>
        <div class="border border-secondary border-md-teal my-3 mb-6">
          <div class="text-white py-2 px-3 bg-dark bg-md-teal text-2xl p-4 d-flex flex align-items-center justify-content-between">
            <span class="h4 m-0 text-white">{{ ws_title }}</span>
          </div>
          <div class="p-3">
            {{ ws_links|raw }}
            {{ request_webform_link }}
          </div>
        </div>
        <div class="border border-secondary border-md-teal my-3 mb-6">
          <div class="text-white py-2 px-3 bg-dark bg-md-teal text-2xl p-4 d-flex flex align-items-center justify-content-between">
            <span class="h4 m-0 text-white">{{ match_title }}</span>
          </div>
          <div class="p-3">
            {{ match_links|raw }}
            {{ request_match_link }}
          </div>
        </div>

        {% if projects != "na" %}
          <div class="border border-secondary border-md-teal my-3 mb-6">
            <div class="text-white py-2 px-3 bg-dark bg-md-teal text-2xl p-4 d-flex flex align-items-center justify-content-between">
              <span class="h4 m-0 text-white">{{ project_title }}</span>
            </div>
            <div class="p-3">
              {{ projects|raw }}
            </div>
          </div>
        {% endif %}
        ',
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
        'project_title' => t('My Projects'),
        'projects' => $projects,
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
      // My Projects.
      $projects = $this->projectList($user, TRUE);

      $persona_page['#title'] = "$user_first_name $user_last_name";
      $persona_page['string'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="border border-secondary border-md-teal my-3 mb-6">
            <div class="text-white py-2 px-3 bg-dark bg-md-teal text-2xl p-4 d-flex flex align-items-center justify-content-between">
              <span class="h4 text-white m-0">{{ mi_title }}</span>
            </div>
            <div class="d-flex flex flex-wrap p-3">
              {{ my_interests|raw }}
            </div>
          </div>
          <div class="border border-secondary border-md-teal my-3 mb-6">
            <div class="text-white py-2 px-3 bg-dark bg-md-teal text-2xl p-4 d-flex flex align-items-center justify-content-between">
              <span class="h4 text-white m-0">{{ me_title }}</span>
            </div>
            <div class="d-flex flex flex-wrap p-3">
              {{ my_skills|raw }}
            </div>
          </div>
          <div class="border border-secondary border-md-teal my-3 mb-6">
            <div class="text-white h4 py-2 px-3 m-0 bg-dark bg-md-teal text-2xl p-4">{{ ag_title }}</div>
              <div class="p-3">
                {{ user_affinity_groups|raw }}
              </div>
          </div>
          <div class="border border-secondary border-md-teal my-3 mb-6">
            <div class="text-white py-2 px-3 bg-dark bg-md-teal text-2xl p-4 d-flex flex align-items-center justify-content-between">
              <span class="h4 m-0 text-white">{{ ws_title }}</span>
            </div>
            <div class="p-3">
              {{ ws_links|raw }}
            </div>
          </div>
          <div class="border border-secondary border-md-teal my-3 mb-6">
            <div class="text-white py-2 px-3 bg-dark bg-md-teal text-2xl p-4 d-flex flex align-items-center justify-content-between">
              <span class="h4 m-0 text-white">{{ match_title }}</span>
            </div>
            <div class="p-3">
              {{ match_links|raw }}
            </div>
          </div>

          {% if projects != "na" %}
            <div class="border border-secondary border-md-teal my-3 mb-6">
              <div class="text-white py-2 px-3 bg-dark bg-md-teal text-2xl p-4 d-flex flex align-items-center justify-content-between">
                <span class="h4 m-0 text-white">{{ project_title }}</span>
              </div>
              <div class="p-3">
                {{ projects|raw }}
              </div>
            </div>
          {% endif %}
          ',
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
          'project_title' => t('Projects'),
          'projects' => $projects,
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

  /**
   * Callback for setting the route title.
   *
   * Set the route title to the user's name if it is a public persona page.
   *
   * @return string
   *   Title to use for the route.
   */
  public function titleCallback() {
    // It's a public persona page if the url has the uid at the end.
    $end_url = new EndUrl();
    $user_id = $end_url->getUrlEnd();
    if (is_numeric($user_id)) {
      // Load the user using the user id.
      $user = User::load($user_id);
      if ($user !== NULL) {
        $user_first_name = $user->get('field_user_first_name')->value;
        $user_last_name = $user->get('field_user_last_name')->value;
        return "$user_first_name $user_last_name";
      }
    }
  }

}
