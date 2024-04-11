<?php

namespace Drupal\ccmnet\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\user\Entity\User;

/**
 * Provides a 'MentorshipPerson' Block.
 *
 * @Block(
 *   id = "mentorship_person_block",
 *   admin_label = @Translation("Mentorship Person")
 * )
 */
class MentorshipPersonBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Note: title from layout builder block placement used here
    $isMentor =  $this->configuration['label'] == 'Mentor' ? TRUE : FALSE;
    $personFieldName = $isMentor ? 'field_mentor' : 'field_mentee';

    $node_param = \Drupal::routeMatch()->getParameter('node');
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    // need this for using layout builder
    if (empty($node_param) || empty($node_param->id())) {
      return [
        '#markup' => $this->t('No node.'),
      ];
    }
    $node = $node_storage->load($node_param->id());

    $userName = '';
    $user_image = '';
    $institution = '';
    $personA = $node->get($personFieldName)->getValue();

    if (empty($personA) || empty([$personA][0])) {
      return [];
    } else {
      $person = $personA[0]['target_id'];
      // load user from user id mentee
      $user = User::load($person);

      // get user profile picure image
      $user_image = $user->get('user_picture');

      if ($user_image->entity !== NULL) {
        $user_image = $user_image->entity->getFileUri();
        $user_image = \Drupal::service('file_url_generator')->generateAbsoluteString($user_image);
      } else {
        $user_image = '/themes/nect-theme/img/user-picture.svg';
      }
      $user_image = '<img src="' . $user_image . '" />';

      // Show access organization if set; otherwise, use institution field.
      $orgArray = $user->get('field_access_organization')->getValue();
      if (!empty($orgArray) && !empty($orgArray[0])) {
        $nodeId = $orgArray[0]['target_id'];
        if (!empty($nodeId)) {
          $orgNode = \Drupal::entityTypeManager()->getStorage('node')->load($nodeId);
        }
      }
      $institution = isset($orgNode) ? $orgNode->getTitle() : $user->get('field_institution')->value;
      $userName = $user->getDisplayName();
    }

    $display = '<div class="d-flex justify-content-start mentorship-person">' .
      '<div class="mentorship-person-picture p-0" >' . $user_image . '</div>' .
      '<div class="col d-flex  flex-column justify-content-start">' .
      '<div><strong>' . $userName . '</strong></div><div>' . $institution . '</div></div></div>';

    return [
      '#markup' => $this->t($display),
    ];
  }

  //
  function personDisplay($fieldName) {
  }
}
