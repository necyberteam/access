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
    $isMentor =  $this->configuration['label'] == 'Mentor' ? true : false;
    $personFieldName = $isMentor ? 'field_mentor' : 'field_mentee';

    $node_param = \Drupal::routeMatch()->getParameter('node');
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    // todo - how to return so title does not display if block is empty
    if (empty($node_param) || empty($node_param->id())) {
      return [
        '#markup' => $this->t('No node found.'),   // TODO : errmsg  just for now to see what's happening.
      ];
    }

    $node = $node_storage->load($node_param->id());

    $userName = '';
    $user_image = '';
    $institution = '';
    $personA = $node->get($personFieldName)->getValue();

    if (!empty($personA) && !empty([$personA][0])) {
      $person = $personA[0]['target_id'];
      // load user from user id mentee
      $user = User::load($person);

      // get user profile picure image
      $user_image = $user->get('user_picture');
      if ($user_image->entity !== NULL) {
        $user_image = $user_image->entity->getFileUri();
        $user_image = \Drupal::service('file_url_generator')->generateAbsoluteString($user_image);
        $user_image = '<img src="' . $user_image . '" class="img-fluid mb-3 border border-black" />';
      } else {
        $user_image = '<svg version="1.1" class="mb-3" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
           viewBox="0 0 448 448" style="enable-background:new 0 0 448 448;" xml:space="preserve">
            <style type="text/css">
              .st0{fill:#ECF9F8;}
              .st1{fill:#B7CDD1;}
            </style>
            <rect class="st0" width="448" height="448"/>
            <path class="st1" d="M291,158v14.5c0,40-32.4,72.5-72.5,72.5s-72.5-32.4-72.5-72.5V158c0-5,0.5-9.8,1.4-14.5h27.5
              c27,0,50.6-14.8,63-36.7c10.6,13.5,27.1,22.2,45.6,22.2h1.2C288.8,137.9,291,147.7,291,158z M102.6,158v14.5
              c0,64,51.9,115.9,115.9,115.9s115.9-51.9,115.9-115.9V158c0-64-51.9-115.9-115.9-115.9S102.6,94,102.6,158z"/>
            <path class="st1" d="M151.2,306.3c-71.9,7.8-128.2,68.1-130,141.7h405.6c-1.8-73.6-58.1-133.8-130.1-141.6
              c-4.8-0.5-9.5,1.7-12.4,5.6l-48.8,65c-5.8,7.7-17.4,7.7-23.2,0l-48.8-65l0.1-0.1C160.7,308,156,305.9,151.2,306.3z"/>
            </svg>
            ';
      }
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

    $display = '<div class="container"><div class="row">' .
      '<div class="col-sm">' . $user_image . '</div>' .
      '<div class="col-sm"><div>' . $userName . '</div><div>' . $institution . '</div></div></div></div>';

    return [
      '#markup' => $this->t($display),
    ];
  }

  //
  function personDisplay($fieldName) {
  }
}
