<?php

namespace Drupal\access_affinitygroup\Form;

use Drupal\access_affinitygroup\Plugin\ConstantContactApiAuth;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class ConstantContact.
 */
class ConstantContact extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['scope'] = array(
      '#type' => 'checkboxes',
      '#options' => [
        'account_read' => $this->t('Account Read'),
        'account_update' => $this->t('Account Update'),
        'contact_data' => $this->t('Contact Data'),
        'offline_access' => $this->t('Offline Access'),
        'campaign_data' => $this->t('campaign_data'),
      ],
      '#title' => $this->t('Scope'),
      '#description' => $this->t('Select Constant Contact permissions.'),
    );

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Authorize App'),
    ];

    //$cc = new ConstantContactApiAuth;

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
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Probably don't need error check...
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cc = new ConstantContactApiAuth;
    $token = urlencode('token here');
    $redirectURI = urlencode('https://localhost');
    $scope = urlencode('account_read account_update contact_data offline_access campaign_data');
    $state = uniqid();
    $response = new RedirectResponse($cc->getAuthorizationURL($token, $redirectURI, $scope, $state));
    $response->send();
    parent::submitForm($form, $form_state);
  }

}
