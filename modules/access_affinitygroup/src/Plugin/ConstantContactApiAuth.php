<?php

namespace Drupal\access_affinitygroup\Plugin;


/**
 * Make Constant Contact api call.
 */
class ConstantContactApiAuth {
  /**
   * Return api results.
   *
   * @var array
   */
  private $apiResult;

  /**
   * Function to sort the curl headers.
   */
  public function __construct() {

  }

  /**
  * @param $redirectURI - URL Encoded Redirect URI
  * @param $clientId - API Key
  * @param $scope - URL encoded, plus sign delimited list of scopes that your application requires. The 'offline_access' scope needed to request a refresh token is added by default.
  * @param $state - Arbitrary string value(s) to verify response and preserve application state
  * @return string - Full Authorization URL
  */

  public function getAuthorizationURL($clientId, $redirectURI, $scope, $state) {
     // Create authorization URL
     $baseURL = "https://authz.constantcontact.com/oauth2/default/v1/authorize";
     $authURL = $baseURL . "?client_id=" . $clientId . "&scope=" . $scope . "+offline_access&response_type=code&state=" . $state . "&redirect_uri=" . $redirectURI;

     return $authURL;

  }

}
