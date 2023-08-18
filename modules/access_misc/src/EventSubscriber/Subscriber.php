<?php

namespace Drupal\access_misc\EventSubscriber;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Utility\Html;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Event Subscriber EventSubscriber
 */
class Subscriber implements EventSubscriberInterface {

  // for pantheon, cookie name must follow pattern S+ESS[a-z0-9]+
  // (see https://docs.pantheon.io/cookies#cache-busting-cookies)
  const SEAMLESSCOOKIENAME = 'SESSaccesscisso';

  /**
   * Event handler for KernelEvents::REQUEST events, specifically to support
   * seamless login by checking if a non-authenticated user already has already
   * been through seamless login.
   */
  public function onRequest(RequestEvent $event) {

    $user_is_authenticated = \Drupal::currentUser()->isAuthenticated();
    $route_name = \Drupal::routeMatch()->getRouteName();
    $cookie_name = self::SEAMLESSCOOKIENAME;
    $cookie_exists = NULL !== \Drupal::service('request_stack')->getCurrentRequest()->cookies->get($cookie_name);


    // if here -- user is unauthenticated.  If cookie exists, redirect to cilogon
    if ($route_name == 'misc.login' && !$user_is_authenticated) {
      $this->doRedirectToCilogon($event);
    }
  }

  /**
   * Add the cookie, via a redirect
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Response event.

   */
  protected function doSetCookie(RequestEvent $event, $seamless_debug, $cookie_name)
  {

    $site_name = \Drupal::config('system.site')->get('name');
    $cookie_value = \Drupal::state()->get('drupal_seamless_cilogon.seamless_cookie_value', $site_name);
    $cookie_expiration = \Drupal::state()->get('drupal_seamless_cilogon.seamless_cookie_expiration', '+18 hours');
    $cookie_expiration = strtotime($cookie_expiration);  // use value from form
    $cookie_domain = \Drupal::state()->get('drupal_seamless_cilogon.seamless_cookie_domain', '.access-ci.org');
    $cookie = new Cookie($cookie_name, $cookie_value, $cookie_expiration, '/', $cookie_domain);

    $request = $event->getRequest();
    $destination = $request->getRequestUri();

    // TODO -- consider following
    // "MUST use service to turn of Internal Page Cache,
    // or else anonymous users will not ever be able to reach source page."
    // $this->killSwitch->trigger();
    // from https://www.drupal.org/project/adv_varnish/issues/3127566:
    // Another documented way is to call the killSwitch in your code:
    //
    // commenting this out to see unnecessary
    // \Drupal::service('page_cache_kill_switch')->trigger();

    $redir = new TrustedRedirectResponse($destination, '302');
    $redir->headers->setCookie($cookie);
    $redir->headers->set('Cache-Control', 'public, max-age=0');
    $redir->addCacheableDependency($destination);
    $redir->addCacheableDependency($cookie);

    $event->setResponse($redir);

    if ($seamless_debug) {
      $msg =  __FUNCTION__ . "() - destination = $destination ---- set cookie:  name = $cookie_name, value = $cookie_value, expiration = $cookie_expiration "
        . " = " . date("Y-m-d H:i:s", $cookie_expiration) . ", domain = $cookie_domain"
        . ' -- ' . basename(__FILE__) . ':' . __LINE__;
      \Drupal::messenger()->addStatus($msg);
    }
  }

  /**
   * Delete the cookie, then redirect to user.logout
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Response event.

   */
  protected function doDeleteCookie(RequestEvent $event, $seamless_debug, $cookie_name)
  {

    $cookie_value = '';
    $cookie_expiration = strtotime('-1 hour');
    $cookie_domain = \Drupal::state()->get('drupal_seamless_cilogon.seamless_cookie_domain', '.access-ci.org');

    // Set cookie in the past and then remove it.
    setcookie($cookie_name, $cookie_value, $cookie_expiration, '/', $cookie_domain);
    unset($_COOKIE[$cookie_name]);

    $destination = 'https://cilogon.org/logout/?skin=access';

    // \Drupal::service('page_cache_kill_switch')->trigger();

    $redir = new TrustedRedirectResponse($destination, '302');
    $redir->headers->set('Cache-Control', 'public, max-age=0');
    $redir->addCacheableDependency($destination);

    $event->setResponse($redir);

    if ($seamless_debug) {
      $msg =  __FUNCTION__ . "() - destination = $destination ---- unset cookie"
        . ' -- ' . basename(__FILE__) . ':' . __LINE__;
      \Drupal::messenger()->addStatus($msg);
      error_log('seamless: ' . $msg);
    }
  }

  /**
   * Redirect to Cilogon
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Response event.

   */
  protected function doRedirectToCilogon(RequestEvent $event)
  {
    $request = $event->getRequest();

    // \Drupal::service('page_cache_kill_switch')->trigger();

    // Setup redirect to CILogon flow.
    // TODO - move some of the following to a constructor for this class?
    $container = \Drupal::getContainer();
    $client_name = 'cilogon';
    $config_name = 'cilogon_auth.settings.' . $client_name;
    $configuration = $container->get('config.factory')->get($config_name)->get('settings');
    $pluginManager = $container->get('plugin.manager.cilogon_auth_client.processor');
    $claims = $container->get('cilogon_auth.claims');
    $client = $pluginManager->createInstance($client_name, $configuration);
    $scopes = $claims->getScopes();
    $destination = $request->getRequestUri();
    $query = NULL;
    if (NULL !== \Drupal::request()->query->get('redirect')) {
      $query = Xss::filter(\Drupal::request()->query->get('redirect'));
    }
    $_SESSION['cilogon_auth_op'] = 'login';
    $_SESSION['cilogon_auth_destination'] = [$destination, ['query' => $query]];

    $response = $client->authorize($scopes);
    $response->headers->set('Cache-Control', 'public, max-age=0');

    $event->setResponse($response);
  }


  /**
   * The ACCESS support portal uses the domain access module.  If this module
   * is in use, we only want to set cookies for the 'access-support'
   * module.
   *
   * This function checks if the domain access module is in use, and
   * if so, returns FALSE if the current domain name is not 'access-support'.
   *
   * Otherwise it returns true.
   *
   * @return bool whether to proceed with the cookie logic in invoking code
   *
   */
  protected function verify_domain_is_asp()
  {
    // Verify the domain module is installed.  If not installed,
    // return true to proceed to CILogon.
    $moduleHandler = \Drupal::service('module_handler');
    if (!$moduleHandler->moduleExists('domain')) {
      return true;
    }

    $token = \Drupal::token();
    $domainName = t("[domain:name]");
    $current_domain_name = Html::getClass($token->replace($domainName));

    $domain_verified = $current_domain_name === 'access-support';

    $seamless_debug = \Drupal::state()->get('drupal_seamless_cilogon.seamless_cookie_debug', false);
    if ($seamless_debug) {
      $msg =  __FUNCTION__ . "() - current_domain_name = [" . $current_domain_name
        . '] so verify_domain_is_asp() returns ' . ($domain_verified ? 'TRUE' : 'FALSE')
        . ' -- ' . basename(__FILE__) . ':' . __LINE__;
      \Drupal::messenger()->addStatus($msg);
    }

    // return true if the current domain is 'access-support'
    return $domain_verified;
  }

  /**
   * Subscribe to onRequest events.  This allows checking if a CILogon redirect is needed any time
   * a page is requested.
   *
   * {@inheritdoc}
   */
  public static function getSubscribedEvents()
  {
    $events[KernelEvents::REQUEST][] = ['onRequest', 31];
    return $events;
  }
}
