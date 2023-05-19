<?php

namespace Drupal\access_misc\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for Community Persona.
 */
class Find extends ControllerBase {

  /**
   * Find page.
   *
   * @return string
   *   Find Page.
   */
  public function find() {
    $find_page['string'] = [
      '#type' => 'inline_template',
      '#template' => '<div class="section container pb-0">
        <div class="row">
        <div class="col text-start text-md-center">

        <h2>{{ title }}</h2>
        <div class="app-container text-start" id="root">&nbsp;</div>
        <script src="https://musical-bubblegum-000179.netlify.app/static/js/main.0c533d56.js"></script></div>
        </div>
        </div>
        <link href="https://musical-bubblegum-000179.netlify.app/static/css/main.8e99b55e.css" rel="stylesheet" />',
      '#context' => [
        'title' => $this->t('What are you looking for?'),
      ],
    ];
    return $find_page;
  }

}
