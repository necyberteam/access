<?php

namespace Drupal\access_affinitygroup\Plugin;


/**
 * Make Constant Contact api call.
 */
class ConstantContactApi {

  /**
   * Return constantcontact.settings config.
   */
  private $configSettings;

  /**
   * Return access_token.
   */
  private $accessToken ;

  /**
   * Return refresh_token.
   */
  private $refreshToken;

  /**
   * Return clientId.
   */
  private $clientId;

  /**
   * Return clientSecret.
   */
  private $clientSecret;

  /**
   * Function to sort the curl headers.
   */
  public function __construct() {
    $config_factory = \Drupal::configFactory();
    $this->configSettings = $config_factory->getEditable('constantcontact.settings');
    $this->accessToken = $this->configSettings->get('access_token');
    $this->refreshToken = $this->configSettings->get('refresh_token');
    $cc_key = trim(\Drupal::service('key.repository')->getKey('constant_contact_client_id')->getKeyValue());
    $this->clientId = urlencode($cc_key);
    $key_secret = trim(\Drupal::service('key.repository')->getKey('constant_contact_client_secret')->getKeyValue());
    $this->clientSecret = urlencode($key_secret);
  }

  /**
   * @param $redirectURI - URL Encoded Redirect URI
   * @param $clientId - API Key
   * @param $scope - URL encoded, plus sign delimited list of scopes that your
   * application requires. The 'offline_access' scope needed to request a
   * refresh token is added by default.
   * @param $state - Arbitrary string value(s) to verify response and preserve
   * application state
   * @return string - Full Authorization URL
   */

  public function getAuthorizationURL($clientId, $redirectURI, $scope, $state) {
    // Create authorization URL
    $baseURL = "https://authz.constantcontact.com/oauth2/default/v1/authorize";
    $authURL = $baseURL . "?client_id=" . $clientId . "&scope=" . $scope . "+offline_access&response_type=code&state=" . $state . "&redirect_uri=" . $redirectURI;

    return $authURL;

  }

  /**
   * Initialize Constant Contact Token.
   */
  public function initializeToken($code) {
    $clientId = $this->clientId;
    $clientSecret = $this->clientSecret;
    $host = \Drupal::request()->getSchemeAndHttpHost();
    $redirectURI = urlencode("$host/admin/services/constantcontact-token");

    $returned_token = $this->getAccessToken($redirectURI, $clientId, $clientSecret, $code);
    $returned_token = json_decode($returned_token);

    if ( !isset($returned_token->error) ) {
      $this->setAccessToken($returned_token->access_token);
      $this->setRefreshToken($returned_token->refresh_token);
      \Drupal::logger('access_affinitygroup')->notice("Constant Contact: new access_token and refresh_token stored");
      \Drupal::messenger()->addMessage("Constant Contact: new access_token and refresh_token stored");
    } else {
      $this->apiError($returned_token->error, $returned_token->error_description);

    }
  }

  /**
   * Refresh Constant Contact Token and set new ones.
   */
  public function newToken() {
    $refreshToken = $this->refreshToken;
    $clientId = $this->clientId;
    $clientSecret = $this->clientSecret;
    // Use cURL to get a new access token and refresh token
    $ch = curl_init();

    // Define base URL
    $base = 'https://authz.constantcontact.com/oauth2/default/v1/token';

    // Create full request URL
    $url = $base . '?refresh_token=' . $refreshToken . '&grant_type=refresh_token';
    curl_setopt($ch, CURLOPT_URL, $url);

    // Set authorization header
    // Make string of "API_KEY:SECRET"
    $auth = $clientId . ':' . $clientSecret;
    // Base64 encode it
    $credentials = base64_encode($auth);
    // Create and set the Authorization header to use the encoded credentials,
    // and set the Content-Type header
    $authorization = 'Authorization: Basic ' . $credentials;
    curl_setopt($ch, CURLOPT_HTTPHEADER, array($authorization, 'Content-Type: application/x-www-form-urlencoded'));

    // Set method and to expect response
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Make the call
    $result = curl_exec($ch);
    $result = json_decode($result);

    //$httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    // todo: might want to check code here too in case result is not formed
        
    curl_close($ch);

    if ( !isset($result->error) ) {
      $this->setAccessToken($result->access_token);
      $this->setRefreshToken($result->refresh_token);
      \Drupal::logger('access_affinitygroup')->notice("Constant Contact: new access_token and refresh_token stored");
      \Drupal::messenger()->addMessage("Constant Contact: new access_token and refresh_token stored");
    } else {
      
      $this->apiError($result->error, $result->error_description);
    }
  }

