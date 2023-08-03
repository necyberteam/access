<?php

namespace Drupal\access_misc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Xss;
use Drupal\access_misc\Plugin\Login;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for Match.
 */
class LoginController extends ControllerBase {

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
   */
  public function __construct(Login $login) {
    $this->login = $login;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('access_misc.login')
    );
  }

  /**
   * Route user to login.
   */
  public function login() {
    $this->login->login();
    return [];
  }

}
