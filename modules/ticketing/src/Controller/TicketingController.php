<?php

namespace Drupal\ticketing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * redirect to JSM
 */
class TicketingController extends ControllerBase {

  /**
   * Redirect to JSM, and prefill the 
   */
  public function doRedirect() {
    $account = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $account_name = $account->getAccountName();
    $display_name = $account->getDisplayName();

    $uri = 'https://access-ci.atlassian.net/servicedesk/customer/portal/2/group/3/create/17?customfield_10103='
        . urlencode($account_name) .'&customfield_10108='
        . urlencode($display_name);

    return new TrustedRedirectResponse($uri);
  }
}