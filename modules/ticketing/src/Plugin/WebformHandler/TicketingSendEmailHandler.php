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
 *   id = "Ticketing Send Email",
 *   label = @Translation("Ticketing Send Email"),
 *   category = @Translation("Entity creation"),
 *   description = @Translation("Send the Ticketing email"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */

class TicketingSendEmailHandler extends WebformHandlerBase
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
        }


        // Adjust the To: email address based on form selections with this logic:
        //  1 - if a resource is selected, use it
        //  2 - if an allocations category is provided, use that category
        //  3 - if some other category is selected, use that
        //  4 - otherwise use the default queue
        if ($data['resource'] !== 'issue_not_resource_related') {
            $to = $data['resource'];
        } else if ($data['is_your_issue_related_to_allocations_'] == 'Yes') {
            $to = $data['please_select_an_allocations_category'];
        } else if (!empty($data['category'])) {
            $to = $data['category'];
        } else {
            $to = '0-Help';
        }

        // append the email domain
        $to .= '@tickets.access-ci.org';

        //  FOR TESTING
        $to_copy = $to;
        $to = 'jasperjunk@gmail.com, andrew@elytra.net';
        $to = 'jasperjunk@gmail.com'; //, andrew@elytra.net';

        // build up the email params
        $params = [];
        $params['to'] = $to;

        // add the cc's
        if ($data['cc']) {
            $ccs = explode(',', $data['cc']);
            $valid_ccs = [];
            foreach ($ccs as $cc) {
                $cc = trim($cc);
                $valid = \Drupal::service('email.validator')->isValid($cc);

                if ($valid) {
                    $valid_ccs[] = $cc;
                } else {
                    $msg = "In the CC list, \"$cc\" is not a valid email address and was not used";
                    \Drupal::messenger()->addWarning($msg);
                }
            }
            $params['headers']['cc'] = implode(',', $valid_ccs);
        }

        // get tags
        foreach ($data['tags'] as $tag_id) {
            $term = Term::load($tag_id);
            $data['tag_names'][] = $term->getName();
        }

        // get the body
        $body = (string) getXMailMessageBody($data['problem_description'], $data['tag_names']);
        $params['body'] = $body;
        
        if ($this->debug) {
            $msg = basename(__FILE__) . ':' . __LINE__ . ' -- ' . 'in postSave() = $body = ' . print_r($body, true);
            \Drupal::messenger()->addStatus($msg);
        }

        // add attachments
        if ($data['attachment']) {
            foreach ($data['attachment'] as $attachment) {
                $load = File::load($attachment);
                $file = (object) [
                    'filename' => $load->getFilename(),
                    'uri' => $load->getFileUri(),
                    'filemime' => $load->getMimeType(),
                ];
                $params['files'][] = $file;
            }
        }


        // $params['body'] = "body test"; //$render_service->render($data);
        $params['title'] = $data['subject'];

        // TESTING
        $params['title'] .= " (debug:  'To:' would be '" . $to_copy . "')";

        
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
            $msg = basename(__FILE__) . ':' . __LINE__ . ' -- ' . 'mail $result = ' . print_r($result['result'], true);
            \Drupal::messenger()->addStatus($msg);
        }
    }
}

function getXMailMessageBody($description, $tags)
{
    return twig_render_template(
        drupal_get_path('module', 'ticketing') . '/templates/ticketing-mail.html.twig',
        [
            'theme_hook_original' => 'not-applicable',
            'problem_description' => $description,
            'tags' => $tags
        ]
    );
}
