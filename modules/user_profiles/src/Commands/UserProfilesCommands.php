<?php

namespace Drupal\user_profiles\Commands;

use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\webform\Entity\WebformSubmission;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile to migrate settings from profile data from
 * one user to another.
 *
 * @package Drupal\user_profiles\Commands
 */
class UserProfilesCommands extends DrushCommands {

  /**
   * Migrate / merge user profile settings from one user to another.  The following
   * will get updated:
   *  - ownership of resources
   *  - affinity groups
   *  - flags:  interest, skill, upvote, interested-in-project
   *  - roles
   *  - engagements
   *
   * @command user_profiles:mergeUser
   * @param string $from_user_id
   *   Id of user id to merge from.
   * @param string $to_user_id
   *   Id of user id to merge to.
   *
   * @aliases mergeUser
   * @usage user_profiles:mergeUser
   */
  public function mergeUser(string $from_user_id, string $to_user_id) {

    $this->output()->writeln("------------- Merge user $from_user_id into $to_user_id ---------------------------------");

    $user_from = User::load($from_user_id);
    $user_to = User::load($to_user_id);

    if (!$user_from) {
      $this->output()->writeln("  *** No user found with id $from_user_id");
      return;
    }
    if (!$user_to) {
      $this->output()->writeln("  *** No user found with id $to_user_id");
      return;
    }

    $first_name1 = $user_from->get('field_user_first_name')->getString();
    $last_name1 = $user_from->get('field_user_last_name')->getString();
    $first_name2 = $user_to->get('field_user_first_name')->getString();
    $last_name2 = $user_to->get('field_user_last_name')->getString();

    $this->output()->writeln("  Merging from '$first_name1 $last_name1' to '$first_name2 $last_name2'");

    // $this->mergeResources($user_from, $user_to);
    // $this->mergeAfffinityGroups($user_from, $user_to);
    // $this->mergeFlag('interest', $user_from, $user_to);
    // $this->mergeFlag('skill', $user_from, $user_to);
    // $this->mergeFlag('upvote', $user_from, $user_to);
    // $this->mergeFlag('interested_in_project', $user_from, $user_to);
    // $this->mergeRoles($user_from, $user_to);
    // $this->mergeUserFields($user_from, $user_to);
    $this->mergeNodes($user_from, $user_to);
  }

  /**
   * Merge nodes from $user_from to $user_to.
   *
   * @param \Drupal\user\Entity\User $user_from
   *   From user.
   * @param \Drupal\user\Entity\User $user_to
   *   To user.
   *
   *
   * do node mass update on all nodes,
   */
  private function mergeNodes(User $user_from, User $user_to) {

    \Drupal::moduleHandler()->loadInclude('node', 'inc', 'node.admin');

    $nodes = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('uid', $user_from->id())
      ->execute();

    if (count($nodes) == 0) {
      $this->output()->writeln("No nodes to migrate");
      return;
    }

    $this->output()->writeln("Migrating nodes");
    $this->output()->writeln("  Updating these nodes: ");
    foreach ($nodes as $nid) {
      $node = \Drupal\node\Entity\Node::load($nid);
      $this->output()->writeln("    " . $node->getTitle());
    }

    node_mass_update($nodes, ['uid' => $user_to->id()], NULL, TRUE);
  }

