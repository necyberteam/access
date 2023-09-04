<?php

/**
* @file
* Contains \Drupal\access_misc\EventSubscriber\FlagSubscriber.
*/

namespace Drupal\access_misc\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\Event\UnflaggingEvent;

class FlagSubscriber implements EventSubscriberInterface {

  public function onFlag(FlaggingEvent $event) {
    $flagging = $event->getFlagging();
    $flag_id = $flagging->getFlagId();
    $flagging_type = $flagging->getFlaggableType(); // 'webform;
    $entity_sid = $flagging->getFlaggable()->id();
    if ($flag_id == 'outdated' || $flag_id == 'not_useful' || $flag_id == 'inaccurate') {
      $flag_resource = \Drupal::state()->get('resource_flags');
      $flagged = isset($flag_resource[$entity_sid][$flag_id]) ? $flag_resource[$entity_sid][$flag_id]++ : 1;
      $flag_resource[$entity_sid]['today'] = 1;
      \Drupal::state()->set('resource_flags', $flag_resource);
      $messenger = \Drupal::messenger();
      $messenger->addMessage('The lorax is watching ' . $entity_sid, 'status', TRUE);
    }
  }

  public function onUnflag(UnflaggingEvent $event) {
    $flagging = $event->getFlaggings();
    $flagging = reset($flagging);
    $flag_id = $flagging->getFlagId();
    $flagging_type = $flagging->getFlaggableType(); // 'webform;
    $entity_sid = $flagging->getFlaggable()->id();
    if ($flag_id == 'outdated' || $flag_id == 'not_useful' || $flag_id == 'inaccurate') {
      $flag_resource = \Drupal::state()->get('resource_flags');
      if (isset($flag_resource[$entity_sid][$flag_id])) {
        $flagged = $flag_resource[$entity_sid][$flag_id] !== 0 ? $flag_resource[$entity_sid][$flag_id]-- : 0;
      }
      else {
        $flag_resource[$entity_sid][$flag_id] = 0;
      }
      $flag_resource[$entity_sid]['today'] = 1;
      \Drupal::state()->set('resource_flags', $flag_resource);
      $messenger = \Drupal::messenger();
      $messenger->addMessage('The lorax is watching ' . $entity_sid, 'status', TRUE);
    }
  }

  public static function getSubscribedEvents() {
    $events = [];
    $events[FlagEvents::ENTITY_FLAGGED][] = ['onFlag'];
    $events[FlagEvents::ENTITY_UNFLAGGED][] = ['onUnflag'];
    return $events;
  }

}
