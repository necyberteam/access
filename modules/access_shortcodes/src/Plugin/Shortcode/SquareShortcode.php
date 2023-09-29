<?php

namespace Drupal\access_shortcodes\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * Provides a shortcode for accordion blocks.
 *
 * @Shortcode(
 *   id = "square",
 *   title = @Translation("Square"),
 *   description = @Translation("Builds an square content box.")
 * )
 */
class SquareShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process(array $attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {
    $attributes = $this->getAttributes([
      'icon' => '',
      'title' => '',
      'text' => '',
      'link' => '',
      'color' => '',
      'flipText' => '',
      'flipBtnText' => '',
    ],
      $attributes
    );

    $icon = $attributes['icon'];
    $title = $attributes['title'];
    $text = $attributes['text'];
    $link = $attributes['link'];
    $color = $attributes['color'];
    $flipText = $attributes['flipText'];
    $flipBtnText = $attributes['flipBtnText'];

    $output = [
      '#theme' => 'shortcode_square',
      '#icon' => $icon,
      '#title' => $title,
      '#text' => $text,
      '#link' => $link,
      '#color' => ($color == '') ? 'dark-teal' : $color,
      '#flipText' => $flipText,
      '#flipBtnText' => $flipBtnText,
    ];

    return $this->render($output);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $output = [];
    $output[] = '<p><strong>' . $this->t('[square icon="/path/to/img" title="Title" text="Your text here" link="https://example.com" color="optional TailwindCSS color name" flipText="Optional flip text" flipBtnText="Optional text for flip button"][/square]') . '</strong>';
    if ($long) {
      $output[] = $this->t('Builds a square content box.') . '</p>';
    }
    else {
      $output[] = $this->t('Builds a square content box.') . '</p>';
    }

    return implode(' ', $output);
  }

}
