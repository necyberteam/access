<?php

namespace Drupal\access_misc\Plugin;

/**
 * Tag Cloud on nodes.
 *
 * @Login (
 *   id = "skill_level",
 *   title = @Translation("Skill Level"),
 *   description = @Translation("Replace skill level with image")
 * )
 */
class SkillLevel {

  /**
   * Return skill level image.
   */
  public function getSkillsImage($skill_list) {
    if (['Beginner'] == $skill_list) {
      $skills = '<img class="object-contain m-0 h-auto" src="/themes/contrib/asp-theme/images/icons/SL-beginner.png" alt="Beginner">';
    }
    elseif (['Beginner', 'Intermediate'] == $skill_list) {
      $skills = '<img class="object-contain m-0 h-auto" src="/themes/contrib/asp-theme/images/icons/SL-beginner-medium.png" alt="Beginner, Intermediate">';
    }
    elseif (['Beginner', 'Intermediate', 'Advanced'] == $skill_list) {
      $skills = '<img class="object-contain m-0 h-auto" src="/themes/contrib/asp-theme/images/icons/SL-all.png" alt="Beginner, Intermediate, Advanced">';
    }
    elseif (['Intermediate'] == $skill_list) {
      $skills = '<img class="object-contain m-0 h-auto" src="/themes/contrib/asp-theme/images/icons/SL-medium-advanced.png" alt="Intermediate">';
    }
    elseif (['Intermediate', 'Advanced'] == $skill_list) {
      $skills = '<img class="object-contain m-0 h-auto" src="/themes/contrib/asp-theme/images/icons/SL-medium-advanced.png" alt="Intermediate, Advanced">';
    }
    elseif (['Advanced'] == $skill_list) {
      $skills = '<img class="object-contain m-0 h-auto" src="/themes/contrib/asp-theme/images/icons/SL-advanced.png" alt="Advanced">';
    }
    return $skills;
  }

}
