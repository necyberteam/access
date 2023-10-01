<?php

namespace Drupal\ticketing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Redirect to JSM.
 */
/*
ticket-login-access -  can do it if not logged in + don't append

https://access-ci.atlassian.net/servicedesk/customer/portal/2/group/3/create/30

ticket-login-other - append the usernames (redirect id not logged in)
https://access-ci.atlassian.net/servicedesk/customer/portal/2/group/3/create/31

ticket-other-question -
ticket-login-other - append the usernames (redirect id not logged in)
'https://access-ci.atlassian.net/servicedesk/customer/portal/2/group/3/create/17',
*/
class TicketingController extends ControllerBase {

  public function doRedirect($ticket_type) {

    \Drupal::logger('aticket')->notice("IN ticket controller redirect type: $ticket_type");
    $createTicketUrlBase = "https://access-ci.atlassian.net/servicedesk/customer/portal/2/group/3/create/";

    if ($ticket_type == 'ticket-login-other') {
      $finalCode = '31';
    } else if ($ticket_type == 'ticket-other-question') {
      $finalCode = '17';
    }

    // for these types, send over the account name and user name to prefill in the ticket
    if ($ticket_type == 'ticket-login-other' || $ticket_type == 'ticket-other-question') {
      $account = User::load(\Drupal::currentUser()->id());
      $account_name = $account->getAccountName();
      $display_name = $account->getDisplayName();

      $uri = Url::fromUri(
        $createTicketUrlBase . $finalCode,
        [
          'query' => [
            'customfield_10103' => $account_name,
            'customfield_10108' => $display_name,
          ],
        ]
      );
      $url = $uri->toString();
    }
    \Drupal::logger('aticket')->notice($uri->toString());
    return new TrustedRedirectResponse($uri->toString());
  }

  public function accessLoginTicket() {
    \Drupal::logger('aticket')->notice("IN Access Login Ticket");
    $url = "https://access-ci.atlassian.net/servicedesk/customer/portal/2/group/3/create/30";
    \Drupal::logger('aticket')->notice($url);
    return new TrustedRedirectResponse($url);
  }
}
