<?php

namespace Drupal\access_affinitygroup\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for Match.
 */
class SimpleListController extends ControllerBase {

  /**
   * Check user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Perform redirect.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Page cache kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * Constructs request stuff.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Used to get current active user.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $kill_switch
   *   Kill switch.
   */
  public function __construct(AccountProxyInterface $current_user,
                              KillSwitch $kill_switch,
                              RedirectDestinationInterface $redirect_destination
  ) {
    $this->currentUser = $current_user;
    $this->redirectDestination = $redirect_destination;
    $this->killSwitch = $kill_switch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('current_user'),
      $container->get('page_cache_kill_switch'),
      $container->get('redirect.destination')
    );
  }

  /**
   * Route to actions on Simplelist.
   */
  public function simplelist() {

    // Get last part of url.
    $path = \Drupal::service('path.current')->getPath();
    $path = explode('/', $path);
    $path = end($path);
    $param = \Drupal::request()->query->all();
    $node_id = explode('/', $param['redirect']);
    $node_id = end($node_id);
    $uid = $this->currentUser->id();


    // Invalidate cache for block.
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['node:' . $node_id . ':user:' . $uid]);

    // Setup SimpleListsApi.
    $simpleListsApi = new \Drupal\access_affinitygroup\Plugin\SimpleListsApi();
    $msg = "";
    $listName = $param['slug'] ? Xss::filter($param['slug']) : '';
    // Get current user email.
    $userEmail = $this->currentUser->getEmail();
    $user = \Drupal\user\Entity\User::load($this->currentUser->id());
    $firstName = $user->get('field_user_first_name')->value;
    $lastName = $user->get('field_user_last_name')->value;
    if ($param['current'] == 'none') {
      $simplelistsId = $simpleListsApi->getUserIdFromEmail($userEmail, $msg);
      if ($simplelistsId) {
        $addCurrentUser = $simpleListsApi->updateUserToList($simplelistsId, $listName, $msg);
      }
      else {
        $addUser = $simpleListsApi->addUser($uid, $userEmail, $firstName, $lastName, $listName, $msg);
      }
      if ($path == 'daily') {
        $digest = 1;
        $set_digest = $simpleListsApi->setUserDigest($listName, $userEmail, $digest, $msg);
      }
    }
    elseif ($path == 'none') {
      $removeUser = $simpleListsApi->removeUserFromList($userEmail, $listName, $msg);
    }
    else {
      if ($path == 'daily') {
        $digest = 1;
      }
      if ($path == 'full') {
        $digest = 0;
      }
      $set_digest = $simpleListsApi->setUserDigest($listName, $userEmail, $digest, $msg);
    }
    $this->killSwitch->trigger();
    // Get redirect destination from url.
    $destination = $param['redirect'] ? Xss::filter($param['redirect']) : '/';
    // Redirect to destination.
    $response = new RedirectResponse($destination);
    $response->send();
    return [
      '#type' => 'markup',
      '#markup' => "👋 " . $this->t("You shouldn't see this."),
    ];
  }

}
