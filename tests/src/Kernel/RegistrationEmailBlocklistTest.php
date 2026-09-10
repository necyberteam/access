<?php

namespace Drupal\Tests\access\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use Symfony\Component\Yaml\Yaml;

/**
 * Covers D8-2849: blocking spam registration domains at account creation.
 *
 * Guards the real, deployed advanced_email_validation.settings config
 * against regressions such as a removed banned domain, so the parsed config
 * file (not a hardcoded list) is the source of truth for this test.
 *
 * @group access
 */
class RegistrationEmailBlocklistTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'access',
    'access_affinitygroup',
    'user',
    'system',
    'field',
    'text',
    'filter',
    'key',
    'advanced_email_validation',
  ];

  /**
   * Banned domains parsed from the real exported config.
   *
   * @var string[]
   */
  protected array $bannedDomains;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $configPath = \Drupal::root() . '/sites/default/config/default/advanced_email_validation.settings.yml';
    $data = Yaml::parseFile($configPath);

    // The real config has rules.mx_lookup = true, which triggers a live DNS
    // MX lookup for every validated address. Leaving that on would make the
    // non-banned control case in this test dependent on outbound network
    // access / DNS resolution and flaky in CI or offline environments. Force
    // it off here; it is unrelated to the banned-domain behavior under test.
    $data['rules']['mx_lookup'] = FALSE;

    $this->bannedDomains = $data['domain_lists']['banned'];

    // Install the module's config schema, then overwrite the data with the
    // real (mx_lookup-disabled) config, before the 'user' entity schema is
    // installed so the mail base field picks up the AEVNewEmail constraint
    // with this config in place.
    $this->installConfig(['advanced_email_validation']);
    $this->config('advanced_email_validation.settings')->setData($data)->save();

    $this->installEntitySchema('user');
    $this->installConfig(['user']);
  }

  /**
   * Fails loudly if a banned domain is ever removed from the real config.
   */
  public function testExpectedBannedDomainsArePresentInRealConfig(): void {
    $this->assertContains('centraltermites.site', $this->bannedDomains);
    $this->assertContains('clarionstreams.site', $this->bannedDomains);
    $this->assertContains('slappergiraffe.site', $this->bannedDomains);
  }

  /**
   * Data provider of the spam addresses named in D8-2849.
   */
  public static function bannedAddressProvider(): array {
    return [
      'centraltermites.site' => ['landwaggingby@centraltermites.site'],
      'clarionstreams.site' => ['whisperingprobablepjt@clarionstreams.site'],
      'slappergiraffe.site' => ['thinkbigprestovbv@slappergiraffe.site'],
    ];
  }

  /**
   * New accounts on banned spam domains must fail validation on ::mail.
   *
   * @dataProvider bannedAddressProvider
   */
  public function testBannedDomainIsRejected(string $mail): void {
    $account = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $mail,
    ]);

    $violations = $account->validate()->getByFields(['mail']);

    $this->assertGreaterThan(0, $violations->count(), sprintf('Expected a violation on the mail field for banned address %s.', $mail));
  }

  /**
   * A new account on an unrelated domain must produce no ::mail violation.
   */
  public function testNonBannedDomainIsAccepted(): void {
    $account = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
    ]);

    $violations = $account->validate()->getByFields(['mail']);

    $this->assertCount(0, $violations, 'A non-banned domain must not produce a violation on the mail field.');
  }

}
