<?php

namespace Drupal\user_profiles\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\email_change_verification\EmailChangeService;
use Drupal\user_profiles\Commands\UserProfilesCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Give user option on what to do with a duplicate email account.
 *
 * @ingroup resolve_duplicate_user
 */
class ResolveDuplicateUser extends ConfigFormBase {

  /**
   * Variable for the form state.
   *
   * @var array
   */
  private $formState;

  /**
   * Call email change service.
   *
   * @var \Drupal\email_change_verification\EmailChangeService
   */
  protected $emailChangeService;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Get current user data.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'resolve_duplicate_user';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'resolve.duplicate.user',
    ];
  }

  /**
   * Constructs request stuff.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Implement messenger service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Invokes renderer.
   * @param Drupal\Core\Session\AccountProxyInterface $current_user
   *   Get current user data.
   * @param \Drupal\email_change_verification\EmailChangeService $email_change_service
   *   Calls email change service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Invokes entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(
    MessengerInterface $messenger,
    Renderer $renderer,
    AccountProxyInterface $current_user,
    EmailChangeService $email_change_service,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database
  ) {
    $this->messengerInterface = $messenger;
    $this->render = $renderer;
    $this->currentUser = $current_user;
    $this->emailChangeService = $email_change_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('messenger'),
      $container->get('renderer'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('email_change_verification.service'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get current user.
    $account = $this->currentUser;
    // Get user email.
    $name = $account->getAccountName();
    $email = $account->getEmail();
    $account_uid = $account->id();
    // Mysql query to check for duplicate email.
    $query = $this->database->select('users_field_data', 'ufd');
    $query->fields('ufd', ['uid']);
    $query->condition('ufd.mail', $email);
    $query->condition('ufd.uid', $account->id(), '<>');
    $dup_uid = $query->execute()->fetchCol();
    if (!empty($dup_uid)) {
      // User load.
      $dup_user = $this->entityTypeManager->getStorage('user');
      $dup_user = $dup_user->load($dup_uid[0]);
      $dup_last_access = $dup_user->getLastAccessedTime();
      $dup_name = $dup_user->getAccountName();
      $dup_email = $dup_user->getEmail();
      $dup_name_editable = strpos($dup_name, '@access-ci.org') === FALSE ? TRUE : FALSE;
      $current_name_editable = strpos($name, '@access-ci.org') === FALSE ? TRUE : FALSE;
      if ($dup_last_access !== "0" && ($dup_name_editable || $current_name_editable)) {
        $current_user['string'] = [
          '#type' => 'inline_template',
          '#template' => '
            <div class="border border-secondary my-3">
              <div class="text-white py-2 px-3 bg-dark d-flex align-items-center justify-content-between">
                <h3 class="text-white m-0">{{ title }}</h3>
              </div>
              <div class="p-3">
                <p>{{ name_label }}: {{ name }}</p>
                <p>{{ email_label }}: {{ email }}</p>
              </div>
            </div>
          ',
          '#context' => [
            'title' => 'Current Account',
            'name_label' => $this->t('Name'),
            'name' => $name,
            'email_label' => $this->t('Email'),
            'email' => $email,
          ],
        ];
        $form['account_uid'] = [
          '#type' => 'hidden',
          '#value' => $account_uid,
        ];

        $form['account'] = [
          '#weight' => 0,
          '#markup' => $this->render->render($current_user),
        ];
        $current_radios = [];
        if ($current_name_editable) {
          $current_radios = [
            'current_delete' => $this->t('Delete current account'),
            'current_edit_email' => $this->t('Edit current account email'),
          ];
        }
        $dup_user_account['string'] = [
          '#type' => 'inline_template',
          '#template' => '
            <div class="border border-secondary my-3">
              <div class="text-white py-2 px-3 bg-dark d-flex align-items-center justify-content-between">
                <h3>{{ title }}</h3>
              </div>
              <div class="p-3">
                <p>{{ name_label }}: {{ name }}</p>
                <p>{{ email_label }}: {{ email }}</p>
              </div>
            </div>
          ',
          '#context' => [
            'title' => $this->t('Duplicate Account'),
            'name_label' => $this->t('Name'),
            'name' => $dup_name,
            'email_label' => $this->t('Email'),
            'email' => $dup_email,
          ],
        ];

        $form['dup_account'] = [
          '#weight' => 3,
          '#markup' => $this->render->render($dup_user_account),
        ];
        $form['dup_account_uid'] = [
          '#type' => 'hidden',
          '#value' => $dup_uid[0],
        ];

        // Check if $name contains '@access-ci.org'.
        if ($dup_name_editable) {
          $dup_radios = [
            'dup_delete' => $this->t('Delete duplicate account'),
            'dup_new_email' => $this->t('Edit duplicate account email'),
          ];
          if (isset($current_radios)) {
            $radios = array_merge($current_radios, $dup_radios);
          }
          else {
            $radios = $dup_radios;
          }
        }
        else {
          $radios = $current_radios;
        }
        $form['actions'] = [
          '#type' => 'radios',
          '#title' => $this->t('Select one of the following to resolve your duplicate account.'),
          '#options' => $radios,
          '#weight' => 4,
        ];
      }
      else {
        // If someone finds themself here with no dup user, send to front.
        $response = new RedirectResponse('/');
        $response->send();
        return;
      }
    }
    else {
      $response = new RedirectResponse('/');
      $response->send();
      return;
    }
    // Replace @ symbol with +duplicate@.
    $new_email = str_replace('@', '+second-account@', $email);
    $form['new_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Add your new email'),
      '#description' => $this->t("If you don't have a new email to place here, you can use this one. The '+' symbol is like a wildcard where you can place anything after it."),
      '#default_value' => $new_email,
      '#weight' => 5,
    ];
    $form['#attached']['library'][] = 'user_profiles/duplicate';
    $form_state->setRedirect('<front>');

    $form['submit'] = [
      '#type' => 'submit',
      '#arg' => 'filter',
      '#value' => $this->t('Edit'),
      '#weight' => 9,
    ];

    return $form;
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
    $submitted_values = $form_state->getValues();
    // Validate email.
    if ($submitted_values['actions'] === 'current_edit_email' || $submitted_values['actions'] === 'dup_new_email') {
      $email = Xss::filter($submitted_values['new_email']);
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $form_state->setErrorByName('new_email', $this->t('The email address %email is not valid.', ['%email' => $email]));
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $submitted_values = $form_state->getValues();
    $user_profile_commands = new UserProfilesCommands();
    $current_user_uid = Xss::filter($submitted_values['account_uid']);
    $dup_user_uid = Xss::filter($submitted_values['dup_account_uid']);
    if ($submitted_values['actions'] === 'current_delete') {
      $current_user = $this->entityTypeManager->getStorage('user');
      $current_user = $current_user->load($current_user_uid);
      $user_profile_commands->mergeUser($current_user_uid, $dup_user_uid);
      $response = new RedirectResponse("/user/$current_user_uid/cancel");
      $response->send();
    }
    elseif ($submitted_values['actions'] === 'current_edit_email') {
      $current_user = $this->entityTypeManager->getStorage('user');
      $current_user = $current_user->load($current_user_uid);
      $current_user_new_email = Xss::filter($submitted_values['new_email']);
      $email_change_verification = $this->emailChangeService;
      $email_change_verification->changeRequest($current_user, $current_user_new_email);
      // Email change verification displays user message.
    }
    elseif ($submitted_values['actions'] === 'dup_new_email') {
      $dup_user = $this->entityTypeManager->getStorage('user');
      $dup_user = $dup_user->load(($dup_user_uid));
      $dup_user_new_email = Xss::filter($submitted_values['new_email']);
      $email_change_verification = $this->emailChangeService;
      $email_change_verification->changeRequest($dup_user, $dup_user_new_email);
      // Email change verification displays user message.
    }
    elseif ($submitted_values['actions'] === 'dup_delete') {
      $dup_user = $this->entityTypeManager->getStorage('user');
      $dup_user = $dup_user->load(Xss::filter($dup_user_uid));
      $dup_user_name = $dup_user->getAccountName();
      $user_profile_commands->mergeUser($dup_user_uid, $current_user_uid);
      $dup_user->delete();
      $this->messengerInterface->addMessage($this->t('Your account %dup_user_name has been deleted.', [
        '%dup_user_name' => $dup_user_name,
      ]));
    }
  }

}
