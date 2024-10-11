<?php

namespace Drupal\access_affinitygroup\Plugin\EmailBuilder;

use Drupal\symfony_mailer\Processor\EmailBuilderBase;
use Drupal\symfony_mailer\EmailInterface;

/**
 * Email Builder plug-in for the access_affinitygroup module.
 *
 * @EmailBuilder(
 *   id = "affinitygroup",
 *   sub_types = {
 *     "simplelist_error" = @Translation("Simplelist Error"),
 *   },
 *   common_adjusters = {"email_subject", "email_body"},
 * )
 */
class AgEmailBuilder extends EmailBuilderBase {
  public function build(EmailInterface $email) {
    $email->setFrom('noreply@access-ci.org');
  }
}
