<?php

declare(strict_types=1);

namespace Drupal\access_events;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Renders an in-person event's times in its venue's timezone.
 *
 * Online events keep core's behaviour untouched: the time converts to the
 * viewer's zone, which is the zone that tells them when to show up. That is
 * roughly two thirds of production events and every event today.
 *
 * An in-person event is different. Its venue clock is authoritative no matter
 * where the viewer sits, so a workshop at 9am in a Chicago building reads 9am
 * to everyone rather than 7am to someone in Los Angeles.
 *
 * Both core date-range formatters route every rendered value through
 * setTimeZone(), so overriding that one method covers both the plain
 * buildDate() path used by the three eventinstance view displays and the
 * buildDateWithIsoAttribute() path used by the six views. Overriding the
 * build methods themselves would mean duplicating the logic, and a fix applied
 * to only one of them leaves every listing rendering viewer-local while the
 * detail page looks correct.
 */
trait EventVenueTimezoneFormatterTrait {

  /**
   * {@inheritdoc}
   *
   * Defers to core unless this is an in-person event with a stored zone.
   */
  protected function setTimeZone(DrupalDateTime $date) {
    $venueZone = $this->venueTimezone();
    if ($venueZone === NULL) {
      parent::setTimeZone($date);
      return;
    }
    $date->setTimezone(new \DateTimeZone($venueZone));
  }

  /**
   * The venue timezone for the entity being rendered, if it has one.
   *
   * @return string|null
   *   An IANA zone name, or NULL to let core convert to the viewer's zone.
   */
  private function venueTimezone(): ?string {
    $entity = $this->venueTimezoneEntity ?? NULL;
    if ($entity === NULL) {
      return NULL;
    }
    // Both fields live on the series and reach the instance through
    // field_inheritance, which exposes them under names with the
    // eventinstance_default_ prefix stripped. An instance whose inheritance
    // rows are missing reads empty here, which falls through to core — the
    // safe direction, and why the backfill repairs those rows.
    $inPerson = $entity->hasField('event_in_person')
      ? (bool) $entity->get('event_in_person')->value
      : FALSE;
    if (!$inPerson) {
      return NULL;
    }
    $zone = $entity->hasField('event_timezone')
      ? trim((string) ($entity->get('event_timezone')->value ?? ''))
      : '';
    if ($zone === '' || !in_array($zone, \DateTimeZone::listIdentifiers(), TRUE)) {
      return NULL;
    }
    return $zone;
  }

  /**
   * {@inheritdoc}
   *
   * Captures the entity, which the build methods do not receive, and declares
   * the cacheability the rendered value actually depends on.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $this->venueTimezoneEntity = $items->getEntity();
    $elements = parent::viewElements($items, $langcode);

    $venueZone = $this->venueTimezone();
    if ($venueZone === NULL) {
      return $elements;
    }

    // The zone lives on the SERIES while this renders an INSTANCE, and neither
    // recurring_events nor field_inheritance bubbles any cacheability for an
    // inherited value. Without the series tag, editing a timezone leaves every
    // cached instance render showing the old one indefinitely.
    $series = method_exists($this->venueTimezoneEntity, 'getEventSeries')
      ? $this->venueTimezoneEntity->getEventSeries()
      : NULL;
    if ($series !== NULL) {
      $elements['#cache']['tags'] = array_merge(
        $elements['#cache']['tags'] ?? [],
        $series->getCacheTags()
      );
    }

    // An in-person time is the same string for every viewer, so varying the
    // cache by timezone fragments it into identical copies.
    foreach ($elements as $delta => $element) {
      if (is_numeric($delta)) {
        $elements[$delta] = $this->dropTimezoneContext($element);
      }
    }

    return $elements;
  }

  /**
   * Removes the viewer-timezone cache context from a rendered date element.
   */
  private function dropTimezoneContext(array $element): array {
    if (isset($element['#cache']['contexts'])) {
      $element['#cache']['contexts'] = array_values(
        array_diff($element['#cache']['contexts'], ['timezone'])
      );
    }
    foreach ($element as $key => $child) {
      if (is_array($child) && str_starts_with((string) $key, '#') === FALSE) {
        $element[$key] = $this->dropTimezoneContext($child);
      }
    }
    return $element;
  }

}
