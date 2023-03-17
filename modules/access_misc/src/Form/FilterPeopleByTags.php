<?php

namespace Drupal\access_misc\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Component\Utility\Xss;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Drupal\Core\File\FileSystemInterface;

/**
 * Filter users by flagged skill or interest.
 *
 * @ingroup filter_people_by_tags
 */
class FilterPeopleByTags extends ConfigFormBase {

  /**
   * Store CSV data.
   *
   * @var string
   */
  private $csv;


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'filter_people_by_tags_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'filter.people.by.tags.settings',
    ];
  }

  /**
   * Use messenger interface.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messengerInterface;

  /**
   * Invoke renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $render;

  /**
   * Constructs request stuff.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Implement messenger service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Invokes renderer.
   */
  public function __construct(
    MessengerInterface $messenger,
    Renderer $renderer
  ) {
    $this->messengerInterface = $messenger;
    $this->render = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $messenger = new self(
      $container->get('messenger'),
      $container->get('renderer'),
    );
    return $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $description['string'] = [
      '#type' => 'inline_template',
      '#template' => '<p>{{ description }}</p>',
      '#context' => [
        'description' => $this->t('This form allows you to search for people by their tags.  You can select multiple tags to search for.  If you select multiple tags, the results will be people who have all of the tags you selected.'),
      ],
    ];

    $form['people_tags_description'] = [
      '#markup' => $this->render->render($description),
    ];

    $term_lookup = \Drupal::database()->select('flagging', 'f');
    $term_lookup->fields('f', ['entity_id']);
    $term_lookup->condition('f.flag_id', ['skill', 'interest'], 'IN');
    $term_lookup->distinct();
    $flagged_terms = $term_lookup->execute()->fetchAll();

    $options[0] = '- None -';
    $options[1] = '-- Any --';
    foreach ($flagged_terms as $flagged_term) {
      $tid = $flagged_term->entity_id;
      $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($tid);
      $options[$tid] = $term->label();
    }
    asort($options);

    $submitted = $form_state
      ->get('show_people');

    if ($submitted === NULL) {
      $submitted = FALSE;
    }

    $first_tags = $this->addTags($form, $form_state, $options, 'first_tags');
    $form['seperator_or'] = [
      '#markup' => $this->t('OR'),
    ];

    $second_tags = $this->addTags($form, $form_state, $options, 'second_tags');

    $form['submit'] = [
      '#type' => 'submit',
      '#arg' => 'filter',
      '#value' => $this->t('Filter'),
    ];

    $form['csv'] = [
      '#type' => 'submit',
      '#arg' => 'csv',
      '#value' => $this->t('Export CSV'),
    ];

    if ( $submitted ) {
      $this->createTable($form, $form_state, $first_tags, $second_tags);
    }

     // $this->messengerInterface->addMessage($this->render->render($dev_message));

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  private function checkTags($filter_group, $entity_ids) {
    $result = false;
    foreach ($filter_group as $filter_item) {
      if ($filter_item === '1') {
        $result = true;
      } elseif (in_array($filter_item, array_column($entity_ids, 'entity_id'))) {
        $result = true;
      } else {
        $result = false;
        break;
      }
      return $result;
    }

  }

  /**
   * Function to add another tags dropdown secton.
   */
  private function addTags(array &$form, FormStateInterface $form_state, $options, $section) {
    // Gather the number of names in the form already.
    $tags = $form_state
      ->get($section);

    // We have to ensure that there is at least one name field.
    if ($tags === NULL) {
      $form_state
        ->set($section, 1);
      $tags = 1;
    }

    $form['#tree'] = TRUE;
    $form[$section] = [
      '#type' => 'fieldset',
      '#title' => $this
        ->t('Each of the following tags are an AND inside this group.'),
      '#prefix' => '<div id="tags-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    for ($i = 0; $i < $tags; $i++) {
      $form[$section]['tag'][$i] = [
        '#type' => 'select',
        '#title' => $this->t('Tags'),
        '#options' => $options,
        '#description' => $this->t('Filter by tags'),
      ];
    }
    $form[$section]['actions'] = [
      '#type' => 'actions',
    ];
    $form[$section]['actions']['add_tag'] = [
      '#type' => 'submit',
      '#value' => $this
        ->t('Add one more'),
      '#arg' => $section,
      '#submit' => [
        '::addOne',
      ],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'tags-fieldset-wrapper',
      ],
    ];

    // If there is more than one name, add the remove button.
    if ($tags > 1) {
      $form[$section]['actions']['remove_tag'] = [
        '#type' => 'submit',
        '#value' => $this
          ->t('Remove one in first group'),
        '#arg' => $section,
        '#submit' => [
          '::removeCallback',
        ],
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => 'tags-fieldset-wrapper',
        ],
      ];
    }
    return $tags;
  }

  /**
   * Create a table of people.
   */
   private function createTable(array &$form, FormStateInterface $form_state, $first_tags, $second_tags) {
    $first_filter_group = [];
    for ($i = 0; $i < $first_tags; $i++) {
      $first_filter_group[] = Xss::filter($form_state->getValue('first_tags', 'tag', 0)['tag'][$i]);
    }
    for ($i = 0; $i < $second_tags; $i++) {
      $second_filter_group[] = Xss::filter($form_state->getValue('second_tags', 'tag', 0)['tag'][$i]);
    }
    $header = [
      'Name',
      'Institution',
      'Email',
      'Roles',
      'Tags',
    ];
    $csv_header = implode(',', $header);
    $rows = [];

    // Query the flagging table for all unique uid's.
    $query = \Drupal::database()->select('flagging', 'f');
    $query->fields('f', ['uid']);
    $query->distinct();
    $query->orderBy('f.uid', 'ASC');
    $uids = $query->execute()->fetchAll();
    $csv_rows = '';
    foreach ($uids as $uid) {
      $user = \Drupal\user\Entity\User::load($uid->uid);
      $institution = $user->get('field_institution')->getValue();
      $first_name = $user->get('field_user_first_name')->getValue();
      $last_name = $user->get('field_user_last_name')->getValue();
      $email = $user->getEmail();
      $roles = $user->getRoles();
      // Query the flagging table for entity_id for this uid.
      $query = \Drupal::database()->select('flagging', 'f');
      $query->fields('f', ['entity_id']);
      $query->condition('f.uid', $uid->uid);
      $query->orderBy('f.entity_id', 'ASC');
      $entity_ids = $query->execute()->fetchAll();
      $tags = '';
      $first = $this->checkTags($first_filter_group, $entity_ids);
      $second = $this->checkTags($second_filter_group, $entity_ids);
      if ($first || $second) {
        foreach ($entity_ids as $entity_id) {
          $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($entity_id->entity_id);
          $tags .= $term !== null ? $term->label() . ", " : '';
        }
        $tags = rtrim($tags, ', ');
        $fname = isset($first_name[0]) && $first_name[0] !== null ? $first_name[0]['value'] : '';
        $lname = isset($last_name[0]) && $last_name[0] !== null ? $last_name[0]['value'] : '';
        $inst = isset($institution[0]) && $institution[0] !== null ? $institution[0]['value'] : '';
        $csv_rows .= "\"$fname $lname\"," . "\"$inst\"," .  "\"$email\",\"" . implode(', ', $roles) . "\",\"$tags\" \n";
        $rows[] = [
          'Name' => [
            'data' => [
              '#markup' => "<a href='/user/" . $uid->uid . "'>" . $fname . " " . $lname . "</a>",
            ],
          ],
          'Institution' => [
            'data' => [
              '#markup' => $inst,
            ],
          ],
          'Email' => [
            'data' => [
              '#markup' => $email,
            ],
          ],
          'Roles' => [
            'data' => [
              '#markup' => implode(', ', $roles),
            ],
          ],
          'Tags' => [
            'data' => [
              '#markup' => $tags,
            ],
          ],
        ];
      }
    }
    $html['results'] = [
      '#theme' => 'table',
      '#sticky' => TRUE,
      '#header' => $header,
      '#rows' => $rows,
    ];

    $form['people_results'] = [
      '#markup' => $this->render->render($html),
    ];

    $this->csv = $csv_header . "\n" . $csv_rows;
   }


  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    $section = $form_state->getTriggeringElement()["#arg"];
    return $form[$section];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $section = $form_state->getTriggeringElement()["#arg"];
    $tags = $form_state
      ->get($section);
    $add_button = $tags + 1;
    $form_state
      ->set($section, $add_button);

    // Since our buildForm() method relies on the value of 'num_tags' to
    // generate 'name' form elements, we have to tell the form to rebuild. If we
    // don't do this, the form builder will not call buildForm().
    $form_state
      ->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $section = $form_state->getTriggeringElement()["#arg"];
    $tags = $form_state
      ->get($section);
    if ($tags > 1) {
      $remove_button = $tags - 1;
      $form_state
        ->set($section, $remove_button);
    }

    // Since our buildForm() method relies on the value of 'num_tags' to
    // generate 'name' form elements, we have to tell the form to rebuild. If we
    // don't do this, the form builder will not call buildForm().
    $form_state
      ->setRebuild();
  }

  /**
   * Implements form validation.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $type = $form_state->getTriggeringElement()["#arg"];
    if ($type === 'filter') {
      $form_state->set('show_people', TRUE);
      $form_state->setRebuild();
    }
    if ($type === 'csv') {
      $headers = [
        'Content-Type' => 'text/csv',
        'Content-Description' => 'File Download',
        'Content-Disposition' => 'attachment; filename=export.csv'
      ];
      // Create comma separated variable from $data.
      $first_tags = $form_state->get('first_tags');
      $second_tags = $form_state->get('second_tags');
      $this->createTable($form, $form_state, $first_tags, $second_tags);
      $csv = $this->csv;

      \Drupal::service('file_system')->saveData($csv, "/tmp/export.csv", FileSystemInterface::EXISTS_REPLACE);

      $form_state->setResponse(new BinaryFileResponse('/tmp/export.csv', 200, $headers, true));

    }
  }
}
