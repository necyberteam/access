<?php

namespace Drupal\ticketing\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Create and send the request to add an organization to the access orginizations list
 *
 * @WebformHandler(
 *   id = "ACCESS Orgs List Add Header",
 *   label = @Translation("ACCESS Orgs List Add Header"),
 *   category = @Translation("Entity creation"),
 *   description = @Translation("ACCESS Orgs List Add Header"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class RequestOrgsListAddHandler extends WebformHandlerBase {
  public $debug = TRUE;

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webformSubmission, $update = TRUE) {
    $data = $webformSubmission->getData();

    if ($this->debug) {
      $msg = basename(__FILE__) . ':' . __LINE__ . ' -- ' . 'in postSave() = $data = ' . print_r($data, TRUE);
      \Drupal::messenger()->addStatus($msg);

      // $data = Array ( [your_name] => a [email] => jasperjunk@gmail.com [access_id] => [comment] => )
    }

    $to = "ACCESS-Allocations-DevOPS@tickets.access-ci.org";

    if ($this->debug) {

      // FOR TESTING.
      $to .= ', jasperjunk@gmail.com';
    }

    // Build up the email params.
    $params = [];
    $params['to'] = $to;
    $body = (string) $this->getXMailMessageBody($data);
    $params['body'] = $body;
    $params['title'] = 'request to an an organization to ACCESS organization list from ' . $data['your_name'];

    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = TRUE;
    $module = 'ticketing';
    $key = "ticketing";
    $mailManager = \Drupal::service('plugin.manager.mail');

    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);

    if ($result === FALSE || (array_key_exists('result', $result) && !$result['result'])) {
      $msg = "There was a problem sending the email";
      \Drupal::messenger()->addWarning($msg);
    }

    if ($this->debug) {
      $msg = basename(__FILE__) . ':' . __LINE__ . ' -- ' . 'mail $result = ' . print_r($result, TRUE);
      \Drupal::messenger()->addStatus($msg);
    }
  }

  public function getXMailMessageBody($data) {
    return twig_render_template(
          drupal_get_path('module', 'ticketing') . '/templates/request-orgs-list-add-mail.html.twig',
          [
            'theme_hook_original' => 'not-applicable',
            'name' => $data['your_name'],
            'email' => $data['your_email'],
            'organization' => $data['your_organization'],
          ]
      );
  }

}
