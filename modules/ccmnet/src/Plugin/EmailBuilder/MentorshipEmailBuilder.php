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
 *     "mentorship_in_progress" = @Translation("Notify liaison and ccmnet pm that mentorship has switched to progress"),
 *     "mentorship_created_admin" = @Translation("Notify ccmnet admin that mentorship has been created"),
 *     "mentorship_approved_ccmnet_pm" = @Translation("Notify ccmnet pm that mentorship has been approved"),
 *   },
 *   common_adjusters = {"email_subject", "email_body"},
 * )
 */
class MentorshipEmailBuilder extends EmailBuilderBase {
  public function build(EmailInterface $email) {
    $email->setFrom('noreply@ccmnet.org');
  }
}
