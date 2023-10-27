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
      $rendered = '<h2 class="text-white-er text-xl font-semibold border-bottom pb-2 bg-dark-teal py-2 px-4">CI Links</h2>';
      $header = [
        [
          'data' => 'Title',
          'class' => [
            'border-x-0',
            'border-b',
            'border-t-0',
            'border-gray',
            'border-solid',
          ],
        ],
        [
          'data' => 'Tags',
          'class' => [
            'border-x-0',
            'border-b',
            'border-t-0',
            'border-gray',
            'border-solid',
          ],
        ],
        [
          'data' => 'Skill Level',
          'class' => [
            'border-x-0',
            'border-b',
            'border-t-0',
            'border-gray',
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
        $ci_link_name = '<div>' . \Drupal::service('renderer')->render($ci_link)->__toString() . '</div>';

        $tags = '';
        foreach ($submission_data['tags'] as $tag) {
          // Lookup tags.
          $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($tag);
          if ($term !== NULL) {
            $link = [
              '#type' => 'link',
              '#title' => $term->getName(),
              '#url' => Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $tag]),
              '#attributes' => ['class' => ['px-2', 'py-1', 'font-normal', 'no-underline', 'border', 'border-black', 'border-solid', 'hover--border-dark-teal', 'hover--text-dark-teal', 'w-fit']],
            ];
            $tags .= '<div class="mr-4 me-4 mb-2">' . \Drupal::service('renderer')->render($link)->__toString() . '</div>';
          }
        }
        $tags = '<div class="square-tags flex flex-wrap">' . $tags . '</div>';
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
          $skills = '<img class="object-contain m-0 h-auto" src="/themes/contrib/asp-theme/images/icons/SL-beginner.png" alt="Beginner">';
        }
        elseif (['Beginner', 'Intermediate'] == $skill_list) {
          $skills = '<img class="object-contain m-0 h-auto" src="/themes/contrib/asp-theme/images/icons/SL-beginner-medium.png" alt="Beginner, Intermediate">';
        }
        elseif (['Beginner', 'Intermediate', 'Advanced'] == $skill_list) {
          $skills = '<img class="object-contain m-0 h-auto" src="/themes/contrib/asp-theme/images/icons/SL-all.png" alt="Beginner, Intermediate, Advanced">';
        }
        elseif (['Intermediate', 'Advanced'] == $skill_list) {
          $skills = '<img class="object-contain m-0 h-auto" src="/themes/contrib/asp-theme/images/icons/SL-medium-advanced.png" alt="Intermediate, Advanced">';
        }
        elseif (['Advanced'] == $skill_list) {
          $skills = '<img class="object-contain m-0 h-auto" src="/themes/contrib/asp-theme/images/icons/SL-advanced.png" alt="Advanced">';
        }

        $rows[] = [
          'name' => [
            'data' => [
              '#markup' => $ci_link_name,
            ],
            'class' => [
              'border-x-0',
              'border-b',
              'border-t-0',
              'border-gray',
              'border-solid',
              'pb-4',
            ],
          ],
          'tags' => [
            'data' => [
              '#markup' => $tags,
            ],
            'class' => [
              'border-x-0',
              'border-b',
              'border-t-0',
              'border-gray',
              'border-solid',
              'pb-4',
            ],
          ],
          'skill' => [
            'data' => [
              '#markup' => $skills,
            ],
            'class' => [
              'border-x-0',
              'border-b',
              'border-t-0',
              'border-gray',
              'border-solid',
              'pb-4',
            ],
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
