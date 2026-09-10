<?php

declare(strict_types=1);

namespace Drupal\Tests\access_events\Kernel;

use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\field_inheritance\Entity\FieldInheritance;
use Drupal\recurring_events\Entity\EventSeries;
use Drupal\user\Entity\User;

/**
 * Tests the event timezone backfill and its field-inheritance rebuild.
 *
 * Two things here are silent when wrong, and both are pinned below.
 *
 * The backfill writes a zone to every existing series, derived from the
 * author's account timezone. Measured against production that contract holds
 * for 855 of 872 series whose author has a zone set, so it is a sound default
 * — but it is a default, not a proof, and the rows it cannot settle have to
 * reach a human rather than being guessed.
 *
 * Separately, field_inheritance resolves a series field onto an instance
 * through a per-instance keyvalue row, NOT an entity reference. The hook that
 * writes those rows returns early during config sync, which is exactly how a
 * new inheritance config reaches production. So the field can be configured
 * correctly, deploy cleanly, and read empty on every existing instance — and
 * because empty is falsy, the display branch renders its safe side and the
 * whole feature looks like it works while doing nothing.
 *
 * @group access_events
 */
class EventTimezoneBackfillTest extends EventKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->seedTimezoneFields();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // setSyncing is a container-level flag; leaving it set leaks into every
    // later test method in this class.
    \Drupal::service(ConfigInstallerInterface::class)->setSyncing(FALSE);
    parent::tearDown();
  }

  /**
   * Creates a series owned by an author with the given account timezone.
   */
  private function seriesOwnedByAuthorInZone(?string $zone, string $suffix): EventSeries {
    $author = User::create([
      'name' => 'author_' . $suffix,
      'mail' => $suffix . '@example.com',
      'status' => 1,
      'timezone' => $zone ?? '',
    ]);
    $author->save();

    $series = EventSeries::create([
      'title' => 'Series ' . $suffix,
      'type' => 'default',
      'recur_type' => 'custom',
      'uid' => $author->id(),
      'custom_date' => [[
        'value' => '2026-07-15T18:00:00',
        'end_value' => '2026-07-15T19:00:00',
      ]],
    ]);
    $series->save();
    return $series;
  }

  /**
   * The backfill takes the author's account timezone.
   */
  public function testBackfillTakesTheAuthorsAccountTimezone(): void {
    $series = $this->seriesOwnedByAuthorInZone('America/Chicago', 'chi');

    $written = \Drupal::service('access_events.timezone_backfill')->backfill();

    $series = $this->reloadSeries($series);
    $this->assertSame('America/Chicago',
      $series->get('field_event_timezone')->value,
      "the series takes its author's zone");
    $this->assertGreaterThan(0, $written['author_zone'],
      'and the run reports it as author-derived');
  }

  /**
   * An author with no timezone falls back to the site default.
   */
  public function testAuthorWithoutZoneFallsBackToSiteDefault(): void {
    $this->config('system.date')->set('timezone.default', 'America/New_York')->save();
    $series = $this->seriesOwnedByAuthorInZone(NULL, 'nozone');

    \Drupal::service('access_events.timezone_backfill')->backfill();

    $this->assertSame('America/New_York',
      $this->reloadSeries($series)->get('field_event_timezone')->value,
      'the site default stands in');
  }

  /**
   * A series that already has a zone is never overwritten.
   *
   * The backfill must be safe to re-run, and re-running must not revert a
   * human correction — which is the whole reason it records provenance.
   */
  public function testExistingValueIsNeverOverwritten(): void {
    $series = $this->seriesOwnedByAuthorInZone('America/Chicago', 'corrected');
    $series->set('field_event_timezone', 'Europe/Rome');
    $series->save();

    \Drupal::service('access_events.timezone_backfill')->backfill();

    $this->assertSame('Europe/Rome',
      $this->reloadSeries($series)->get('field_event_timezone')->value,
      'a human correction survives a re-run');
  }

  /**
   * Re-running the backfill changes nothing.
   */
  public function testBackfillIsIdempotent(): void {
    $this->seriesOwnedByAuthorInZone('America/Denver', 'idem');

    $first = \Drupal::service('access_events.timezone_backfill')->backfill();
    $second = \Drupal::service('access_events.timezone_backfill')->backfill();

    $this->assertGreaterThan(0, $first['author_zone'], 'the first run writes');
    $this->assertSame(0, $second['author_zone'] + $second['site_default'],
      'the second run writes nothing');
  }

  /**
   * The backfill writes every revision, not just the current one.
   *
   * A field row missing from a revision reads as EMPTY when that revision is
   * loaded. Since generation resolves the stored zone, reverting a series to
   * such a revision would resolve against the ambient zone instead and
   * silently reschedule the event. Production has 3,859 series revisions.
   */
  public function testBackfillWritesEveryRevision(): void {
    $series = $this->seriesOwnedByAuthorInZone('America/Chicago', 'revisioned');

    // A second and third revision, as an edited series accumulates.
    foreach (['Second title', 'Third title'] as $title) {
      $series->setNewRevision(TRUE);
      $series->set('title', $title);
      $series->save();
    }

    $revisionIds = \Drupal::database()->select('eventseries_revision', 'r')
      ->fields('r', ['vid'])
      ->condition('r.id', $series->id())
      ->execute()
      ->fetchCol();
    $this->assertGreaterThan(1, count($revisionIds), 'the series has several revisions');

    \Drupal::service('access_events.timezone_backfill')->backfill();

    $storage = \Drupal::entityTypeManager()->getStorage('eventseries');
    foreach ($revisionIds as $vid) {
      $revision = $storage->loadRevision($vid);
      $this->assertNotNull($revision, "revision $vid loads");
      $this->assertSame(
        'America/Chicago',
        $revision->get('field_event_timezone')->value,
        "revision $vid carries the zone, so reverting to it cannot reschedule the event"
      );
    }
  }

  /**
   * The inheritance keyvalue rebuild is what makes the field readable.
   *
   * Installing the inheritance config under config sync — which is how it
   * reaches production — writes no keyvalue row, so the instance reads empty.
   * A test that instead creates a series programmatically passes whether or
   * not the rebuild exists, because the insert hook fires on that path.
   */
  public function testKeyvalueRebuildMakesInheritedFieldReadable(): void {
    // A series and instance that exist BEFORE the inheritance config, which is
    // the situation of all 1,737 production instances.
    $instance = $this->createRegistrableInstance();
    $series = $instance->getEventSeries();
    $series->set('field_event_timezone', 'America/Denver')->save();

    $keyValue = \Drupal::keyValue('field_inheritance');
    $stateKey = 'eventinstance:' . $instance->uuid();

    // Drop the row this fixture's helper wrote, to model the deployed state.
    $row = $keyValue->get($stateKey) ?: [];
    unset($row['event_timezone']);
    $keyValue->set($stateKey, $row);

    // Re-declare the config the way a deploy does: during config sync.
    \Drupal::service(ConfigInstallerInterface::class)->setSyncing(TRUE);
    if ($existing = FieldInheritance::load('eventinstance_default_event_timezone')) {
      $existing->delete();
    }
    FieldInheritance::create([
      'id' => 'eventinstance_default_event_timezone',
      'label' => 'Event timezone',
      'type' => 'inherit',
      'sourceEntityType' => 'eventseries',
      'sourceEntityBundle' => 'default',
      'sourceField' => 'field_event_timezone',
      'destinationEntityType' => 'eventinstance',
      'destinationEntityBundle' => 'default',
      'destinationField' => '',
      'plugin' => 'default_inheritance',
    ])->save();
    \Drupal::service(ConfigInstallerInterface::class)->setSyncing(FALSE);
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    $this->assertArrayNotHasKey('event_timezone', $keyValue->get($stateKey) ?: [],
      'a config-sync install writes no keyvalue row — this is the silent failure');

    $rebuilt = \Drupal::service('access_events.timezone_backfill')->rebuildInheritance();

    $this->assertArrayHasKey('event_timezone', $keyValue->get($stateKey) ?: [],
      'the rebuild writes the missing row');
    $this->assertGreaterThan(0, $rebuilt, 'and reports how many it repaired');
    $this->assertSame('America/Denver',
      $this->reloadInstance($instance)->get('event_timezone')->value,
      'so the instance finally resolves a non-empty value');
  }

  /**
   * The rebuild MERGES; it must not wipe other inherited fields.
   *
   * Contrib's own rebuild resets the row and repopulates from every
   * inheritance config, which is safe only because it enumerates all of them.
   * Copying that shape while scoped to two fields would blank the title,
   * description, location and event_type rows for all 1,737 instances — and
   * those render as absent rather than as errors, so nothing would look broken.
   */
  public function testRebuildPreservesOtherInheritedFields(): void {
    $instance = $this->createRegistrableInstance();
    $keyValue = \Drupal::keyValue('field_inheritance');
    $stateKey = 'eventinstance:' . $instance->uuid();

    $before = $keyValue->get($stateKey) ?: [];
    $this->assertNotEmpty($before, 'the instance starts with inheritance rows');

    \Drupal::service('access_events.timezone_backfill')->rebuildInheritance();

    $after = $keyValue->get($stateKey) ?: [];
    foreach (array_keys($before) as $key) {
      $this->assertArrayHasKey($key, $after,
        "the rebuild preserved the pre-existing '$key' row");
    }
  }

  /**
   * Reloads a series from storage.
   */
  private function reloadSeries(EventSeries $series): EventSeries {
    return \Drupal::entityTypeManager()->getStorage('eventseries')
      ->loadUnchanged($series->id());
  }

}
