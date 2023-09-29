<?php

namespace Drupal\access_affinitygroup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Displays Resources for Affinity Group in layout.
 *
 * @Block(
 *   id = "resources_for_affinity_group",
 *   admin_label = "Resources for Affinity Group view",
 * )
 */
class ResourcesForAffinityGroup extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    $node = $node ? $node : \Drupal::entityTypeManager()->getStorage('node')->load(327);
    // Load field_resources_entity_reference field.
    $field_resources_entity_reference = $node->get('field_resources_entity_reference')->getValue();
    // Create empty string in case the following if statement is not true.
    $rendered = '';
    if (!empty($field_resources_entity_reference)) {
      $rendered = '<h3 class="border-bottom pb-2">CI Links</h3>';
      $header = [
        'title' => 'Title',
        'description' => 'Skill Level',
        'link' => 'Tags',
      ];
      $rows = [];
      foreach ($field_resources_entity_reference as $value) {
        $webform_submission = WebformSubmission::load($value['target_id']);
        if (!$webform_submission) {
          // Webform submissions are sanitized in dev enviroments.
          continue;
        }
        $submission_data = $webform_submission->getData();

        // Ci link name and url.
        $ci_link = [
          '#type' => 'link',
          '#title' => $submission_data['title'],
          '#url' => Url::fromUri('internal:/ci-link/' . $value['target_id']),
        ];
        $ci_link_name = \Drupal::service('renderer')->render($ci_link)->__toString();

        $tags = '';
        foreach ($submission_data['tags'] as $tag) {
          // Lookup tags.
          $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($tag);
          if ($term !== NULL) {
            $link = [
              '#type' => 'link',
              '#title' => $term->getName(),
              '#url' => Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $tag]),
            ];
            $tags .= \Drupal::service('renderer')->render($link)->__toString();
          }
        }
        $tags = '<div class="square-tags">' . $tags . '</div>';
        // Lookup skills by id and make an array of names.
        $skills = '';
        $skill_list = [];
        foreach ($submission_data['skill_level'] as $skill) {
          $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($skill);
          if ($term !== NULL) {
            array_push($skill_list, $term->getName());
          }
        }
        if (['Beginner'] == $skill_list) {
          $skills = '<img src="/themes/custom/accesstheme/assets/SL-beginner.png" alt="Beginner">';
        }
        elseif (['Beginner', 'Intermediate'] == $skill_list) {
          $skills = '<img src="/themes/custom/accesstheme/assets/SL-beginner-medium.png" alt="Beginner, Intermediate">';
        }
        elseif (['Beginner', 'Intermediate', 'Advanced'] == $skill_list) {
          $skills = '<img src="/themes/custom/accesstheme/assets/SL-all.png" alt="Beginner, Intermediate, Advanced">';
        }
        elseif (['Intermediate', 'Advanced'] == $skill_list) {
          $skills = '<img src="/themes/custom/accesstheme/assets/SL-medium-advanced.png" alt="Intermediate, Advanced">';
        }
        elseif (['Advanced'] == $skill_list) {
          $skills = '<img src="/themes/custom/accesstheme/assets/SL-advanced.png" alt="Advanced">';
        }

        $rows[] = [
          'name' => [
            'data' => [
              '#markup' => $ci_link_name,
            ],
          ],
          'skill' => [
            'data' => [
              '#markup' => $skills,
            ],
          ],
          'tags' => [
            'data' => [
              '#markup' => $tags,
            ],
          ],
        ];
      }

      $html['ci-links'] = [
        '#theme' => 'table',
        '#sticky' => TRUE,
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => ['id' => 'ci-links', 'class' => ['table-search']],
      ];
      $rendered .= \Drupal::service('renderer')->render($html['ci-links']);
    }

    /**
   * Grab node id.
   */
    $node = \Drupal::routeMatch()->getParameter('node');

    /**
   * Adding a default for layout page.
   */
    $nid = $node ? $node->id() : 291;
    // Entity query to get access_news nodes that have a field_affinity_group_node field that references $nid.
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'access_news')
      ->condition('status', 1)
      ->condition('field_affinity_group_node', $nid, '=')
      ->sort('created', 'DESC');
    $nids = $query->execute();
    // Get the field_affinity_announcements field from $nid.
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    $field_affinity_announcements = $node->get('field_affinity_announcements')->getValue();
    foreach ($field_affinity_announcements as $ann_nid) {
      $nids[] = $ann_nid['target_id'];
    }

    $announcements = [];
    // Get titles and dates and place into an array.
    foreach ($nids as $anid) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($anid);
      $title = $node->getTitle();
      $link = [
        '#type' => 'link',
        '#title' => $title,
        '#url' => Url::fromUri('internal:/node/' . $anid),
      ];
      $link_name = \Drupal::service('renderer')->render($link)->__toString();
      $published_date = $node->get('field_published_date')->getValue();
      $published_date = $published_date[0]['value'];
      $announcements[$anid] = [
        'link' => $link_name,
        'date' => $published_date,
      ];
    }

    // Sort announcements by date.
    usort($announcements, fn($b, $a) => $a['date'] <=> $b['date']);

    // Set announcements title.
    $rendered .= '<h3 class="border-bottom pb-2">Announcements</h3>';

    if (empty($nids)) {
      $rendered .= '<div class="alert alert-warning">
        <p>There are no announcements at this time. Please check back later or visit the <a href="/announcements">Announcements</a> page.</p>
      </div>';
    }

    foreach ($announcements as $announcement) {
      // Format $date.
      $title = $announcement['link'];
      $create_date = date_create($announcement['date']);
      $adate = date_format($create_date, "m-d-Y");
      $rendered .= '<div class="announcement mb-3"><span class="announcement-title">' . $title . '</span> <span class="announcement-date">[' . $adate . ']</span></div>';
    }

    return [
      ['#markup' => $rendered],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['affinity_group_ci_links']);
  }

  /**
   *
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
