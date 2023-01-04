<?php

namespace Drupal\access_affinitygroup\Commands;

use Drupal\access_affinitygroup\Plugin\ConstantContactApi;
use Drush\Commands\DrushCommands;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * A Drush commandfile for Affinity Groups.
 *
 * @package Drupal\access_affinitygroup\Commands
 */
class AffinityGroupCommands extends DrushCommands {

  /**
   * Add existing Affinity Group members to Constant Contact lists.
   *
   * Save all Affinity Groups to trigger the creation of the
   * associated Constant Contact list. Then add all existing members of
   * the group to that Constant Contact list.
   *
   * @command access_affinitygroup:initConstantContact
   * @aliases initConstantContact
   * @usage access_affinitygroup:initConstantContact
   */
  public function initConstantContact() {
    // Get all the Affinity Groups.
    $nids = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', 'affinity_group')
      ->execute();
    $nodes = Node::loadMultiple($nids);
    $cca = new ConstantContactApi();

    foreach ($nodes as $node) {
      $this->output()->writeln($node->getTitle());
      // If there isn't a Constant Contact list_id,
      // trigger save to generate Constant Contact list.
      $list_id = $node->get('field_list_id')->value;
      $this->output()->writeln($list_id);
      if (!$list_id || strlen($list_id) < 1) {
        $node->save();
        $list_id = $node->get('field_list_id')->value;
      }

      // Get the Users who have flagged the associated term.
      $term = $node->get('field_affinity_group');
      $flag_service = \Drupal::service('flag');
      $flags = $flag_service->getAllEntityFlaggings($term->entity);
      foreach ($flags as $flag) {
        $uid = $flag->get('uid')->target_id;
        $this->output()->writeln($uid);
        $user = User::load($uid);

        $first_name = $user->get('field_user_first_name')->getString();
        $last_name = $user->get('field_user_last_name')->getString();

        // CC names can only be 50 chars.
        $first_name = substr($first_name, 0, 49);
        $last_name = substr($last_name, 0, 49);

        $this->output()->writeln($first_name);
        $this->output()->writeln($last_name);

        // Get the Constant Contact id for the User.
        $field_val = $user->get('field_constant_contact_id')->getValue();
        if (!empty($field_val) && $field_val != 0) {
          $cc_id = $field_val[0]['value'];
          $this->output()->writeln($first_name . ' ' . $last_name . ' already has cc id: ' . $cc_id);
        }
        else {
          // User did not already have the CC id.
          // Check if they are already in CC.
          $resp = $cca->apiCall('/contacts?status=all&email=' . $user->getEmail());
          if ($resp->contacts) {
            $cc_id = $resp->contacts[0]->contact_id;
            $this->output()->writeln($cc_id);
          }
          else {
            // Try to add to CC.
            $cc_id = $cca->addContact($first_name, $last_name, $user->getEmail());
            // Delay for api limit.
            usleep(500);
          }
          $this->output()->writeln($cc_id);
          $user->set('field_constant_contact_id', $cc_id);
          $user->save();
          $this->output()->writeln('Added ' . $first_name . ' ' . $last_name);
        }
        $post_data = [
          'source' => [
            'contact_ids' => [$cc_id],
          ],
          'list_ids' => [$list_id],
        ];
        // $this->output()->writeln(var_dump($post_data));
        $cca->apiCall('/activities/add_list_memberships', json_encode($post_data), 'POST');
        // Delay for api limit.
        usleep(500);
      }
    }
  }

}
