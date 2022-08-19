<?php

namespace Drupal\ticketing\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\file\Entity\File;
use Drupal\taxonomy\Entity\Term;

/**
 * Create and send the request ticketing email
 *
 * @WebformHandler(
 *   id = "Account Support Add Header",
 *   label = @Translation("Account Support Add Header"),
 *   category = @Translation("Entity creation"),
 *   description = @Translation("Account Support Add Header"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */

class AccountSupportHandler extends WebformHandlerBase
{
    var $debug = false;

    /**
     * {@inheritdoc}
     */
    public function postSave(WebformSubmissionInterface $webformSubmission, $update = true)
    {
        $data = $webformSubmission->getData();

        if ($this->debug) {
            $msg = basename(__FILE__) . ':' . __LINE__ . ' -- ' . 'in postSave() = $data = ' . print_r($data, true);
            \Drupal::messenger()->addStatus($msg);

            // $data = Array ( [your_name] => a [email] => jasperjunk@gmail.com [access_id] => [comment] => )
        }

        $to = "0-Help@tickets.access-ci.org";
        $to = "0-Help@stg-tickets.access-ci.org";

        if ($this->debug) {

            // FOR TESTING
            $to .= ', jasperjunk@gmail.com, andrew@elytra.net';
        }

        // build up the email params
        $params = [];
        $params['to'] = $to;
        $body = (string) $this->getXMailMessageBody($data);
        $params['body'] = $body;
        $params['title'] = 'account support request from ' . $data['your_name'];

        $langcode = \Drupal::currentUser()->getPreferredLangcode();
        $send = TRUE;
        $module = 'ticketing';
        $key = "ticketing";
        $mailManager = \Drupal::service('plugin.manager.mail');

        $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);

        if ($result === false || (array_key_exists('result', $result) && !$result['result'])) {
            $msg = "There was a problem sending the email";
            \Drupal::messenger()->addWarning($msg);
        }

        if ($this->debug) {
            $msg = basename(__FILE__) . ':' . __LINE__ . ' -- ' . 'mail $result = ' . print_r($result, true);
            \Drupal::messenger()->addStatus($msg);
        }
    }

    function getXMailMessageBody($data)
    {
        return twig_render_template(
            drupal_get_path('module', 'ticketing') . '/templates/account-support-mail.html.twig',
            [
                'theme_hook_original' => 'not-applicable',
                'name' => $data['your_name'],
                'email' => $data['email'],
                'access_id' => $data['access_id'],
                'comment' => $data['comment']
            ]
        );
    }
}
