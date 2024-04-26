<?php

namespace Drupal\ccmnet\Plugin\EmailBuilder;

use Drupal\symfony_mailer\Processor\EmailBuilderBase;
use Drupal\symfony_mailer\EmailInterface;

/**
 * Email Builder plug-in for the ccmnet module.
 *
 * @EmailBuilder(
 *   id = "ccmnet",
 *   sub_types = {
 *     "mentor_changed" = @Translation("Mentor Changed notification"),
 *     "mentee_changed" = @Translation("Mentee Changed notification"),
 *     "liaison_mentor_mentee_changed" = @Translation("Liaison Mentor/Mentee Changed notification"),
 *   },
 *   common_adjusters = {"email_subject", "email_body"},
 * )
 */
class MentorshipEmailBuilder extends EmailBuilderBase {
  public function build(EmailInterface $email) {
    $email->setFrom('noreply@ccmnet.org');
  }
}
