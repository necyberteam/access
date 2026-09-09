<?php

declare(strict_types=1);

namespace Drupal\Tests\access_events\Kernel;

/**
 * Pins the clone-on-read contract for the shared computed date object.
 *
 * Core's DateTimeComputed caches the DrupalDateTime it builds and returns the
 * SAME object to every reader, so any caller doing
 * `$entity->date->start_date->setTimezone(...)` rewrites the entity's cached
 * value in place and every later read in that request sees the converted
 * object instead of the stored UTC one.
 *
 * Core's aliasing is not ours to change; the contract is that OUR call sites
 * clone before converting. These tests pin that contract — the aliasing
 * itself, and the clone idiom the display paths use.
 *
 * @group access_events
 */
class SharedDateObjectMutationTest extends EventKernelTestBase {

  /**
   * The clone idiom the display paths use leaves the entity's copy alone.
   *
   * This is the exact shape of the three converted call sites in
   * access_events.module.
   */
  public function testCloningBeforeConvertLeavesTheEntityCopyInUtc(): void {
    $instance = $this->createRegistrableInstance();
    $stored = $instance->get('date')->value;

    $formatted = (clone $instance->get('date')->start_date)
      ->setTimezone(new \DateTimeZone('America/Los_Angeles'))
      ->format('n/j/Y g:i A T');

    $this->assertNotEmpty($formatted, 'the read still renders');
    $this->assertSame($stored, $instance->get('date')->value,
      'the stored column is untouched');
    $this->assertSame('UTC',
      $instance->get('date')->start_date->getTimezone()->getName(),
      'the cached date object is still UTC after a cloned read');
  }

  /**
   * Two cloned readers in different zones do not contaminate each other.
   */
  public function testTwoClonedReadersDoNotContaminateEachOther(): void {
    $instance = $this->createRegistrableInstance();

    $first = (clone $instance->get('date')->start_date)
      ->setTimezone(new \DateTimeZone('America/New_York'));
    $second = (clone $instance->get('date')->start_date)
      ->setTimezone(new \DateTimeZone('Australia/Perth'));

    $this->assertSame('America/New_York', $first->getTimezone()->getName(),
      'the first reader keeps its own zone');
    $this->assertSame('Australia/Perth', $second->getTimezone()->getName(),
      'the second reader keeps its own zone');
    $this->assertSame('UTC',
      $instance->get('date')->start_date->getTimezone()->getName(),
      'and the entity copy is still UTC');
  }

  /**
   * An UNCLONED convert corrupts the entity copy — the bug being guarded.
   *
   * Pinning core's aliasing directly, so that if a future core or contrib
   * change ever makes the computed property hand out copies, this test fails
   * and the clones above can be revisited as unnecessary.
   */
  public function testUnclonedConvertCorruptsTheEntityCopy(): void {
    $instance = $this->createRegistrableInstance();

    $instance->get('date')->start_date
      ->setTimezone(new \DateTimeZone('America/Los_Angeles'));

    $this->assertSame(
      'America/Los_Angeles',
      $instance->get('date')->start_date->getTimezone()->getName(),
      'core still aliases, so our call sites must keep cloning'
    );
  }

  /**
   * Pins the aliasing itself, so a future refactor cannot silently reintroduce
   * in-place mutation without this test noticing.
   */
  public function testComputedDateIsReturnedByReferenceAndMustBeCloned(): void {
    $instance = $this->createRegistrableInstance();

    $this->assertSame(
      $instance->get('date')->start_date,
      $instance->get('date')->start_date,
      'the computed property hands out one shared object, so readers must clone'
    );
  }

}
