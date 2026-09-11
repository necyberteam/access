<?php

declare(strict_types=1);

namespace Drupal\access_events\EventSubscriber;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Strips the admin-route flag recurring_events 3.0.0 added to public listings.
 *
 * Applies to the two public-facing entity collection paths only:
 *  - /events        (entity.eventinstance.collection, taken over by the
 *                    events_facet View's page_1 display)
 *  - /events/series (entity.eventseries.collection, taken over by the
 *                    recurring_events_event_series View)
 *
 * Every other recurring_events admin route — /admin/content/events/…, the
 * operation routes flagged by RecurringEventsAdminRouteSubscriber, and the
 * revision routes — keeps its flag.
 */
class RouteAlterSubscriber implements EventSubscriberInterface {

  /**
   * Route names whose _admin_route option must be removed.
   */
  private const PUBLIC_COLLECTION_ROUTES = [
    'entity.eventinstance.collection',
    'entity.eventseries.collection',
  ];

  /**
   * Removes options._admin_route from the public collection routes.
   *
   * The recurring_events module (3.0.0) builds both collection routes through
   * \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider (via
   * EventInstanceHtmlRouteProvider and EventSeriesHtmlRouteProvider), which
   * sets options._admin_route: TRUE. The events_facet page_1 and
   * recurring_events_event_series View displays share those exact paths, so
   * Views takes over the existing routes rather than registering its own —
   * and inherits the admin-route flag along with them, even though neither
   * View has an admin-theme setting of its own. Since 'view the
   * administration theme' is granted to admin-ish roles, logged-in privileged
   * users ended up with /events and /events/series wrapped in the admin theme
   * while anonymous visitors (who lack that permission) still saw the default
   * theme. Strip the flag to restore recurring_events 2.0.3 behavior.
   *
   * Must run after \Drupal\views\EventSubscriber\RouteSubscriber, which
   * takes over these routes at priority -175.
   */
  public function onAlterRoutes(RouteBuildEvent $event): void {
    $collection = $event->getRouteCollection();
    foreach (self::PUBLIC_COLLECTION_ROUTES as $route_name) {
      if ($route = $collection->get($route_name)) {
        $options = $route->getOptions();
        unset($options['_admin_route']);
        $route->setOptions($options);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -200];
    return $events;
  }

}
