<?php

namespace Drupal\user_profiles\Plugin\OpenIDConnectClient;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientBase;
use Symfony\Component\HttpFoundation\Response;


// bad_php;

/**
 * ACCESS OpenID Connect client.
 *
 * Implements OpenID Connect Client plugin for ACCESS.
 *
 * @OpenIDConnectClient(
 *   id = "ACCESS",
 *   label = @Translation("ACCESS")
 * )
 */
class OpenIDConnectAccessClient extends OpenIDConnectClientBase {

  /**
   * A mapping of OpenID Connect user claims to ACCESS user properties.
   *
   * @var array
   */
  protected $userInfoMapping = [
    'first_name' => 'given_name',
    'last_name' => 'family_name',
    'mail' => 'email',
    'name' => 'eppn',
    'idp_name' => 'idp_name',
  ];

  /*
⧉⌕$context array (6)
⇄⧉tokens => array (3)
⇄plugin_id => string (6) "access"
⇄⧉user_data => array (17)
⇄email => string (17) "andrew@elytra.net"
⇄given_name => string (6) "Andrew"
⇄family_name => string (8) "Pasquale"
⇄name => string (15) "Andrew Pasquale"
⇄cert_subject_dn => string (58) "/DC=org/DC=cilogon/C=US/O=ACCESS/CN=Andrew Pasquale E17924"
⇄idp => string (25) "https://access-ci.org/idp"
⇄idp_name => string (6) "ACCESS"
⇄eppn => string (23) "apasquale@access-ci.org"
⇄⧉eptid => string (85) "https://access-ci.org/idp!https://cilogon.org/shibboleth!cSP9oLg+zSyblaC5VPE...
⇄acr => string (30) "https://refeds.org/profile/mfa"
⇄iss => string (19) "https://cilogon.org"
⇄sub => string (38) "http://cilogon.org/serverE/users/17924"
⇄aud => string (51) "cilogon:/client_id/6e6bcf2881796b725f80c26ad05b3e3c"
⇄⧉jti => string (80) "https://cilogon.org/oauth2/idToken/bc25f11a880aa209970eeded6e63a09/165902670...
⇄⧉auth_time => integer 1659026681
⇄⧉exp => integer 1659027605
⇄⧉iat => integer 1659026705
⇄userinfo => null
⇄sub => string (38) "http://cilogon.org/serverE/users/17924"
⇄account => boolean false
⧉Called from <ROOT>/modules/contrib/openid_connect/src/OpenIDConnect.php:335 [kint()]
Redirecting to /user.

  */

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $url = 'https://github.com/settings/developers';
    $form['description'] = [
      '#markup' => '<div class="description">' . $this->t('Set up your app in <a href="@url" target="_blank">developer applications</a> on GitHub.', ['@url' => $url]) . '</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpoints(): array {
    return [
      'authorization' => 'https://cilogon.org/authorize',
      'token' => 'https://cilogon.org/oauth2/token',
      'userinfo' => 'https://cilogon.org/oauth2/userinfo',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function authorize_orig(string $scope = 'openid email', array $additional_params = []): Response {
    // Use ACCESS specific authorisations.
    return parent::authorize('openid email profile org.cilogon.userinfo');
  }


  /**
   * {@inheritdoc}
   */
  public function authorize(string $scope = 'openid email', array $additional_params = []): Response {
    // Use ACCESS specific authorisations.
    $authorize_result = parent::authorize('openid email profile org.cilogon.userinfo');
    \Drupal::messenger()->addStatus(basename(__FILE__) . ':' . __LINE__ . ' -- ' . '$authorize_result = ' . print_r($authorize_result, true));
    return $authorize_result;
  }

  public function openid_connect_post_authorize() {
    \Drupal::messenger()->addStatus(basename(__FILE__) . ':' . __LINE__ . ' -- ' . '$this->configuration = ' . print_r($this->configuration, true));

  }

    /**
   * {@inheritdoc}
   */
  public function getClientScopes(): ?array {
    \Drupal::messenger()->addStatus(basename(__FILE__) . ':' . __LINE__ . ' -- ' . '$this->configuration = ' . print_r($this->configuration, true));
    return $this->configuration['scopes'];
  }
  /**
   * {@inheritdoc}
   */
  // public function retrieveUserInfo(string $access_token): ?array {

  //   $response_data = parent::retrieveUserInfo($access_token);

  //   \Drupal::messenger()->addStatus(basename(__FILE__) . ':' . __LINE__ . ' -- ' . 'retrieveUserInfo $response_data = ' . print_r($parent_response, true));

  //   $claims = [];

  //   foreach ($this->userInfoMapping as $claim => $key) {
  //     if (array_key_exists($key, $response_data)) {
  //       $claims[$claim] = $response_data[$key];
  //     }
  //   }

  //   \Drupal::messenger()->addStatus(basename(__FILE__) . ':' . __LINE__ . ' -- ' . 'retrieveUserInfo $claims = ' . print_r($claims, true));
  //   return $claims;
  // }

    
  // public function retrieveUserInfo(string $access_token): ?array {

  //   // this doesn't work because Authorization value needs to start with "Bearer " 
  //   // $request_options = [
  //   //   'headers' => [
  //   //     'Authorization' => 'token ' . $access_token,
  //   //     'Accept' => 'application/json',
  //   //   ],
  //   // ];

  //   $request_options = [
  //     'headers' => [
  //       'Authorization' => 'Bearer ' . $access_token,
  //       'Accept' => 'application/json',
  //     ],
  //   ];
  //   $endpoints = $this->getEndpoints();
    
  //   $client = $this->httpClient;
  //   try {
  //     $claims = [];
  //     $response = $client->get($endpoints['userinfo'], $request_options);
  //     $response_data = Json::decode((string) $response->getBody());

  //     \Drupal::messenger()->addStatus(basename(__FILE__) . ':' . __LINE__ . ' -- ' . 'userinfo $response_data = ' . print_r($response_data, true));
      
  //     // $response_data = Array ( 
  //     //   [sub] => http://cilogon.org/serverE/users/17924 
  //     //   [idp_name] => ACCESS 
  //     //   [eppn] => apasquale@access-ci.org 
  //     //   [cert_subject_dn] => /DC=org/DC=cilogon/C=US/O=ACCESS/CN=Andrew Pasquale E17924 
  //     //   [eptid] => https://access-ci.org/idp!https://cilogon.org/shibboleth!cSP9oLg+zSyblaC5VPEo7XkeJW4= 
  //     //   [iss] => https://cilogon.org 
  //     //   [given_name] => Andrew 
  //     //   [acr] => https://refeds.org/profile/mfa 
  //     //   [aud] => cilogon:/client_id/6e6bcf2881796b725f80c26ad05b3e3c 
  //     //   [idp] => https://access-ci.org/idp 
  //     //   [name] => Andrew Pasquale 
  //     //   [family_name] => Pasquale 
  //     //   [email] => andrew@elytra.net 
  //     //   [jti] => https://cilogon.org/oauth2/idToken/6ad2fd573244c4a19e346fd1e9852864/1659102835602 ) 

  //     foreach ($this->userInfoMapping as $claim => $key) {
  //       kint($key);
  //       if (array_key_exists($key, $response_data)) {
  //         $claims[$claim] = $response_data[$key];
  //       }
  //     }

  //     // GitHub names can be empty. Fall back to the login name.
  //     // if (empty($claims['name']) && isset($response_data['login'])) {
  //     //   $claims['name'] = $response_data['login'];
  //     // }

  //     // Convert the updated_at date to a timestamp.
  //     if (!empty($response_data['updated_at'])) {
  //       $claims['updated_at'] = strtotime($response_data['updated_at']);
  //     }

  //     // The email address is only provided in the User resource if the user has
  //     // chosen to display it publicly. So we need to make another request to
  //     // find out the user's email address(es).
  //     // if (empty($claims['email'])) {
  //     //   $email_response = $client->get($endpoints['userinfo'] . '/emails', $request_options);
  //     //   $email_response_data = Json::decode((string) $email_response->getBody());

  //     //   foreach ($email_response_data as $email) {
  //     //     // See https://developer.github.com/v3/users/emails/
  //     //     if (!empty($email['primary'])) {
  //     //       $claims['email'] = $email['email'];
  //     //       $claims['email_verified'] = $email['verified'];
  //     //       break;
  //     //     }
  //     //   }
  //     // }
  //     kint($claims);
      
  //     return $claims;
  //   }
  //   catch (\Exception $e) {
  //     $variables = [
  //       '@message' => 'Could not retrieve user profile information',
  //       '@error_message' => $e->getMessage(),
  //     ];
  //     $this->loggerFactory->get('openid_connect_' . $this->pluginId)
  //       ->error('@message. Details: @error_message', $variables);
  //   }
  //   return NULL;
  // }

}
