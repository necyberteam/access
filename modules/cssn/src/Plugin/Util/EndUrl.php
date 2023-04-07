<?php

namespace Drupal\cssn\Plugin\Util;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;

/**
 * Lookup the end of the url.
 *
 * @EndUrl(
 *   id = "end_url",
 *   title = @Translation("End URL"),
 *   description = @Translation("Lookup the end of the url.")
 * )
 */
class EndUrl {
  /**
   * Array of sorted matches.
   * $var array
   */
  private $urlEnd;

  /**
   * @inheritDoc
   */
  public function __construct() {
    $current_url = Url::fromRoute('<current>');
    $url_clean = Xss::filter($current_url->toString());
    $url_parts = explode('/', $url_clean);
    $this->urlEnd = end($url_parts);
  }

  /**
   * @inheritDoc
   */
  public function getUrlEnd() {
    return $this->urlEnd;
  }
}