  /**   
   * @param $key - error key.
   * @param $message - error message.
   */
  public function apiError($key, $message) {
        
    \Drupal::logger('access_affinitygroup')->error("$key: $message");
    \Drupal::messenger()->addMessage("$key: $message", 'error');
  }
  
  /**
   * Make api call.
   * @param $endpoint - end of the URL api call.
   * @param $post_data - included with $type PUT json encoded.
   * @param $type - POST or GET, defaults to GET.
   */
  public function apiCall($endpoint, $post_data=null, $type='GET') {

    $access_token = $this->accessToken;
    // Use cURL to get a new access token and refresh token
    $ch = curl_init();

    // Create full request URL
    $url = 'https://api.cc.email/v3' . $endpoint;
    curl_setopt($ch, CURLOPT_URL, $url);

    // Set authorization header
    // Make string of "API_KEY"
    $auth = $access_token;
    // Base64 encode it
    $credentials = $auth;
    // Create and set the Authorization header to use the encoded credentials,
    // and set the Content-Type header
    $authorization = 'Authorization: Bearer ' . $credentials;
    curl_setopt($ch, CURLOPT_HTTPHEADER, array($authorization, 'Content-Type: application/json'));

    if ($type != 'GET') {      
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
      
      if ($type == 'POST') {      
        curl_setopt($ch, CURLOPT_POST, true);
      }
      if ($type == 'PUT') {      
        curl_setopt($ch, CURLOPT_PUT, true);
      }
      if ($type == 'DELETE') {      
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");        
      }
    }

    // Set method and to expect response
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Make the call
    $returned_result = curl_exec($ch);       
    
    $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);    
    $errMsg = getHttpErrMsg($httpCode);
        
    if (!empty($errMsg)) {
      showStatus($errMsg);    
      return NULL;      
    }

    if (empty($returned_result)) {
      showStatus("Error calling Constant Contact: no result.");
      return NULL;
    }

    $result = json_decode($returned_result);
    
    curl_close($ch);
          
    // looking for error_key; that field is not present if there is no error.
    if ( preg_match('/error_key/', $returned_result, $matches, PREG_OFFSET_CAPTURE) ) {
      foreach ($result as $error) {
        $this->apiError($error->error_key, $error->error_message);
      }
    }

    return $result;
  }

  /**
   * Add user to constant contact emails.
   * @string $firstname - contact's first name.
   * @string $lastname - contact's last name.
   * @string $mail - contact's last name.
   * @string $uid - contact's user id.
   * 
   * Try to add contact and return new contact id
   * generated by Constant Contact.
   * Returns 0 if error adding contact; eg already exists.
   */
  public function addContact($firstname, $lastname, $mail, $uid) {
    $cc_contact = [
      'email_address' => [
        'address' => $mail,
        'permission_to_send' => 'implicit'
      ],
      'first_name' => $firstname,
      'last_name' => $lastname,
      'create_source' => 'Account',
    ];
    $cc_contact = json_encode($cc_contact);

    $new_contact = $this->apiCall('/contacts', $cc_contact, 'POST');
    if (empty($new_contact)) {
      return 0;
    } else {      
      return $new_contact->contact_id;    
    }
  }

  /**
   * Save new access_token.
   */
  private function setAccessToken($access_token) {
    $this->accessToken = $access_token;
    $this->configSettings->set('access_token', $access_token);
    $this->configSettings->save();
  }

  /**
   * Save new refresh_token.
   */
  private function setRefreshToken($refresh_token) {
    $this->refreshToken = $refresh_token;
    $this->configSettings->set('refresh_token', $refresh_token);
    $this->configSettings->save();
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
}
