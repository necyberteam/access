<?php

declare(strict_types=1);

namespace Drupal\Tests\access_events\Kernel;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\recurring_events\Entity\EventInstance;

/**
 * Tests how an event's own timezone affects the rendered time.
 *
 * Online events keep viewer-local conversion, which is what nearly two thirds
 * of production events are and what every event does today. In-person events
 * render in the venue's zone instead, because the venue clock is authoritative
 * regardless of where the viewer sits.
 *
 * The rule underneath both branches is the one that matters: a displayed time's
 * zone abbreviation must come from the same date object that produced the
 * number. Attaching the event's zone to a viewer-converted number would give
 * "12:00 PM CDT" to an LA viewer of a 2pm Chicago event — a real-looking time
 * naming a different instant.
 *
 * Two formatters reach these surfaces and they take different render paths:
 * the three eventinstance view displays use daterange_custom (plain #markup via
 * buildDate), while six views use daterange_default (a #theme=time element via
 * buildDateWithIsoAttribute). Both inherit from the same core base, so one
 * override covers both — but a fix verified only on the detail page leaves
 * every listing rendering viewer-local.
 *
 * @group access_events
 */
class EventTimezoneDisplayTest extends EventKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->seedTimezoneFields();

    // daterange_default renders through a date_format config entity, which
    // ships in system's config/install and so is absent from this minimal
    // kernel environment.
    if (!\Drupal::entityTypeManager()->getStorage('date_format')->load('html_datetime')) {
      \Drupal::entityTypeManager()->getStorage('date_format')->create([
        'id' => 'html_datetime',
        'label' => 'HTML Datetime',
        'locked' => TRUE,
        'pattern' => 'Y-m-d\\TH:i:sO',
      ])->save();
    }
  }

  /**
   * Builds an instance whose series carries the given zone and modality.
   */
  private function instanceWithZone(string $zone, bool $inPerson): EventInstance {
    $instance = $this->createRegistrableInstance();
    $series = $instance->getEventSeries();
    $series->set('field_event_timezone', $zone);
    $series->set('field_event_in_person', $inPerson);
    $series->save();

    // Pin a summer date: the base fixture sits in January, where the zone
    // abbreviations are the standard-time ones (PST/CST) rather than the
    // daylight ones the assertions name.
    $instance->set('date', [
      'value' => '2026-07-15T19:00:00',
      'end_value' => '2026-07-15T20:00:00',
    ]);
    $instance->save();

    // The inheritance rows are written per instance per field, so a field set
    // after the instance exists needs the same repair the backfill performs.
    \Drupal::service('access_events.timezone_backfill')->rebuildInheritance();
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    return $this->reloadInstance($instance);
  }

  /**
   * Renders an instance's date field through a named formatter.
   */
  private function renderDate(EventInstance $instance, string $formatter, array $settings): string {
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    $items = $instance->get('date');
    $build = $items->view([
      'type' => $formatter,
      'label' => 'hidden',
      'settings' => $settings,
    ]);
    return (string) \Drupal::service('renderer')->renderInIsolation($build);
  }

  /**
   * An online event still converts to the viewer's zone.
   *
   * This is the common case — roughly two thirds of production events — and it
   * must not change. The number and the label both move to the viewer.
   */
  public function testOnlineEventRendersInTheViewersZone(): void {
    $instance = $this->instanceWithZone('America/Chicago', FALSE);
    $original = date_default_timezone_get();
    date_default_timezone_set('America/Los_Angeles');
    try {
      $output = $this->renderDate($instance, 'daterange_custom', [
        'date_format' => 'n/j/Y g:i A T',
        'separator' => '-',
        'from_to' => 'both',
        'timezone_override' => '',
      ]);
    }
    finally {
      date_default_timezone_set($original);
    }

    $this->assertStringContainsString('PDT', $output,
      'an online event shows the viewer their own zone');
    $this->assertStringNotContainsString('CDT', $output,
      'and not the event zone, which would be a different instant');
  }

  /**
   * An in-person event renders in the venue's zone, identically for everyone.
   */
  public function testInPersonEventRendersInTheVenueZoneForEveryViewer(): void {
    $instance = $this->instanceWithZone('America/Chicago', TRUE);

    $rendered = [];
    $original = date_default_timezone_get();
    foreach (['America/New_York', 'America/Los_Angeles', 'UTC'] as $viewerZone) {
      date_default_timezone_set($viewerZone);
      try {
        $rendered[$viewerZone] = $this->renderDate($instance, 'daterange_custom', [
          'date_format' => 'n/j/Y g:i A T',
          'separator' => '-',
          'from_to' => 'both',
          'timezone_override' => '',
        ]);
      }
      finally {
        date_default_timezone_set($original);
      }
    }

    foreach ($rendered as $viewerZone => $output) {
      $this->assertStringContainsString('CDT', $output,
        "a viewer in $viewerZone sees the venue's zone");
    }
    $this->assertCount(1, array_unique($rendered),
      'and every viewer sees exactly the same string');
  }

  /**
   * The in-person branch also reaches the views formatter.
   *
   * daterange_default takes a different render path — buildDateWithIsoAttribute
   * rather than buildDate — and six views use it, including the main events
   * listing and the API view. An override that covers only daterange_custom
   * fixes the detail page while every listing keeps rendering viewer-local.
   */
  public function testInPersonBranchAppliesToTheViewsFormatterToo(): void {
    $instance = $this->instanceWithZone('America/Chicago', TRUE);

    $original = date_default_timezone_get();
    date_default_timezone_set('America/Los_Angeles');
    try {
      $output = $this->renderDate($instance, 'daterange_default', [
        'format_type' => 'html_datetime',
        'separator' => '::',
        'from_to' => 'both',
        'timezone_override' => '',
      ]);
    }
    finally {
      date_default_timezone_set($original);
    }

    // Chicago in July is UTC-5; a Los Angeles viewer would get -0700. The 'O'
    // format emits the offset without a colon.
    $this->assertStringContainsString('-0500', $output,
      'the views formatter emits the venue offset, not the viewer’s');
    $this->assertStringNotContainsString('-0700', $output,
      'and specifically not the viewer’s offset');
    $this->assertStringContainsString('T14:00:00', $output,
      'the wall clock is the venue’s 2pm, not the viewer’s noon');
  }

  /**
   * A zero-duration event exercises the other arm of the equality split.
   *
   * viewElements() branches on whether start and end share a timestamp, and
   * the arm for equal timestamps is the one no ordinary event reaches. It is
   * also the shape that breaks the display alter this work removes.
   */
  public function testZeroDurationInPersonEventStillRendersTheVenueZone(): void {
    $instance = $this->instanceWithZone('America/Chicago', TRUE);
    $instance->set('date', [
      'value' => '2026-07-15T19:00:00',
      'end_value' => '2026-07-15T19:00:00',
    ]);
    $instance->save();

    $original = date_default_timezone_get();
    date_default_timezone_set('America/Los_Angeles');
    try {
      $output = $this->renderDate($this->reloadInstance($instance), 'daterange_custom', [
        'date_format' => 'n/j/Y g:i A T',
        'separator' => '-',
        'from_to' => 'both',
        'timezone_override' => '',
      ]);
    }
    finally {
      date_default_timezone_set($original);
    }

    $this->assertStringContainsString('CDT', $output,
      'the equal-timestamp arm resolves the venue zone as well');
  }

  /**
   * The render depends on the series, so it must carry the series cache tag.
   *
   * The fields live on the series while these surfaces render the instance, and
   * neither contrib module bubbles any cacheability for an inherited value.
   * Without the tag, editing a series' timezone leaves every cached instance
   * render showing the old zone until something else invalidates it.
   */
  public function testRenderCarriesTheSeriesCacheTag(): void {
    $instance = $this->instanceWithZone('America/Chicago', TRUE);
    $seriesTag = 'eventseries:' . $instance->getEventSeries()->id();

    $build = $instance->get('date')->view([
      'type' => 'daterange_custom',
      'label' => 'hidden',
      'settings' => [
        'date_format' => 'n/j/Y g:i A T',
        'separator' => '-',
        'from_to' => 'both',
        'timezone_override' => '',
      ],
    ]);
    $renderer = \Drupal::service('renderer');
    $renderer->renderInIsolation($build);

    $tags = $build['#cache']['tags'] ?? [];
    $this->assertContains($seriesTag, $tags,
      'a timezone edit on the series invalidates this render');
  }

}
