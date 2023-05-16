<?php


namespace Drupal\access_affinitygroup\Plugin\views\access;

use Drupal\views\Plugin\views\access\AccessPluginBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Class ViewsCustomAccess
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *     id = "ViewsCustomAccess",
 *     title = @Translation("Custom AG Access"),
 *     help = @Translation("Add custom logic to access() method"),
 * )
 */
class ViewsCustomAccess extends AccessPluginBase {
  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->t('Custom AG Access');
  }


  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    $access = FALSE;
    // Get current user roles.
    $roles = $account->getRoles();
    // If roles contain administrator role, then grant access.
    if (in_array('administrator', $roles)) {
      $access = TRUE;
    }
    $nid = \Drupal::request()->query->get('nid');
    if ($nid) {
      $node = \Drupal\node\Entity\Node::load($nid);
      $coordinators = $node->get('field_coordinator')->getValue();
      foreach ($coordinators as $coordinator) {
        if ($coordinator['target_id'] == $account->id()) {
          $access = TRUE;
        }
      }
    }
    return $access;
  }


  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_access', 'TRUE');
  }
}
