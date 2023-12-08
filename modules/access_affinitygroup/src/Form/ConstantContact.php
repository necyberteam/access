<?php

namespace Drupal\access_affinitygroup\Form;

use Drupal\access_affinitygroup\Plugin\AllocationsUsersImport;
use Drupal\access_affinitygroup\Plugin\ConstantContactApi;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class ConstantContact.
 */
class ConstantContact extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $allocCronDisable = \Drupal::state()->get('access_affinitygroup.allocCronDisable');
    $allocCronAllowOndemand = \Drupal::state()->get('access_affinitygroup.allocCronAllowOndemand');

    $allocCronSliceSize = \Drupal::state()->get('access_affinitygroup.allocCronSliceSize'); /* def need */
    $allocCronImportLimit = \Drupal::state()->get('access_affinitygroup.allocCronImportLimit'); /* might not need */
    // This should be display only:
    $allocCronStartAt = \Drupal::state()->get('access_affinitygroup.allocCronStartAt');
    $allocCronNoCC = \Drupal::state()->get('access_affinitygroup.allocCronNoCC');
    $allocCronNoUserDetSave = \Drupal::state()->get('access_affinitygroup.allocCronNoUserDetSave');
    $allocCronVerbose = \Drupal::state()->get('access_affinitygroup.allocCronVerbose');

    $noConstantContactCalls = \Drupal::configFactory()->getEditable('access_affinitygroup.settings')->get('noConstantContactCalls');

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

    $form['y0'] = [
      '#markup' => '<h4>Set up or refresh Constant Contact Connection</h4>',
    ];

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

    $form['y2'] = [
      '#markup' => '<br><br><h4>Disable all calls to Constant Contact API</h4>',
    ];
    $form['no_constant_contact_calls'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable calls to Constant Contact API'),
      '#description' => $this->t('Uncheck for production site. '),
      '#default_value' => $noConstantContactCalls,
    ];
    $form['saveccdisable'] = [
      '#type' => 'submit',
      '#description' => $this->t('Unchecked is the normal value.'),
      '#value' => $this->t('Save Disable Setting'),
      '#submit' => [[$this, 'doSaveDisableCC']],
    ];
    $form['y5'] = [
      '#markup' => '<br><br><h4>Generate Weekly Digest</h4>',
    ];

    $form['i5'] = [
      '#markup' => 'Campaign created in Constant Contact, but not sent.<br>',
    ];

    $form['runNewsDigest'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate Digest'),
      '#submit' => [[$this, 'doGenerateDigest']],
    ];

    /* MANUAL IMPORT ALLOCATIONS BATCH */

    $form['y3'] = [
      '#markup' => '<br><br><h4>Administration Allocations Import Cron Settings</h4>',
    ];

    $form['alloc_cron_disable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Allocations Import Cron'),
      '#description' => $this->t('Unchecked is the normal value.'),
      '#default_value' => $allocCronDisable,
    ];

    /* prob don't need this one */
    $form['alloc_cron_allow_ondemand'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow Allocation Import Cron On-Demand'),
      '#description' => $this->t('Unchecked is the normal value.'),
      '#default_value' => $allocCronAllowOndemand,
    ];

    $form['alloc_cron_param_slicesize'] = [
      '#type' => 'number',
      '#title' => $this->t('Slice Size'),
      '#default_value' => $allocCronSliceSize,
      '#description' => $this->t("Number to process on on each cron run."),
      '#required' => FALSE,
    ];
    /* We prob won't use this one: */
    $form['alloc_cron_param_importlimit'] = [
      '#type' => 'number',
      '#title' => $this->t('Process limit'),
      '#default_value' => $allocCronImportLimit,
      '#description' => $this->t("Limit number of users processed."),
      '#required' => FALSE,
    ];
    $form['alloc_cron_param_startat'] = [
      '#type' => 'number',
      '#title' => $this->t('Process start at'),
      '#default_value' => $allocCronStartAt,
      '#description' => $this->t("Start procssing nth user (default: 0)"),
      '#required' => FALSE,
    ];
    $form['alloc_cron_param_nocc'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not add users to Constant Contact.'),
      '#description' => $this->t('Unchecked is the normal value.'),
      '#default_value' => $allocCronNoCC,
    ];
    $form['alloc_cron_param_nouserdetsave'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not save user detail changes.'),
      '#description' => $this->t('Unchecked is the normal value.'),
      '#default_value' => $allocCronNoUserDetSave,
    ];
    $form['alloc_cron_param_verbose'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Verbose logging'),
      '#default_value' => $allocCronVerbose,
      '#required' => FALSE,
    ];

    $form['savecronsettings'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Cron Settings'),
      '#submit' => [[$this, 'doSaveCronSettings']],
    ];

    $form['runCron'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run Cron Slice'),
      '#description' => $this->t("Dev test: Run one cron slice "),
      '#submit' => [[$this, 'doCronSlice']],
    ];

    /* MANUAL IMPORT ALLOCATIONS BATCH */

    $form['y4'] = [
      '#markup' => '<br><br><h4>Run an import allocations batch</h4>',
    ];

    $form['batch_param_batchsize'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch Size'),
      '#min' => 5,
      '#max' => 1000,
      '#default_value' => 100,
      '#description' => $this->t("Size of each batch slice."),
      '#required' => FALSE,
    ];

    $form['batch_param_importlimit'] = [
      '#type' => 'number',
      '#title' => $this->t('Process limit'),
      '#default_value' => 5000,
      '#description' => $this->t("Limit number of users processed."),
      '#required' => FALSE,
    ];
    $form['batch_param_startat'] = [
      '#type' => 'number',
      '#title' => $this->t('Process start at'),
      '#default_value' => 0,
      '#description' => $this->t("Start procssing nth user (default: 0)"),
      '#required' => FALSE,
    ];
    $form['batch_param_nocc'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not add users to Constant Contact.'),
      '#description' => $this->t('Unchecked is the normal value.'),
      '#default_value' => TRUE,
    ];
    $form['batch_param_nouserdetsave'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not save user detail changes.'),
      '#description' => $this->t('Unchecked is the normal value.'),
      '#default_value' => TRUE,
    ];
    $form['batch_param_verbose'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Verbose logging'),
      '#default_value' => FALSE,
      '#required' => FALSE,
    ];

    $form['runBatch'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start Batch'),
      '#description' => $this->t("Start allocations import batch processing"),
      '#submit' => [[$this, 'doBatch']],
    ];

    /* SYNC */

    $form['y6'] = [
      '#markup' => '<br><br><h4>Sync Constant Constact Lists</h4>',
    ];
    $form['i6'] = [
      '#markup' => 'This process checks each Affinity Group membership, and associated <br>
                    Constant Contact list, and then adds missing AG members to CC list.',
    ];

    $form['maint_sync_param_start'] = [
      '#type' => 'number',
      '#min' => 1,
      '#max' => 1000,
      '#default_value' => 1,
      '#description' => $this->t("Start count affinity groups."),
      '#required' => FALSE,
    ];
    $form['maint_sync_param_stop'] = [
      '#type' => 'number',
      '#min' => 1,
      '#max' => 1000,
      '#default_value' => 1000,
      '#description' => $this->t("Stop count affinity groups."),
      '#required' => FALSE,
    ];
    $form['maint_sync_param_verbose'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Verbose logging'),
      '#default_value' => FALSE,
      '#required' => FALSE,
    ];
    $form['maint_sync_run'] = [
      '#type' => 'submit',
      '#value' => $this->t('Sync AG and CC'),
      '#submit' => [[$this, 'doRunMaintSync']],
    ];

    $form['y7'] = [
      '#markup' => '<br><br><h4>Clean up obsolete allocations for users</h4>',
    ];
    $form['i7'] = [
      '#markup' => 'This process compares user current allocations according allocations api,<br>
                   and removes obsolete CiDeR allocations from user profiles.',
    ];

    $form['maint_obsclean_param_start'] = [
      '#type' => 'number',
      '#min' => 1,
      '#max' => 20000,
      '#default_value' => 1,
      '#description' => $this->t("Start count clean obsolete allocations."),
      '#required' => FALSE,
    ];
    $form['maint_obsclean_param_stop'] = [
      '#type' => 'number',
      '#min' => 1,
      '#max' => 20000,
      '#default_value' => 20000,
      '#description' => $this->t("Stop count clean obsolete allocations."),
      '#required' => FALSE,
    ];
    $form['maint_obsclean_param_verbose'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Verbose logging'),
      '#default_value' => FALSE,
      '#required' => FALSE,
    ];
    $form['maint_obsclean_run'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clean allocations'),
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
   * Save config setting access_affinitygroup.settings.noConstantContactCalls according to checkbox  value.
   */
  public function doSaveDisableCC(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('access_affinitygroup.settings');
    $config->set('noConstantContactCalls', $form_state->getValue('no_constant_contact_calls'));
    $config->save();
  }

  /**
   * Save state variables for running and testing the allocations import cron job according to checkbox values.
   */
  public function doSaveCronSettings(array &$form, FormStateInterface $form_state) {
    \Drupal::state()->set('access_affinitygroup.allocCronDisable', $form_state->getValue('alloc_cron_disable'));
    \Drupal::state()->set('access_affinitygroup.allocCronAllowOndemand', $form_state->getValue('alloc_cron_allow_ondemand'));

    \Drupal::state()->set('access_affinitygroup.allocCronSliceSize', $form_state->getValue('alloc_cron_param_slicesize'));
    \Drupal::state()->set('access_affinitygroup.allocCronImportLimit', $form_state->getValue('alloc_cron_param_importlimit'));
    \Drupal::state()->set('access_affinitygroup.allocCronNoCC', $form_state->getValue('alloc_cron_param_nocc'));
    \Drupal::state()->set('access_affinitygroup.allocCronNoUserDetSave', $form_state->getValue('alloc_cron_param_nouserdetsave'));
    \Drupal::state()->set('access_affinitygroup.allocCronStartAt', $form_state->getValue('alloc_cron_param_startat'));
    \Drupal::state()->set('access_affinitygroup.allocCronVerbose', $form_state->getValue('alloc_cron_param_verbose'));
  }

  /**
   * Generate the access_news weekly digest (normally run weekly via cron)
   */
  public function doGenerateDigest() {
    weeklyNewsReport(TRUE);
  }

  /**
   * Set off the allocations import with the batch api.
   */
  public function doBatch(array &$form, FormStateInterface $form_state) {
    $aui = new AllocationsUsersImport();
    $aui->startBatch(
      $form_state->getValue('batch_param_batchsize'),
      $form_state->getValue('batch_param_importlimit'),
      $form_state->getValue('batch_param_nocc'),
      $form_state->getValue('batch_param_nouserdetsave'),
      $form_state->getValue('batch_param_startat'),
      $form_state->getValue('batch_param_verbose')
    );
  }
  /**
   * Dev - if run cron on demand needed, uncomment run cron button
   */
  public function doCronSlice() {
    $aui = new AllocationsUsersImport();
    $aui->runCronSlice();
  }
  /**
   * Run the affinity group / constant contact membership list sync.
   */
  public function doRunMaintSync(array &$form, FormStateInterface $form_state) {
    $aui = new AllocationsUsersImport();
    $aui->syncAGandCC(
      $form_state->getValue('maint_sync_param_start'),
      $form_state->getValue('maint_sync_param_stop'),
      $form_state->getValue('maint_sync_param_verbose')
    );
  }

  /**
   * Clean out any obsolete user allocations for users no longer listed by
   * allocations api.
   */
  public function doRunMaintObsClean(array &$form, FormStateInterface $form_state) {
    $aui = new AllocationsUsersImport();
    $aui->cleanObsoleteAllocations(
      $form_state->getValue('maint_obsclean_param_start'),
      $form_state->getValue('maint_obsclean_param_stop'),
      $form_state->getValue('maint_obsclean_param_verbose')
    );
  }

}