  /**
   * Merge various fields from $user_from to $user_to.
   *
   * @param \Drupal\user\Entity\User $user_from
   *   From user.
   * @param \Drupal\user\Entity\User $user_to
   *   To user.
   */
  private function mergeUserFields(User $user_from, User $user_to) {
    $this->output()->writeln("Merging user fields");

    /* Here's a list of all fields -- only a subset of these are migrated.

    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('user', 'user');

    [0] => uid
    [1] => uuid
    [2] => langcode
    [3] => preferred_langcode
    [4] => preferred_admin_langcode
    [5] => name
    [6] => pass
    [7] => mail
    [8] => timezone
    [9] => status
    [10] => created
    [11] => changed
    [12] => access
    [13] => login
    [14] => init
    [15] => roles
    [16] => default_langcode
    [17] => mail_change
    [18] => role_change
    [19] => path
    [20] => field_blocked_ag_tax
    [21] => field_carnegie_code
    [22] => field_cider_resources
    [23] => field_citizenships
    [24] => field_constant_contact_id
    [25] => field_current_degree_program
    [26] => field_current_occupation
    [27] => field_cv_resume
    [28] => field_degree
    [29] => field_domain_access
    [30] => field_domain_admin
    [31] => field_domain_all_affiliates
    [32] => field_hpc_experience
    [33] => field_institution
    [34] => field_is_cc
    [35] => field_region
    [36] => field_user_first_name
    [38] => user_picture
     */

    $merge_fields = [
      'field_citizenships',
      'field_current_degree_program',
      'field_current_occupation',
      'field_cv_resume',
      'field_degree',
      'field_hpc_experience',
      'field_institution',
      'user_picture',
    ];

    // For each of the fields listed above, only replace the value for the
    // to_user if to_user field is empty and if the from-user is not empty.
    foreach ($merge_fields as $merge_field) {
      $to_val = $user_to->get($merge_field)->getValue();
      $from_val = $user_from->get($merge_field)->getValue();
      if (!$to_val && $from_val) {
        $value_text = '';
        if (isset($from_val[0]['value'])) {
          $value_text = "with value '" . $from_val[0]['value'] . "'";
        }
        $this->output()->writeln("  Merging field '$merge_field' $value_text");
        $user_to->get($merge_field)->setValue($from_val);
        $user_to->set($merge_field, $from_val);
      }
    }

    // Migrate the boolean field_is_cc manually
    if (
      $user_from->get('field_is_cc')->getValue()[0]['value']
      && !$user_to->get('field_is_cc')->getValue()[0]['value']
    ) {
      $this->output()->writeln("  Setting to-user as a campus champion");
      $user_to->set('field_is_cc', TRUE);
    }

    // Per Andrew:  Carnegie code should only be copied if the old institution
    // is the same as the new institution.
    $from_carnegie_code = $user_from->get('field_carnegie_code')->getValue();
    if ($from_carnegie_code) {
      $from_carnegie_code = $from_carnegie_code[0]['value'];
    }
    if ($from_carnegie_code) {
      $from_inst = $user_from->get('field_institution')->getValue();
      if ($from_inst) {
        $from_inst = $from_inst[0]['value'];
      }
      $to_inst = $user_to->get('field_institution')->getValue();
      if ($to_inst) {
        $to_inst = $to_inst[0]['value'];
      }
      if ($from_inst === $to_inst) {
        $this->output()->writeln("  Merging 'field_carnegie_code' with value '$from_carnegie_code'");
        $user_to->set('field_carnegie_code', $from_carnegie_code);
      } else {
        $this->output()->writeln("  Not merging 'field_carnegie_code' with "
          . "value '$from_carnegie_code' because differing institutions ('$from_inst' and '$to_inst')");
      }
    } else {
      $this->output()->writeln("  From user has no carnegie code to merge.");
    }

    // Per Andrew:  field_region should only be added to, not replaced.
    $this->output()->writeln("  Merging region / program fields");
    $from_region = $user_from->get('field_region')->referencedEntities();
    foreach ($from_region as $from_program) {
      $to_region = $user_to->get('field_region')->referencedEntities();
      if (count($to_region) == 0) {
        $this->output()->writeln("    Adding region / program '"
          . $from_program->getName() . "'");
        $user_to->set('field_region', $from_program->id());
      } else {
        if (!array_filter(
          $to_region,
          function ($to_program) use ($from_program) {
            return $to_program->id() == $from_program->id();
          }
        )) {
          $this->output()->writeln(
            "    Appending program '"
              . $from_program->getName() . "'"
          );
          $user_to->get('field_region')->appendItem(
            [
              'target_id' => $from_program->id(),
            ]
          );
        } else {
          $this->output()->writeln(
            "    Already a member of program '"
              . $from_program->getName() . "'"
          );
        }
      }
    }

    $user_to->save();
  }

