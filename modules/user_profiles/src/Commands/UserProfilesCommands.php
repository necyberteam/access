<?php

namespace Drupal\user_profiles\Commands;

use Drupal\access_affinitygroup\Plugin\ConstantContactApi;
use Drupal\access_affinitygroup\Plugin\AllocationsUsersImport;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\recurring_events\Entity\EventSeries;
use Drupal\recurring_events\Entity\EventInstance;
use Drupal\recurring_events\EventInterface;
use Drupal\recurring_events\Plugin\ComputedField\EventInstances;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile for Affinity Groups.
 *
 * @package Drupal\user_profiles\Commands
 */
class UserProfilesCommands extends DrushCommands
{
    /**
     * Add existing Affinity Group members to Constant Contact lists.
     *
     * Save all Affinity Groups to trigger the creation of the
     * associated Constant Contact list. Then add all existing members of
     * the group to that Constant Contact list.
     *
     * @command user_profiles:mergeUser
     * @param   $from_user_id user id to merge from
     * @param   $to_user_id user id to merge to
     * @aliases mergeUser
     * @usage   user_profiles:mergeUser
     */
    public function mergeUser(string $from_user_id, string $to_user_id)
    {
        $this->output()->writeln("------------- Merge user $from_user_id into $to_user_id ---------------------------------");

        $user_from = User::load($from_user_id);
        $user_to = User::load($to_user_id);

        // $this->output()->writeln('methods of output: ' . print_r(get_class_methods($this->output), true));


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

        $this->mergeAfffinityGroups($user_from, $user_to);
    }

    private function mergeAfffinityGroups($user_from, $user_to) {

        $this->output()->writeln("  Merging affinity groups from " . $user_from->id() 
            . " to " . $user_to->id());

        $userBlockedArray = $user_to->get('field_blocked_ag_tax')->getValue();
        $userBlockedAgTids = [];
        foreach ($userBlockedArray as $userBlock) {
            $userBlockedAgTids[] = $userBlock['target_id'];
        }   
        
        $this->output()->writeln("  user-to blocked ag tids: " . implode(' ', $userBlockedAgTids));

        // $user_entity = \Drupal::entityTypeManager()->getStorage('user')->load($from_user_id);
        $query = \Drupal::database()->select('flagging', 'fl');
        $query->condition('fl.uid', $user_from->id());
        $query->condition('fl.flag_id', 'affinity_group');
        $query->fields('fl', ['entity_id']);
        $ag_tids = $query->execute()->fetchCol();
        if ($ag_tids == NULL) {
            $this->output()->writeln("  from-user is not a member of any affinity groups");
        } else {
            
            $this->output()->writeln("  from-user ag tids: " . implode(' ', $ag_tids));
            foreach ($ag_tids as $ag_tid) {


                $query = \Drupal::database()->select('taxonomy_index', 'ti');
                $query->condition('ti.tid', $ag_tid);
                $query->fields('ti', ['nid']);
                $affinity_group_nid = $query->execute()->fetchCol();
                if (isset($affinity_group_nid[0])) {
                    $affinity_group_loaded = \Drupal::entityTypeManager()->getStorage('node')->load($affinity_group_nid[0]);
                    if (!$affinity_group_loaded) {
                        $this->output()->writeln("  *** Warning, from-user flagged as member of affinity group #" 
                            . $affinity_group_nid[0] 
                            . " but no such affinity group found - skipping this affinity group");
                    } else {
                        $this->output()->writeln("  from-user member of AG '" 
                            . $affinity_group_loaded->getTitle()  . "' (tid #" 
                            . $ag_tid . ")");
                        $this->addUserToAG($user_to, $affinity_group_loaded, $userBlockedAgTids);
                    }
                    //   $url = Url::fromRoute('entity.node.canonical', array('node' => $affinity_group_loaded->id()));
                    // $this->output()->writeln("  From-user is a member of affinity group '" 
                    //     . $affinity_group_loaded->getTitle() . "'");
                    //   $project_link = Link::fromTextAndUrl($affinity_group_loaded->getTitle(), $url);
                    //   $link = $project_link->toString()->__toString();
                    //   $user_affinity_groups .= "<li>$link</li>";
                }
            }
        }
    }

    /**
     * 
     */
    private function addUserToAG(UserInterface $to_user, EntityInterface $affinity_group, $blockList) {
        
        // instead of loading by title, could we load by id?  not sure how
        // $ag_id = $affinity_group->getEntityTypeId();
        // $agTax = \Drupal::entityTypeManager()
        //     ->getStorage('taxonomy_term')
        //     ->loadByProperties(['id' => $ag_id]);

        $ag_title = $affinity_group->getTitle();
        $agTax = \Drupal::entityTypeManager()
            ->getStorage('taxonomy_term')
            ->loadByProperties(['name' => $ag_title]);


        // $this->output()->writeln("  agTax = " . print_r($agTax, true));     
        $agTax = reset($agTax);

        if (!$agTax) {
            $this->output()->writeln("  *** Warning, no taxonomy id found for AG title '$ag_title'");     
            return;
        }
        // check if ag is on block list.
        if (in_array($agTax->id(), $blockList)) {
           
            $this->output()->writeln("  Affinity group '$ag_title' on to-user's block list");
            return;
        }
        
        $flagService = \Drupal::service('flag');
        $flag = $flagService->getFlagById('affinity_group');

        // Check if already flagged. If not, set the join flag.
        $flagStatus = $flagService->getFlagging($flag, $agTax, $to_user);
        if (!$flagStatus) {
            // $flagService->flag($flag, $agTax, $to_user);
            $this->output()->writeln("  WILL Add to-user to affinity group '$ag_title'");
        } else {
            $this->output()->writeln("  to-user already a member of affinity group '$ag_title'");
        }

    }

}
