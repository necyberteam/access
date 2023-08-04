<?php

namespace Drupal\access_misc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\access_misc\Plugin\Login;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for Match.
 */
class LoginController extends ControllerBase {

  /**
   * Page cache kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * Call login service.
   *
   * @var \Drupal\access_misc\Plugin\Login
   */
  protected $login;

  /**
   * Constructs request stuff.
   *
   * @param \Drupal\access_misc\Plugin\Login $login
   *   Login service.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   *    Kill switch.
   */
  public function __construct(Login $login, KillSwitch $kill_switch) {
    $this->login = $login;
    $this->killSwitch = $kill_switch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('access_misc.login'),
      $container->get('page_cache_kill_switch')

    );
  }

  /**
   * Route user to login.
   */
  public function login() {
    \Drupal::logger('access_misc')->notice('login function');
    $this->killSwitch->trigger();
    $this->login->login();
    return [];
  }

}
