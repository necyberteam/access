<?php

declare(strict_types=1);

namespace Drupal\access_events;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;

/**
 * Backfills event timezones and repairs field-inheritance for them.
 *
 * Two jobs, deliberately separate because they fail differently.
 *
 * The backfill writes each series' timezone from its author's account zone.
 * That is the contract organizers were always given, and it holds well on real
 * data — 855 of 872 series whose author has a zone set have their first
 * instance land on a round local time in that zone. It is still a default
 * rather than a proof, so provenance is recorded and the rows it cannot settle
 * are reported rather than guessed.
 *
 * The inheritance rebuild exists because field_inheritance resolves a series
 * field onto an instance through a per-instance keyvalue row, not an entity
 * reference. The contrib hook that writes those rows returns early during
 * config sync, which is precisely how a new inheritance config arrives in
 * production — so without this the field reads empty on every pre-existing
 * instance, and because empty is falsy the display renders its safe branch and
 * nothing appears broken.
 */
class EventTimezoneBackfill {

  /**
   * Inheritance ids this class owns, mapped to their source series field.
   *
   * Only these keys are ever written. The rebuild MERGES into the existing
   * keyvalue row: contrib's own rebuild resets the row and repopulates from
   * every inheritance config, which is safe only because it enumerates all of
   * them. Scoped to two fields, that shape would blank the title, description,
   * location and event_type rows for every instance — and those render as
   * absent rather than as errors.
   */
  private const OWNED_INHERITANCES = [
    'event_timezone' => 'field_event_timezone',
    'event_in_person' => 'field_event_in_person',
  ];

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly KeyValueFactoryInterface $keyValue,
    private readonly Connection $database,
  ) {}

  /**
   * Writes a timezone to every series that has none.
   *
   * Writes the field column directly rather than saving the entity: an entity
   * save would run the recur-config change detection, and on a series with
   * registrations that path throws rather than rebuilding. A backfill must not
   * be able to destroy registrations.
   *
   * @return array
   *   Counts keyed 'author_zone', 'site_default', 'skipped', plus a 'review'
   *   list of series needing a human decision.
   */
  public function backfill(): array {
    $siteDefault = (string) ($this->configFactory->get('system.date')->get('timezone.default') ?: date_default_timezone_get());
    $storage = $this->entityTypeManager->getStorage('eventseries');

    $result = [
      'author_zone' => 0,
      'site_default' => 0,
      'skipped' => 0,
      'review' => [],
    ];

    $ids = $storage->getQuery()->accessCheck(FALSE)->execute();
    foreach (array_chunk($ids, 50) as $chunk) {
      foreach ($storage->loadMultiple($chunk) as $series) {
        if (!$series->hasField('field_event_timezone')) {
          continue;
        }
        $existing = trim((string) ($series->get('field_event_timezone')->value ?? ''));
        if ($existing !== '') {
          // Never overwrite: a re-run must not revert a human correction.
          $result['skipped']++;
          continue;
        }

        $owner = $series->getOwner();
        $authorZone = $owner ? trim((string) ($owner->getTimeZone() ?? '')) : '';
        $derived = $authorZone !== '' ? $authorZone : $siteDefault;

        $this->writeZone($series, $derived, $authorZone !== '' ? 'author' : 'site_default');
        $authorZone !== '' ? $result['author_zone']++ : $result['site_default']++;

        if ($reason = $this->needsReview($series, $derived)) {
          $result['review'][] = [
            'id' => (int) $series->id(),
            'title' => (string) $series->label(),
            'written' => $derived,
            'reason' => $reason,
          ];
        }
      }
      $storage->resetCache($chunk);
    }

    return $result;
  }

  /**
   * Why a series' derived zone should be checked by a human, if it should.
   *
   * Deliberately conservative: these are reported, not corrected. The location
   * cases are the ones production actually shows — a timezone typed into the
   * location field, or a broadcast to satellite sites where a single venue
   * zone is the wrong shape and the real times live in the body prose.
   */
  private function needsReview($series, string $written): ?string {
    $location = $series->hasField('field_location')
      ? trim((string) ($series->get('field_location')->value ?? ''))
      : '';

    if ($location === '') {
      return NULL;
    }
    // A satellite broadcast happens at several clocks at once.
    if (preg_match('/^(multiple|tbd|tba|n\/a|na|varies|various)$/i', $location)) {
      return 'location is "' . $location . '" — may be a broadcast to satellite sites; check the body for the stated time';
    }
    // The workaround this field replaces: a zone written into free text.
    if (preg_match('/\b([ECMP][SD]T|UTC|GMT|BST|CES?T)\b/i', $location, $m)
      && stripos($written, (string) $m[1]) === FALSE) {
      return 'location names "' . $m[1] . '" but the author zone gives ' . $written;
    }
    return NULL;
  }

  /**
   * Writes the zone column and its provenance, without an entity save.
   */
  private function writeZone($series, string $zone, string $provenance): void {
    foreach (['eventseries__field_event_timezone' => 'field_event_timezone_value'] as $table => $column) {
      if (!$this->database->schema()->tableExists($table)) {
        continue;
      }
      $this->database->merge($table)
        ->keys([
          'entity_id' => $series->id(),
          'deleted' => 0,
          'delta' => 0,
          'langcode' => $series->language()->getId(),
        ])
        ->fields([
          'bundle' => $series->bundle(),
          'revision_id' => $series->getRevisionId(),
          $column => $zone,
        ])
        ->execute();
    }
    // Provenance lives in state rather than a field: it is operational data
    // about the migration, not part of the event, and "empty" stops
    // distinguishing derived from human-set the moment the backfill runs.
    $key = 'access_events.timezone_provenance';
    $store = $this->keyValue->get($key);
    $store->set((string) $series->id(), $provenance);
  }

  /**
   * Repairs the field-inheritance keyvalue rows this class owns.
   *
   * @return int
   *   How many instance rows were repaired.
   */
  public function rebuildInheritance(): int {
    $instanceStorage = $this->entityTypeManager->getStorage('eventinstance');
    $store = $this->keyValue->get('field_inheritance');
    $repaired = 0;

    $ids = $instanceStorage->getQuery()->accessCheck(FALSE)->execute();
    foreach (array_chunk($ids, 50) as $chunk) {
      foreach ($instanceStorage->loadMultiple($chunk) as $instance) {
        $series = $instance->getEventSeries();
        if (!$series) {
          continue;
        }
        $stateKey = $instance->getEntityTypeId() . ':' . $instance->uuid();
        // Read-modify-write. Never reset: the row holds every other inherited
        // field's source too.
        $row = $store->get($stateKey) ?: [];
        $changed = FALSE;
        foreach (array_keys(self::OWNED_INHERITANCES) as $name) {
          if (empty($row[$name]['entity'])) {
            $row[$name] = ['entity' => $series->id()];
            $changed = TRUE;
          }
        }
        if ($changed) {
          $row['enabled'] = TRUE;
          $store->set($stateKey, $row);
          $repaired++;
        }
      }
      $instanceStorage->resetCache($chunk);
    }

    return $repaired;
  }

}
