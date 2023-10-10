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
    $node = $node ? $node : \Drupal::entityTypeManager()->getStorage('node')->load(327);
    // Load field_resources_entity_reference field.
    $field_resources_entity_reference = $node->get('field_resources_entity_reference')->getValue();
    // Create empty string in case the following if statement is not true.
    $rendered = '';
    if (!empty($field_resources_entity_reference)) {
      $rendered = '<h2 class="text-white-er border-bottom pb-2 bg-dark-teal py-2 px-4">CI Links</h2>';
      $header = [
        [
          'data' => 'Title',
          'class' => [
            'border-x-0',
            'border-b-2',
            'border-t-0',
            'border-black',
            'border-solid',
          ],
        ],
        [
          'data' => 'Tags',
          'class' => [
            'border-x-0',
            'border-b-2',
            'border-t-0',
            'border-black',
            'border-solid',
          ],
        ],
        [
          'data' => 'Skill Level',
          'class' => [
            'border-x-0',
            'border-b-2',
            'border-t-0',
            'border-black',
            'border-solid',
          ],
        ],
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
              '#attributes' => ['class' => ['no-underline']],
            ];
            $tags .= \Drupal::service('renderer')->render($link)->__toString();
          }
        }
        $tags = '<div class="square-tags border-2 border-black border-solid px-2 mr-2 mb-2 w-fit">' . $tags . '</div>';
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
          $skills = '<img class="object-contain m-0 h-auto" src="/themes/custom/accesstheme/assets/SL-beginner.png" alt="Beginner">';
        } elseif (['Beginner', 'Intermediate'] == $skill_list) {
          $skills = '<img class="object-contain m-0 h-auto" src="/themes/custom/accesstheme/assets/SL-beginner-medium.png" alt="Beginner, Intermediate">';
        } elseif (['Beginner', 'Intermediate', 'Advanced'] == $skill_list) {
          $skills = '<img class="object-contain m-0 h-auto" src="/themes/custom/accesstheme/assets/SL-all.png" alt="Beginner, Intermediate, Advanced">';
        } elseif (['Intermediate', 'Advanced'] == $skill_list) {
          $skills = '<img class="object-contain m-0 h-auto" src="/themes/custom/accesstheme/assets/SL-medium-advanced.png" alt="Intermediate, Advanced">';
        } elseif (['Advanced'] == $skill_list) {
          $skills = '<img class="object-contain m-0 h-auto" src="/themes/custom/accesstheme/assets/SL-advanced.png" alt="Advanced">';
        }

        $rows[] = [
          'name' => [
            'data' => [
              '#markup' => $ci_link_name,
            ],
            'class' => array(
              'border-x-0',
              'border-b-2',
              'border-t-0',
              'border-black',
              'border-solid',
            ),
          ],
          'tags' => [
            'data' => [
              '#markup' => $tags,
            ],
            'class' => array(
              'border-x-0',
              'border-b-2',
              'border-t-0',
              'border-black',
              'border-solid',
            ),
          ],
          'skill' => [
            'data' => [
              '#markup' => $skills,
            ],
            'class' => array(
              'border-x-0',
              'border-b-2',
              'border-t-0',
              'border-black',
              'border-solid',
            ),
          ],
        ];
      }

      $html['ci-links'] = [
        '#theme' => 'table',
        '#sticky' => TRUE,
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => ['id' => 'ci-links', 'class' => ['table-search border-spacing-0']],
      ];
      $rendered .= \Drupal::service('renderer')->render($html['ci-links']);
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
