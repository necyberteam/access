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
    $request = \Drupal::request();
    $code = $request->get('code');

    if ($code) {
      $key = trim(\Drupal::service('key.repository')->getKey('constant_contact')->getKeyValue());
      $clientId = urlencode($key);
      $key_secret = trim(\Drupal::service('key.repository')->getKey('constant_contact_client_secret')->getKeyValue());
      $clientSecret = urlencode($key_secret);
      $host = \Drupal::request()->getSchemeAndHttpHost();
      $redirectURI = urlencode("$host/admin/services/constantcontact-token");

      $returned_token = $this->getAccessToken($redirectURI, $clientId, $clientSecret, $code);
      $returned_token = json_decode($returned_token);

      if ( !isset($returned_token->error) ) {
        $config_factory = \Drupal::configFactory();
        $config = $config_factory->getEditable('constantcontact.settings');
        $config->set('access_token', $returned_token->access_token);
        $config->set('refresh_token', $returned_token->refresh_token);
        $config->save();
        \Drupal::logger('access_affinitygroup')->notice("Constant Contact: new access_token and refresh_token stored");
        \Drupal::messenger()->addMessage("Constant Contact: new access_token and refresh_token stored");
      } else {
        \Drupal::logger('access_affinitygroup')->error("$returned_token->error: $returned_token->error_description");
        \Drupal::messenger()->addMessage("$returned_token->error: $returned_token->error_description", 'error');
      }
    }

    $form['scope'] = array(
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
    );

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Authorize App'),
    ];

    return $form;
  }

  /*
  * This function can be used to exchange an authorization code for an access token.
  * Make this call by passing in the code present when the account owner is redirected back to you.
  * The response will contain an 'access_token' and 'refresh_token'
  */

  /**
   * @param $redirectURI - URL Encoded Redirect URI
   * @param $clientId - API Key
   * @param $clientSecret - API Secret
   * @param $code - Authorization Code
   * @return string - JSON String of results
   */

  private function getAccessToken($redirectURI, $clientId, $clientSecret, $code) {
    // Use cURL to get access token and refresh token
    $ch = curl_init();

    // Define base URL
    $base = 'https://authz.constantcontact.com/oauth2/default/v1/token';

    // Create full request URL
    $url = $base . '?code=' . $code . '&redirect_uri=' . $redirectURI . '&grant_type=authorization_code';
    curl_setopt($ch, CURLOPT_URL, $url);

    // Set authorization header
    // Make string of "API_KEY:SECRET"
    $auth = $clientId . ':' . $clientSecret;
    // Base64 encode it
    $credentials = base64_encode($auth);
    // Create and set the Authorization header to use the encoded credentials, and set the Content-Type header
    $authorization = 'Authorization: Basic ' . $credentials;
    curl_setopt($ch, CURLOPT_HTTPHEADER, array($authorization, 'Content-Type: application/x-www-form-urlencoded'));

    // Set method and to expect response
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Make the call
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
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
    $selected_scope = '';
    foreach ($form_state->getValue('scope') as $scope_value) {
      if ( $scope_value !== 0 ) {
        $selected_scope .= $scope_value . ' ';
      }
    }
    $cc = new ConstantContactApiAuth;
    $key = trim(\Drupal::service('key.repository')->getKey('constant_contact')->getKeyValue());
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
