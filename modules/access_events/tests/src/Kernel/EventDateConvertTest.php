<?php

declare(strict_types=1);

namespace Drupal\Tests\access_events\Kernel;

use Drupal\access_events\Plugin\Util\EventDateConvert;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests same-day detection in the event date formatter.
 *
 * EventDateConvert collapses a range to a single date plus two times when the
 * start and end fall on the same day. Getting that comparison wrong drops the
 * end date from multi-day events, of which production has 245.
 *
 * @group access_events
 */
class EventDateConvertTest extends KernelTestBase {

  /**
   * A range inside one calendar day renders one date and two times.
   */
  public function testSameCalendarDayCollapses(): void {
    $convert = new EventDateConvert('2026-05-18 14:00:00', '2026-05-18 15:00:00');

    $this->assertSame(1, $convert->sameDay, 'a range within one day is same-day');
    $this->assertStringNotContainsString('/', $convert->getEnd(),
      'the collapsed end carries a time only, no date');
  }

  /**
   * A range spanning two days in the same month is not same-day.
   */
  public function testDifferentDayInSameMonthIsNotSameDay(): void {
    $convert = new EventDateConvert('2026-05-18 14:00:00', '2026-05-19 15:00:00');

    $this->assertSame(0, $convert->sameDay, 'consecutive days are not same-day');
    $this->assertStringContainsString('05/19/26', $convert->getEnd(),
      'the end keeps its own date');
  }

  /**
   * A range whose ends share a day-of-month but not a month is not same-day.
   *
   * This is the regression: comparing date('d') sees 18 == 18 for 5/18 -> 6/18
   * and collapses a month-long event, printing an end time with no end date.
   */
  public function testSameDayOfMonthInDifferentMonthsIsNotSameDay(): void {
    $convert = new EventDateConvert('2026-05-18 14:00:00', '2026-06-18 15:00:00');

    $this->assertSame(0, $convert->sameDay,
      '5/18 -> 6/18 spans a month and must not collapse');
    $this->assertStringContainsString('06/18/26', $convert->getEnd(),
      'the end keeps its own date');
  }

  /**
   * A range whose ends share a day-of-month but not a year is not same-day.
   */
  public function testSameDayOfMonthInDifferentYearsIsNotSameDay(): void {
    $convert = new EventDateConvert('2025-05-18 14:00:00', '2026-05-18 15:00:00');

    $this->assertSame(0, $convert->sameDay,
      '5/18/25 -> 5/18/26 spans a year and must not collapse');
    $this->assertStringContainsString('05/18/26', $convert->getEnd(),
      'the end keeps its own date');
  }

}
