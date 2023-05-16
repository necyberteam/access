<?php

namespace Drupal\views_custom_access\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ViewsCustomAccessSubscriber implements EventSubscriberInterface {
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['extendViewsCustomAccess'];
    return $events;
  }

  /**
   * Perform our logic here
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   */
  public function extendViewsCustomAccess(FilterResponseEvent $event) {
    // Get the current user
    $user = \Drupal::currentUser();
    // Get the user's permissions hash
    $hash = \Drupal::service('user_permissions_hash_generator')->generate($user);
    // Get the render cache
    $render_cache = \Drupal::cache('render');
    // Use the hash to delete the cached render for the view.
    // @TODO instead of hardcoding, create a facility to read all views using my permission plugin and loop through after drush cr
    $render_cache->delete('view:affinity_group_members:display:page_1:[languages:language_interface]=en:[theme]=accesstheme:[user.permissions]=' . $hash);
  }
}
