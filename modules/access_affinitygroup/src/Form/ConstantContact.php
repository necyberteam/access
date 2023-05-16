<?php

namespace Drupal\access_affinitygroup\Form;

use Drupal\access_affinitygroup\Plugin\ConstantContactApi;
use Drupal\access_affinitygroup\Plugin\AllocationsUsersImport;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class ConstantContact.
 */
class ConstantContact extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $alloc_cron_disable = \Drupal::state()->get('access_affinitygroup.alloc_cron_disable');
    $alloc_cron_allow_ondemand = \Drupal::state()->get('access_affinitygroup.alloc_cron_allow_ondemand');

    $allocBatchBatchSize = \Drupal::state()->get('access_affinitygroup.allocBatchBatchSize');
    $allocBatchImportLimit = \Drupal::state()->get('access_affinitygroup.allocBatchImportLimit');
    $allocBatchStartAt = \Drupal::state()->get('access_affinitygroup.allocBatchStartAt');
    $allocBatchNoCC = \Drupal::state()->get('access_affinitygroup.allocBatchNoCC');
    $allocBatchNoUserDetSav = \Drupal::state()->get('access_affinitygroup.allocBatchNoUserDetSave');

    $request = \Drupal::request();
    $code = $request->get('code');
    $refresh_token = $request->get('refresh_token');

    if ($refresh_token) {
      $cca = new ConstantContactApi();
      $cca->newToken();
    }

    if ($code) {
      $cca = new ConstantContactApi();
      $cca->initializeToken($code);
    }

    $url = Url::fromUri('internal:/admin/services/constantcontact-token', ['query' => ['refresh_token' => TRUE]]);
    $link = Link::fromTextAndUrl(t('Refresh Token'), $url)->toString()->getGeneratedLink();

    $form['scope'] = [
      '#type' => 'checkboxes',
      '#options' => [
        'account_read' => $this->t('Account Read'),
        'account_update' => $this->t('Account Update'),
        'contact_data' => $this->t('Contact Data'),
        'offline_access' => $this->t('Offline Access'),
        'campaign_data' => $this->t('campaign_data'),
      ],
      '#default_value' => [
        'account_read',
        'account_update',
        'contact_data',
        'offline_access',
        'campaign_data',
      ],
      '#title' => $this->t('Scope'),
      '#description' => $this->t('Select Constant Contact permissions.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Authorize App'),
      '#submit' => [[$this, 'submitForm']],
    ];

    $form['refresh_token'] = [
      '#markup' => $link,
    ];

    $form['x1'] = [
      '#markup' => '<br><br><b>Administration Allocations Import Cron Settings</b>',
    ];

    $form['alloc_cron_disable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Allocations Import Cron'),
      '#description' => $this->t('Unchecked is the normal value.'),
      '#default_value' => $alloc_cron_disable,
    ];

    $form['alloc_cron_allow_ondemand'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow Allocation Import Cron On-Demand'),
      '#description' => $this->t('Unchecked is the normal value.'),
      '#default_value' => $alloc_cron_allow_ondemand,
    ];

    $form['savecronsettings'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Cron Settings'),
      '#submit' => [[$this, 'doSaveCronSettings']],
    ];

    $form['x2'] = [
      '#markup' => '<br><br><b>Import allocations batch processing</b><br>',
    ];

    $form['batch_param_batchsize'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch Size'),
      '#min' => 5,
      '#max' => 1000,
      '#default_value' => $allocBatchBatchSize,
      '#description' => $this->t("Size of each batch slice."),
      '#required' => FALSE,
    ];

    $form['batch_param_importlimit'] = [
      '#type' => 'number',
      '#title' => $this->t('Process limit'),
      '#default_value' => $allocBatchImportLimit,
      '#description' => $this->t("Limit allocations users processed, up to 105000."),
      '#required' => FALSE,
    ];
    $form['batch_param_startat'] = [
      '#type' => 'number',
      '#title' => $this->t('Process start at'),
      '#default_value' => $allocBatchStartAt,
      '#description' => $this->t("Start procssing nth user (default: 0)"),
      '#required' => FALSE,
    ];
    $form['batch_param_nocc'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not add users to Constant Contact.'),
      '#description' => $this->t('Unchecked is the normal value.'),
      '#default_value' => $allocBatchNoCC,
    ];
    $form['batch_param_nouserdetsave'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not save user detail changes.'),
      '#description' => $this->t('Unchecked is the normal value.'),
      '#default_value' => $allocBatchNoUserDetSav,
    ];

    $form['savebatchsettings'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Batch Settings'),
      '#submit' => [[$this, 'doSaveBatchSettings']],
    ];

    $form['runBatch'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start Batch'),
      '#description' => $this->t("Start allocations import batch processing"),
      '#submit' => [[$this, 'doBatch']],
    ];

    $form['x4'] = [
      '#markup' => '<br><br><b>Weekly Digest</b><br>',
    ];

    $form['runNewsDigest'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate Digest'),
      '#submit' => [[$this, 'doGenerateDigest']],
    ];
    $form['x5'] = [
      '#markup' => '<br><br><b>Allocations Maintenance</b><br>',
    ];

    $form['maint_sync_param_start'] = [
      '#type' => 'number',
      '#title' => $this->t('Sync Group Start'),
      '#min' => 1,
      '#max' => 1000,
      '#default_value' => 1,
      '#description' => $this->t("Start count affinity group."),
      '#required' => FALSE,
    ];
    $form['maint_sync_param_stop'] = [
      '#type' => 'number',
      '#title' => $this->t('Sync Group Stop'),
      '#min' => 1,
      '#max' => 1000,
      '#default_value' => 1000,
      '#description' => $this->t("Stop count affinity group."),
      '#required' => FALSE,
    ];

    $form['maint_sync_run'] = [
      '#type' => 'submit',
      '#value' => $this->t('Sync AG and CC'),
      '#submit' => [[$this, 'doRunMaintSync']],
    ];

    $form['maint_obsclean_param_start'] = [
      '#type' => 'number',
      '#title' => $this->t('Obsolete Clean Start'),
      '#min' => 1,
      '#max' => 20000,
      '#default_value' => 1,
      '#description' => $this->t("Start count clean obsolete allocations."),
      '#required' => FALSE,
    ];
    $form['maint_obsclean_param_stop'] = [
      '#type' => 'number',
      '#title' => $this->t('Obsolete Clean Stop'),
      '#min' => 1,
      '#max' => 20000,
      '#default_value' => 20000,
      '#description' => $this->t("Stop count clean obsolete allocations."),
      '#required' => FALSE,
    ];

    $form['maint_obsclean_run'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clean obsolete allocations'),
      '#submit' => [[$this, 'doRunMaintObsClean']],
    ];
    return $form;
  }

  /**
   * Getter method for Form ID.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'constantcontact_form';
  }

  /**
   * Implements form validation.
   *
   * @param array form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $value_check = array_filter($form_state->getValue('scope'));
    if (empty($value_check)) {
      $form_state->setErrorByName('access_affinitygroup', 'Select at least one checkbox under scope.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_scope = '';
    foreach ($form_state->getValue('scope') as $scope_value) {
      if ($scope_value !== 0) {
        $selected_scope .= $scope_value . ' ';
      }
    }
    $cc = new ConstantContactApi();
    $key = trim(\Drupal::service('key.repository')->getKey('constant_contact_client_id')->getKeyValue());
    $token = urlencode($key);
    $host = \Drupal::request()->getSchemeAndHttpHost();
    $redirectURI = urlencode("$host/admin/services/constantcontact-token");
    $scope = urlencode(rtrim($selected_scope));
    $state = uniqid();
    $response = new RedirectResponse($cc->getAuthorizationURL($token, $redirectURI, $scope, $state));
    $response->send();
    parent::submitForm($form, $form_state);
  }

  /**
   *
   */
  public function doSaveBatchSettings(array &$form, FormStateInterface $form_state) {
    \Drupal::state()->set('access_affinitygroup.allocBatchBatchSize', $form_state->getValue('batch_param_batchsize'));
    \Drupal::state()->set('access_affinitygroup.allocBatchImportLimit', $form_state->getValue('batch_param_importlimit'));
    \Drupal::state()->set('access_affinitygroup.allocBatchNoCC', $form_state->getValue('batch_param_nocc'));
    \Drupal::state()->set('access_affinitygroup.allocBatchNoUserDetSave', $form_state->getValue('batch_param_nouserdetsave'));
    \Drupal::state()->set('access_affinitygroup.allocBatchStartAt', $form_state->getValue('batch_param_startat'));
  }

  /**
   *
   */
  public function doSaveCronSettings(array &$form, FormStateInterface $form_state) {
    \Drupal::state()->set('access_affinitygroup.alloc_cron_disable', $form_state->getValue('alloc_cron_disable'));
    \Drupal::state()->set('access_affinitygroup.alloc_cron_allow_ondemand', $form_state->getValue('alloc_cron_allow_ondemand'));
  }

  /**
   * Generate the access_news weekly digest (normally run weekly via cron)
   */
  public function doGenerateDigest() {
    weeklyNewsReport(TRUE);
  }

  /**
   *
   */
  public function doBatch() {
    $aui = new AllocationsUsersImport();
    $aui->startBatch();
  }

  /**
   * Run the affinity group / constant contact membership list sync.
   */
  public function doRunMaintSync(array &$form, FormStateInterface $form_state) {
    $aui = new AllocationsUsersImport();
    $aui->syncAGandCC($form_state->getValue('maint_sync_param_start'),
                      $form_state->getValue('maint_sync_param_stop'));
  }

  /**
   * Clean out any obsolete user allocations for users no longer listed by
   * allocations api.
   */
  public function doRunMaintObsClean(array &$form, FormStateInterface $form_state) {
    $aui = new AllocationsUsersImport();
    $aui->cleanObsoleteAllocations($form_state->getValue('maint_obsclean_param_start'),
                                   $form_state->getValue('maint_obsclean_param_stop'));
  }

}
