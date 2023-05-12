<?php

namespace Drupal\access_misc\EventSubscriber;


use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Component\Utility\Html;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Event Subscriber AccessMiscDataLayerEventSubscriber.
 */
class AccessMiscDataLayerEventSubscriber implements EventSubscriberInterface {

  /**
   * Event handler for KernelEvents::REQUEST events.
   */
  public function onRequest(RequestEvent $event) {

    $dl_debug = TRUE;

    if ($dl_debug) {
      $msg = __FUNCTION__ . "() ------- "
        . ' -- ' . basename(__FILE__) . ':' . __LINE__;
      \Drupal::messenger()->addStatus($msg);
      error_log('seamless: ' . $msg);
    }
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
