<?php

namespace Drupal\access_match_engagement\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for Match.
 */
class MatchController extends ControllerBase {

  /**
   * Build content to display on page.
   */
  public function interestedContent() {
    $nid = \Drupal::routeMatch()->getRawParameter('node');
    // Load entity node using node id.
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    if ($node->getType() == 'match_engagement') {
      $current_user = \Drupal::currentUser()->id();
      $interested_users = $node->get('field_match_interested_users')->getValue();
      if (array_search($current_user, array_column($interested_users, 'target_id')) !== FALSE) {
        \Drupal::messenger()->addStatus(t("You're already on the interested list"));
      } else {
        $interested_users[] = ['target_id' => $current_user];
        // Get current user.
        $current_user = \Drupal::currentUser();
        // Update node field.
        $node->set('field_match_interested_users', $interested_users);
        $node->save();
        \Drupal::messenger()->addStatus(t("You have been added to the interested list"));
      }
    }
    \Drupal::service('page_cache_kill_switch')->trigger();
    // Redirect to node.
    $response = new RedirectResponse('/node/' . $nid);
    $response->send();
  }

}
