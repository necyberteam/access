<?php

namespace Drupal\access_cilink\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Controller for Match.
 */
class CiLinkController extends ControllerBase {

  /**
   * Variable for title.
   */
  protected $title;

  /**
   * Number for webform submission id.
   */
  protected $sid;

  /**
   * Object with webform submission.
   */
  protected $webform_submission;


  public function __construct() {
    $url = \Drupal::request()->getRequestUri();
    $url_chunked = explode('/', $url);
    $webform_submission = 0;
    if (is_numeric($url_chunked[2])){
      $this->sid = $url_chunked[2];
      $this->webform_submission = \Drupal::entityTypeManager()->getStorage('webform_submission')->load($this->sid);
    }
    else {
      $this->webform_submission = 0;
    }

    if ($this->webform_submission) {
      $title = $this->webform_submission->getData('title');
      $this->title = $title['title'];
    }
    else {
      $this->title = 'Ci Link';
    }
  }

  /**
   * Build content to display on page.
   */
  public function cilinks() {
    if (!$this->webform_submission) {
      return [
        '#markup' => $this->t('No Ci Link found.'),
      ];
    }
    $data = $this->webform_submission->getData();

    // Get tags.
    $terms = explode(',', $data['terms']);
    $ci_tagged = '';
    foreach ($terms as $term) {
      // Remove leading space.
      $term = Xss::filter(ltrim($term));
      $tagged = explode(' ', $term);
      $tag_link = Link::fromTextAndUrl($tagged[0], Url::fromUri('internal:/tags/' . $tagged[0]))->toString();
      $ci_tagged .= $tag_link->__toString() . ' ';
    }

    // Get links to resource.
    $link_data = '';
    foreach ($data['link_to_resource'] as $link) {
      $title = Xss::filter($link['title']);
      $link = Xss::filter($link['url']);
      if ($title && empty($link)) {
        $link = $title;
      }
      $options = array(
        'attributes' => ['class' => ['btn', 'btn-outline-dark', 'btn-outline-primary', 'btn-sm']],
      );
      $external_link = Link::fromTextAndUrl($title, Url::fromUri($link, $options))->toString();
      $link_data .= '<li class="p-0 my-1 mx-0">' . $external_link->__toString() . '</li>';
    }

    // Skill Level.
    $skill_level = $data['skill_level'];
    $skill_graph = '';
    if (in_array(304, $skill_level) && in_array(305, $skill_level) && !in_array(306, $skill_level)) {
      $skill_graph = '<img src="/themes/custom/accesstheme/assets/SL-beginner-medium.png" alt="Beginner and Intermediate"/>';
    }
    elseif(!in_array(304, $skill_level) && in_array(305, $skill_level) && in_array(306, $skill_level)) {
      $skill_graph = '<img src="/themes/custom/accesstheme/assets/SL-medium-advanced.png" alt="Intermediate and Advanced"/>';
    }
    elseif (in_array(304, $skill_level) && in_array(305, $skill_level) && in_array(306, $skill_level)) {
      $skill_graph = '<img src="/themes/custom/accesstheme/assets/SL-all.png" alt="Beginner, Intermediate, and Advanced"/>';
    }
    elseif (in_array(304, $skill_level)) {
      $skill_graph = '<img src="/themes/custom/accesstheme/assets/SL-beginner.png" alt="Beginner"/>';
    }
    elseif(in_array(305, $skill_level)) {
      $skill_graph = '<img src="/themes/custom/accesstheme/assets/SL-medium.png" alt="Intermediate"/>';
    }
    elseif (in_array(306, $skill_level)) {
      $skill_graph = '<img src="/themes/custom/accesstheme/assets/SL-advanced.png" alt="Advanced"/>';
    }

    // Affinity groups.
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'affinity_group')
      ->condition('field_resources_entity_reference', $this->sid);
    $nids = $query->execute();
    $affinity_nodes = '';
    if ($nids) {
      $affinity_nodes = '<h4>Affinity Group</h4>';
    }
    foreach ($nids as $nid) {
      // Get node title.
      $node = \Drupal\node\Entity\Node::load($nid);
      $title = $node->getTitle();
      $affinity_nodes .= '<a href="/node/' . $nid . '">' . $title . '</a>, ';
    }
    $affinity_nodes = rtrim($affinity_nodes, ', ');


    // Check if user is logged in.
    $user = \Drupal::currentUser();
    $options = array(
      'query' => ['destination' => '/ci-links/' . $this->sid],
      'attributes' => ['class' => ['fw-normal']],
    );
    $login = Link::fromTextAndUrl($this->t('Login to vote'), Url::fromUri('internal:/user/login', $options))->toString();