  /**
   * Merge the roles from $user_from to $user_to.
   *
   * @param \Drupal\user\Entity\User $user_from
   *   From user.
   * @param \Drupal\user\Entity\User $user_to
   *   To user.
   */
  private function mergeRoles(User $user_from, User $user_to) {
    $this->output()->writeln("Merging roles");

    $roles = $user_from->getRoles();
    $changes = FALSE;
    foreach ($roles as $role) {
      if (in_array($role, ['anonymous', 'authenticated', 'administrator'])) {
        $this->output()->writeln("  Skipping role '$role' - can't be assigned programatically");
      } else {
        $this->output()->writeln("  Merging role '$role'");
        $user_to->addRole($role);
        $changes = TRUE;
      }
    }
    if ($changes) {
      $user_to->save();
    }
  }

  /**
   * Change the ownership of any resources from $user_from to $user_to.
   *
   * @param \Drupal\user\Entity\User $user_from
   *   From user.
   * @param \Drupal\user\Entity\User $user_to
   *   To user.
   *
   *
   * update all webform submissions?
   * can node_mass_update do this?
   */
  private function mergeResources(User $user_from, User $user_to) {
    $this->output()->writeln("Merging resources");

    $ws_query = \Drupal::entityQuery('webform_submission')
      ->condition('uid', $user_from->id())
      ->condition('uri', '/form/resource');
    $ws_results = $ws_query->execute();
    if ($ws_results == NULL) {
      $this->output()->writeln("  From-user has no resources");
      return;
    }
    foreach ($ws_results as $ws_result) {
      $ws = WebformSubmission::load($ws_result);
      $ws_data = $ws->getData();
      $resource_title = $ws_data['title'];
      $ws->setOwner($user_to);
      $ws->save();
      $this->output()->writeln("  Updated ownership of resource '$resource_title'");
    }
  }

  /**
   * Copy the flag setting.
   *
   * @param string $flag_name
   *   The name of the flag to merge.
   * @param \Drupal\user\Entity\User $user_from
   *   From user.
   * @param \Drupal\user\Entity\User $user_to
   *   To user.
   */
  private function mergeFlag($flag_name, User $user_from, User $user_to) {
    $this->output()->writeln("Merging flags with name '$flag_name'");

    $term = \Drupal::database()->select('flagging', 'fl');
    $term->condition('fl.uid', $user_from->id());
    $term->condition('fl.flag_id', $flag_name);
    $term->fields('fl', ['entity_id']);
    $flagged_items = $term->execute()->fetchCol();
    if ($flagged_items == NULL) {
      $this->output()->writeln("  From-user has no flags with name '$flag_name'");
      return;
    }

    foreach ($flagged_items as $flagged_item) {
      $term = Term::load($flagged_item);
      $title = $term->get('name')->value;

      // Check if already flagged. If not, set the flag.
      $flag_service = \Drupal::service('flag');
      $flag = $flag_service->getFlagById($flag_name);
      $flag_status = $flag_service->getFlagging($flag, $term, $user_to);
      if (!$flag_status) {
        $bundles = $flag->getBundles();
        if (!empty($bundles) && !in_array($term->bundle(), $bundles)) {
          $this->output()->writeln("*** Error, flag '$flag_name' with title '$title' has bundle "
            . $term->bundle() . " which is not in allowed list: {"
            . implode(', ', $bundles) . '} -- skipping this one.');
        } else {
          $this->output()->writeln("  Adding flag $flag_name with title '$title' to to-user");
          $flag_service->flag($flag, $term, $user_to);
        }
      } else {
        $this->output()->writeln("  To-user already has flag $flag_name with title '$title'");
      }
    }
  }

