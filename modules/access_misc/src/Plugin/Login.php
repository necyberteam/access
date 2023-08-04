<?php

namespace Drupal\access_misc\Plugin;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Component\Utility\Xss;

/**
 * Environment icon to be used on header title.
 *
 * @Login (
 *   id = "login",
 *   title = @Translation("Login Service"),
 *   description = @Translation("Forward user to login to ciLogon")
 * )
 */
class Login {

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  private $container;

  /**
   * Get request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   *
   */
  public function __construct(
    RequestStack $requestStack,
    ContainerInterface $container
  ) {
    $this->requestStack = $requestStack;
    $this->container = $container;
  }

  /**
   * Login user.
   */
  public function login() {
    \Drupal::logger('access misc')->notice('in function.');

    $request = $this->requestStack->getCurrentRequest();
    $container = $this->container;
    $client_name = 'cilogon';
    $config_name = 'cilogon_auth.settings.' . $client_name;
    $configuration = $container->get('config.factory')->get($config_name)->get('settings');
    $pluginManager = $container->get('plugin.manager.cilogon_auth_client.processor');
    $claims = $container->get('cilogon_auth.claims');
    $client = $pluginManager->createInstance($client_name, $configuration);
    $scopes = $claims->getScopes();
    $destination = $request->getRequestUri();
    $query = NULL;
    if (NULL !== $request->query->get('redirect')) {
      $query = Xss::filter($request->query->get('redirect'));
    }
    $_SESSION['cilogon_auth_op'] = 'login';
    $_SESSION['cilogon_auth_destination'] = [$destination, ['query' => $query]];
    \Drupal::logger('cilogon auth')->notice('destination: ' . $destination);
    \Drupal::logger('cilogon auth')->notice('query: ' . $query);
    $response = $client->authorize($scopes);
    $response->send();

  }

}
