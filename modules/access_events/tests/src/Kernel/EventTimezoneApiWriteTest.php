<?php

declare(strict_types=1);

namespace Drupal\Tests\access_events\Kernel;

/**
 * Tests that the write API can set an event's timezone, and defaults it.
 *
 * api/2.3 is a write-capable CRUD surface, and it is the path the MCP agent
 * creates events through. Its writable fields are a hardcoded allowlist, so a
 * field absent from that list simply cannot be set — an agent-created event
 * would carry no timezone at all.
 *
 * The form's author-zone default is a form-layer hook, so it never fires here.
 * Without an equivalent, every agent-created event would fall to the display
 * fallback, which is the population most likely to need a real zone.
 *
 * @group access_events
 */
class EventTimezoneApiWriteTest extends EventKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->seedTimezoneFields();
  }

  /**
   * The timezone is a writable field on the create path.
   */
  public function testTimezoneIsWritableThroughTheApi(): void {
    $this->assertContains(
      'field_event_timezone',
      $this->writableContentFields(),
      'an API caller can set the event timezone'
    );
  }

  /**
   * The in-person flag is writable too.
   */
  public function testInPersonFlagIsWritableThroughTheApi(): void {
    $this->assertContains(
      'field_event_in_person',
      $this->writableContentFields(),
      'an API caller can mark an event as having a physical venue'
    );
  }

  /**
   * A create that omits the timezone gets the acting user's account zone.
   *
   * This mirrors what the form does, so both write paths agree rather than
   * diverging by which one an event happened to come through.
   */
  public function testCreateWithoutTimezoneDefaultsToActingUsersZone(): void {
    $actor = $this->createUser([], 'api_actor');
    $actor->set('timezone', 'America/Denver')->save();

    $this->assertSame(
      'America/Denver',
      _access_events_api_default_timezone($actor),
      "an API create with no timezone takes the acting user's zone"
    );
  }

  /**
   * An acting user with no zone falls back to the site default.
   */
  public function testActingUserWithoutZoneFallsBackToSiteDefault(): void {
    $this->config('system.date')->set('timezone.default', 'America/New_York')->save();
    $actor = $this->createUser([], 'zoneless_actor');
    $actor->set('timezone', '')->save();

    $this->assertSame(
      'America/New_York',
      _access_events_api_default_timezone($actor),
      'the site default stands in, as it does on the form'
    );
  }

  /**
   * The resolved default is always a zone the select would offer.
   */
  public function testResolvedApiDefaultIsAlwaysValid(): void {
    $actor = $this->createUser([], 'validity_actor');
    $actor->set('timezone', 'Australia/Perth')->save();

    $this->assertContains(
      _access_events_api_default_timezone($actor),
      \DateTimeZone::listIdentifiers(),
      'the default is a real IANA zone, so generation and display can use it'
    );
  }

  /**
   * Reads the controller's writable-field allowlist.
   */
  private function writableContentFields(): array {
    $reflection = new \ReflectionClass(
      \Drupal\access_events\Controller\EventCrudApiController::class
    );
    return $reflection->getConstant('CONTENT_ATTRIBUTES');
  }

}