    // Upvote widget
    $flag_upvote = \Drupal::service('flag.link_builder')->build('webform_submission', $this->sid, 'upvote');
    $flag_upvote = \Drupal::service('renderer')->renderPlain($flag_upvote);
    $flag_upvote_count = \Drupal::service('flag.count')->getEntityFlagCounts($this->webform_submission);
    $flag_upvote_set = $flag_upvote_count['upvote'] ?? 0;
    $flag_upvote_count = $flag_upvote_set ? $flag_upvote_count['upvote'] : 0;

    // Flags
    $flag_outdated = \Drupal::service('flag.link_builder')->build('webform_submission', $this->sid, 'outdated');
    $flag_outdated['#attributes']['class'][] = 'dropdown-item';
    $flag_outdated = \Drupal::service('renderer')->renderPlain($flag_outdated);
    $flag_not_useful = \Drupal::service('flag.link_builder')->build('webform_submission', $this->sid, 'not_useful');
    $flag_not_useful['#attributes']['class'][] = 'dropdown-item';
    $flag_not_useful = \Drupal::service('renderer')->renderPlain($flag_not_useful);
    $flag_inaccurate = \Drupal::service('flag.link_builder')->build('webform_submission', $this->sid, 'inaccurate');
    $flag_inaccurate['#attributes']['class'][] = 'dropdown-item';
    $flag_inaccurate = \Drupal::service('renderer')->renderPlain($flag_inaccurate);

    $cilink_page['string'] = [
      '#type' => 'inline_template',
      '#attached' => [
        'library' => [
          'access_cilink/resource_view',
        ],
      ],
      '#template' => '
        <div class="container">
          <div class="row">
            <div class="col-12 col-md-8 mt-5">
              <div class="square-tags my-2">
                {{ tags | raw }}
              </div>
              <p>{{ description }}</p>
              <ul class="list-group list-unstyled">
                {{ links | raw }}
              </ul>
            </div>
            <div class="col-12 col-md-4 mt-5">
              <div class="text-dark bg-light  p-3 mb-5">
                <div class="d-flex align-items-center">
                  <div class="fs-1 ml-2 ms-2 mr-2 me-2 px30">{{ count }}</div> <span>{{ (count|render == "1") ? "Person" : "People" }} found this useful</span>
                </div>
                <span class="vote-for-ci text-decoration-none ">{{ flag_upvote }}</span>
                {% if user is same as(0) %}
                  <div class="d-flex">
                    <div class="px46"><i class="fa-light fa-right-to-bracket ms-2 ml-2"></i></div> {{ user_login | raw }}
                  </div>
                {% endif %}

              </div>

              <div class="d-flex justify-content-between">
                <div class="mb-3">
                   <h4>Category</h4>
                   {{ category }}
                </div>
                 <div class="mb-3">
                  <h4>Skill Level</h4>
                  {{ skill_graph | raw }}
                </div>
              </div>

              <div class="mt-3">
                {{ affinity_groups | raw }}
              </div>
              {% if user > 0 %}
                <div class="dropdown mt-3">
                  <a class="btn btn-outline-dark btn-outline-primary btn-sm dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    {{ report }}
                  </a>
                  <ul class="dropdown-menu">
                    <li>{{ outdated | raw }}</li>
                    <li>{{ not_useful | raw }}</li>
                    <li>{{ inaccurate | raw }}</li>
                  </ul>
                </div>
              {% endif %}
            </div>
          </div>
        </div>
      ',
      '#context' => [
        'tags' => $ci_tagged,
        'description' => Xss::filter($data['description']),
        'links' => $link_data,
        'flag_upvote' => $flag_upvote,
        'count' => $flag_upvote_count,
        'user_login' => $login,
        'category' => Xss::filter($data['category']),
        'skill_graph' => $skill_graph,
        'affinity_groups' => $affinity_nodes,
        'user' => $user->id(),
        'report' => $this->t('Report The CI Link'),
        'outdated' => $flag_outdated,
        'not_useful' => $flag_not_useful,
        'inaccurate' => $flag_inaccurate,
      ],
    ];
    return $cilink_page;
  }

  /**
   * Redirect to cilinks page.
   */
  public function cilink() {
    $response = new \Symfony\Component\HttpFoundation\RedirectResponse('/ci-links/' . $this->sid);
    $response->send();
    return [
      '#type' => 'markup',
      '#markup' => "👋 " . $this->t("You shouldn't see this."),
    ];
  }

  /**
   * Display title.
   */
  public function getTitle() {
    return $this->title;
  }

}
