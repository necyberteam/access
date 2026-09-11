<?php

declare(strict_types=1);

namespace Drupal\Tests\access_events\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the _admin_route stripping performed by RouteAlterSubscriber.
 *
 * The recurring_events 3.0.0 module builds both public collection routes
 * (entity.eventinstance.collection, entity.eventseries.collection) with
 * options._admin_route: TRUE via
 * \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider (see
 * EventInstanceHtmlRouteProvider / EventSeriesHtmlRouteProvider), and also
 * declares it directly in recurring_events.routing.yml for those two route
 * names. RouteAlterSubscriber runs at RoutingEvents::ALTER priority -200 (after
 * the Views route subscriber, priority -175, takes the paths over for the
 * events_facet and recurring_events_event_series View displays) and strips the
 * flag from just those two routes, so anonymous and privileged visitors alike
 * see /events and /events/series in the default theme.
 *
 * A standalone base (rather than EventKernelTestBase) is used deliberately:
 * this test only needs the router built, not any event/registration fixtures.
 *
 * @covers \Drupal\access_events\EventSubscriber\RouteAlterSubscriber
 * @group access_events
 */
class RouteAlterSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'options',
    'text',
    'link',
    'datetime',
    'datetime_range',
    'field_inheritance',
    'recurring_events',
    // access_events.cancellation_notifier needs the notification service
    // recurring_events_registration provides.
    'recurring_events_registration',
    // access_events.services.yml decorates core's access_check.latest_revision
    // service (access_events.latest_revision), which content_moderation
    // provides; workflows is content_moderation's own dependency.
    'workflows',
    'content_moderation',
    // 'access' provides access.access_id_resolver, an access_events
    // dependency; access_affinitygroup is access_events' other declared
    // dependency (and needs key.repository for its xdusage_client service).
    'key',
    'access',
    'access_affinitygroup',
    // access_events.post_survey needs access_misc.sitetools + the Symfony
    // mailer service access_misc provides.
    'access_misc',
    'access_events',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('eventseries');
    $this->installEntitySchema('eventinstance');

    // field_inheritance 3.x installs a `field_inheritance` base field on every
    // entity type named in field_inheritance.config, via its ConfigSubscriber.
    // The module's install default names node/taxonomy_term/block_content/
    // file, whose entity schemas this minimal kernel env does not install —
    // set the site's value directly rather than importing the module default
    // (mirrors EventKernelTestBase::setUp()).
    $this->config('field_inheritance.config')
      ->set('included_entities', ['eventinstance'])
      ->save();
    $this->installConfig(['recurring_events']);

    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * The public eventinstance collection route (/events) has no admin flag.
   */
  public function testEventInstanceCollectionRouteIsNotAdmin(): void {
    $route = \Drupal::service('router.route_provider')
      ->getRouteByName('entity.eventinstance.collection');

    $this->assertNull($route->getOption('_admin_route'));
  }

  /**
   * The public eventseries collection route (/events/series) has no admin flag.
   */
  public function testEventSeriesCollectionRouteIsNotAdmin(): void {
    $route = \Drupal::service('router.route_provider')
      ->getRouteByName('entity.eventseries.collection');

    $this->assertNull($route->getOption('_admin_route'));
  }

  /**
   * Control: the admin-only series listing keeps its admin flag.
   *
   * The entity.eventseries.admin_collection route
   * (/admin/content/events/series) is declared with options._admin_route:
   * TRUE directly in recurring_events.routing.yml and is NOT one of the two
   * routes RouteAlterSubscriber targets, proving the subscriber is not
   * over-broad.
   */
  public function testEventSeriesAdminCollectionRouteStaysAdmin(): void {
    $route = \Drupal::service('router.route_provider')
      ->getRouteByName('entity.eventseries.admin_collection');

    $this->assertTrue($route->getOption('_admin_route'));
  }

}