  /**
   * Merge affinity groups.
   *
   * @param \Drupal\user\Entity\User $user_from
   *   From user.
   * @param \Drupal\user\Entity\User $user_to
   *   To user.
   */
  private function mergeAfffinityGroups(User $user_from, User $user_to) {

    $this->output()->writeln("Merging affinity groups");

    // Get user_to's blocked ag taxonomy ids.
    $user_blocked_tid_array = $user_to->get('field_blocked_ag_tax')->getValue();
    $user_blocked_tids = [];
    foreach ($user_blocked_tid_array as $user_blocked_tid) {
      $user_blocked_tids[] = $user_blocked_tid['target_id'];
    }
    // $this->output()->writeln("  user-to blocked ag tids: "
    // . implode(' ', $user_blocked_tids));

    // Get all the affinity groups of $user_from.
    $query = \Drupal::database()->select('flagging', 'fl');
    $query->condition('fl.uid', $user_from->id());
    $query->condition('fl.flag_id', 'affinity_group');
    $query->fields('fl', ['entity_id']);
    $ag_ids = $query->execute()->fetchCol();

    if ($ag_ids == NULL) {
      $this->output()->writeln("  from-user is not a member of any affinity groups");
      return;
    }
    // For each affinity group id, add user_to to that affinity group.
    foreach ($ag_ids as $ag_id) {
      $this->addUserToAg($user_to, $ag_id, $user_blocked_tids);
    }
  }

  /**
   * Add a user to an affinity group (unless on the users's block list).
   *
   * @param \Drupal\user\Entity\UserInterface $to_user
   *   To user.
   * @param string $ag_id
   *   Affinity group id.
   * @param array $user_blocked_tids
   *   Array of blocked affinity group taxonomy ids.
   */
  private function addUserToAg(UserInterface $to_user, string $ag_id, array $user_blocked_tids) {
    // Get the node id of the affinity group.
    $query = \Drupal::database()->select('taxonomy_index', 'ti');
    $query->condition('ti.tid', $ag_id);
    $query->fields('ti', ['nid']);
    $affinity_group_nid = $query->execute()->fetchCol();

    if (!isset($affinity_group_nid[0])) {
      // Not sure how or if this could happen, or what it would mean, but
      // Miles' code in CommunityPersonaController.php line 36 also
      // ignores this condition.
      $this->output()->writeln("*** Warning, from-user flagged as member of affinity group #$ag_id but no such affinity group found - skipping this affinity group");
      return;
    }

    // Load that affinity group.
    $ag_nid = $affinity_group_nid[0];
    /** @var Drupal\node\Entity\Node $affinity_group_loaded */
    $affinity_group_loaded = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->load($ag_nid);

    if (!$affinity_group_loaded) {
      $this->output()->writeln("*** Warning, could not load affinity group with node_id #$ag_nid - skipping this affinity group");
      return;
    }

    $ag_title = $affinity_group_loaded->getTitle();

    // Get AG taxonomy id.
    $ag_taxonomy = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['name' => $ag_title]);
    $ag_taxonomy = reset($ag_taxonomy);

    if (!$ag_taxonomy) {
      $this->output()->writeln("*** Warning, no taxonomy id found for AG title '$ag_title'");
      return;
    }

    $ag_tax_id = $ag_taxonomy->id();

    // Check if ag is on block list.
    if (in_array($ag_tax_id, $user_blocked_tids)) {
      $this->output()->writeln("  Not adding '$ag_title' (tid #$ag_tax_id) because on to-user's block list");
      return;
    }

    // Check if already flagged. If not, set the flag.
    $flag_service = \Drupal::service('flag');
    $ag_flag = $flag_service->getFlagById('affinity_group');
    $ag_flag_status = $flag_service->getFlagging($ag_flag, $ag_taxonomy, $to_user);
    if (!$ag_flag_status) {
      $this->output()->writeln("  Adding to-user to affinity group '$ag_title' (#$ag_tax_id)");
      // Following sometimes giving "The flag does not apply to
      // the bundle of the entity".
      if ($ag_taxonomy->bundle() !== 'affinity_groups') {
        $this->output()->writeln("*** Error, affinity group '$ag_title' has unexpected bundle = '" . $ag_taxonomy->bundle()
          . "' (expected it to have bundle 'affinity_groups')");
      } else {
        $flag_service->flag($ag_flag, $ag_taxonomy, $to_user);
      }
    } else {
      $this->output()->writeln("  To-user already a member of affinity group '$ag_title' (#$ag_tax_id)");
    }
  }
}
