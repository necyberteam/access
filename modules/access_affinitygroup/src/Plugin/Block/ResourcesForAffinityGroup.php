<?php

namespace Drupal\access_affinitygroup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\views\Views;
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
    // Load field_resources_entity_reference field.
    $field_resources_entity_reference = $node->get('field_resources_entity_reference')->getValue();
    if (!empty($field_resources_entity_reference)) {
      $rendered = '<h3 class="border-bottom pb-2">CI Links</h3>';
      $header = [
        'title' => 'CI Link',
        'description' => 'Skill Level',
        'link' => 'Tags',
      ];
      $rows = [];
      foreach ($field_resources_entity_reference as $value) {
        $webform_submission = WebformSubmission::load($value['target_id']);
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
            $link_string = \Drupal::service('renderer')->render($link)->__toString();
            $tags .= $link_string . ', ';
          }
        }
        $tags = rtrim($tags, ', ');
        // Lookup skills.
        $skills = '';
        foreach ($submission_data['skill_level'] as $skill) {
          $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($skill);
          if ($term !== NULL) {
            $skills .= $term->getName() . ', ';
          }
        }
        $skills = rtrim($skills, ', ');
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

    // Grab node id.
    $node = \Drupal::routeMatch()->getParameter('node');

    // Adding a default for layout page.
    $nid = $node ? $node->id() : 291;
    // Load Announcement view.
    $announcement_view = Views::getView('access_news');
    $announcement_view->setDisplay('block_2');
    $announcement_view->setArguments([$nid]);
    $announcement_view->execute();
    $announcement_list = $announcement_view->render();
    $rendered .= \Drupal::service('renderer')->render($announcement_list);

    return [
      ['#markup' => $rendered],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
    }
    else {
      return parent::getCacheTags();
    }
  }

  /**
   *
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
