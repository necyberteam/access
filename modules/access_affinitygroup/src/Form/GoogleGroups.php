<?php

namespace Drupal\access_affinitygroup\Form;

use Drupal\access_affinitygroup\Plugin\ConstantContactApi;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Class GoogleGroups.
 */
class GoogleGroups extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $client = new \Google\Client();
    $client->setAuthConfig('sites/default/files/private/.keys/client_secret_109355002412-0tmn5qs0diarrlosmiriqgurvt9okf2j.apps.googleusercontent.com.json');
    $client->addScope(\Google\Service\GroupsMigration::APPS_GROUPS_MIGRATION);
    $redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . '/admin/services/googlegroups-token';
    $client->setRedirectUri($redirect_uri);
    $client->setAccessType('offline');
    //$client->setIncludeGrantedScopes(true);
    $auth_url = $client->createAuthUrl();
    if (isset($_GET['code'])) {
      $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
      kint($token);
    } else {
      return new TrustedRedirectResponse($auth_url);
    }

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
    ];

    $form['refresh_token'] = [
      '#markup' => $link,
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
   * @param array $form
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

}
