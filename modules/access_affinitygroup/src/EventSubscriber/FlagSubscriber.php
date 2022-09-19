<?php

/**
* @file
* Contains \Drupal\access_affinitygroup\EventSubscriber\FlagSubscriber.
*/

namespace Drupal\access_affinitygroup\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\Event\UnflaggingEvent;

class FlagSubscriber implements EventSubscriberInterface {

  public function onFlag(FlaggingEvent $event) {
    // $flagging = $event->getFlagging();
    // $entity_nid = $flagging->getFlaggable()->id();
    // WRITE SOME CUSTOM LOGIC
    // $current_user = \Drupal::currentUser();

  }

  public function onUnflag(UnflaggingEvent $event) {
    // $flagging = $event->getFlaggings();
    // $flagging = reset($flagging);
    // $entity_nid = $flagging->getFlaggable()->id();
    // WRITE SOME CUSTOM LOGIC
  }

  public static function getSubscribedEvents() {
    $events = [];
    $events[FlagEvents::ENTITY_FLAGGED][] = ['onFlag'];
    $events[FlagEvents::ENTITY_UNFLAGGED][] = ['onUnflag'];
    return $events;
  }

}
