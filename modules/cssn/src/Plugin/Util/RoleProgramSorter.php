<?php

namespace Drupal\cssn\Plugin\Util;

use Drupal\user\Entity\User;

/**
 * Sort Users.
 *
 * @RoleProgramSorter(
 *   id = "role_program_sorter",
 *   title = @Translation("CSSN Role Program Sorter"),
 *   description = @Translation("Sorts users by CSSN role and program.")
 * )
 */
class RoleProgramSorter {
  /**
   * Store user object.
   * $var object
   */
  private $storedUser;

  /**
   * Function to return matching nodes.
   */
  public function __construct($user) {
    $this->storedUser = $user;
  }

  /**
   * Add role to user.
   */
  public function addRole($role) {
    $account = User::load($this->storedUser->id());
    if (!$account->hasRole($role)) {
      \Drupal::messenger()->addMessage(t('The following role added: @role', ['@role' => $role]));
      $account->addRole($role);
      $account->save();
    }
  }

  /**
   * Remove role from user.
   */
  public function removeRole($role) {
    $account = User::load($this->storedUser->id());
    if ($account->hasRole($role)) {
      \Drupal::messenger()->addMessage(t('The following role removed: @role', ['@role' => $role]));
      $account->removeRole($role);
      $account->save();
    }
  }

  /**
   * Add item to field_region.
   */
  public function addFieldRegion($region) {
    $account = User::load($this->storedUser->id());
    $account->field_region->appendItem($region);
    $account->save();
    \Drupal::messenger()->addMessage(t('You have been added to the CSSN Program.'));
  }

  /**
   * Remove item to field_region.
   */
  public function removeFieldRegion($region) {
    $account = User::load($this->storedUser->id());
    $values = $account->field_region->getValue();
    foreach ($values as $key => $value) {
      if ($value['target_id'] == $region) {
        unset($values[$key]);
        \Drupal::messenger()->addMessage(t('You have been removed from the CSSN Program.'));
      }
    }
    $account->field_region->setValue($values);
    $account->save();
  }

}
