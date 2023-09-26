<?php

namespace Drupal\access_cilink\EventSubscriber;

use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\Event\UnflaggingEvent;
use Drupal\views\Views;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 *
 */
class FlagSubscriber implements EventSubscriberInterface {

  /**
   *
   */
  public function onFlag(FlaggingEvent $event) {
    $flagging = $event->getFlagging();
    $flag_id = $flagging->getFlagId();
    $entity_sid = $flagging->getFlaggable()->id();
    if ($flag_id == 'outdated' || $flag_id == 'not_useful' || $flag_id == 'inaccurate') {
      $flag_resource = \Drupal::state()->get('resource_flags');
      if (isset($flag_resource[$entity_sid][$flag_id])) {
        $flagged = isset($flag_resource[$entity_sid][$flag_id]) ? $flag_resource[$entity_sid][$flag_id]++ : 1;
      }
      else {
        $flag_resource[$entity_sid][$flag_id] = 1;
      }
      $flag_resource[$entity_sid]['today'] = 1;
      // Need to invalidate cache for the view to properly update link.
      $view = Views::getView('resource');
      $view->storage->invalidateCaches();
      // Set state to send email later.
      \Drupal::state()->set('resource_flags', $flag_resource);
    }
  }

  /**
   *
   */
  public function onUnflag(UnflaggingEvent $event) {
    $flagging = $event->getFlaggings();
    $flagging = reset($flagging);
    $flag_id = $flagging->getFlagId();
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
      // Need to invalidate cache for the view to properly update link.
      $view = Views::getView('resource');
      $view->storage->invalidateCaches();
      // Set state to send email later.
      \Drupal::state()->set('resource_flags', $flag_resource);
    }
  }

  /**
   *
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[FlagEvents::ENTITY_FLAGGED][] = ['onFlag'];
    $events[FlagEvents::ENTITY_UNFLAGGED][] = ['onUnflag'];
    return $events;
  }

}