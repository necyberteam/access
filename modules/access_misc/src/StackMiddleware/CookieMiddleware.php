<?php

namespace Drupal\access_misc\StackMiddleware;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Provides a HTTP middleware.
 */
class CookieMiddleware implements HttpKernelInterface {

  /**
   * The wrapped HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The RedirectAddAnalytics logging channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The state key/value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a drupal_seamless_cilogin object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $kernel
   *   The decorated kernel.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $config_factory
   *   Logging interface.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key/value store.
   */
  public function __construct(
    HttpKernelInterface $http_kernel,
    LoggerChannelFactoryInterface $channelFactory,
    StateInterface $state
  ) {
    $this->httpKernel = $http_kernel;
    $this->logger = $channelFactory->get('drupal_seamless_cilogon');
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {

    $path = $request->getRequestUri();
    $arg = explode('/', $path);

    if ($arg[1] == 'login') {
      $request->headers->set('Cache-Control', 'public, max-age=0');
    }

    return $this->httpKernel->handle($request, $type, $catch);
  }

}
