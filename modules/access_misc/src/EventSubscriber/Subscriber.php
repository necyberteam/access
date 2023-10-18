<?php

namespace Drupal\access_misc\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event Subscriber EventSubscriber.
 */
class Subscriber implements EventSubscriberInterface {

  /**
   * Redirect user if not authenticated and on /login page.
   */
  public function onRequest(RequestEvent $event) {

    $user_is_authenticated = \Drupal::currentUser()->isAuthenticated();
    $route_name = \Drupal::routeMatch()->getRouteName();

    // Log user in on the /login page.
    if ($route_name == 'misc.login' && !$user_is_authenticated) {
      $this->doRedirectToCilogon($event);
    }

    if ($route_name == 'user.login' && !$user_is_authenticated) {
      $this->doRedirectToCilogon($event);
    }
  }

  /**
   * Redirect to Cilogon.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Response event.   *.
   */
  protected function doRedirectToCilogon(RequestEvent $event) {
    $request = $event->getRequest();

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
   * Subscribe to onRequest events.
   *
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest', 31];
    return $events;
  }

}
