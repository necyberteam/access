<?php

namespace Drupal\access_shortcodes\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * Provides a shortcode for accordion blocks.
 *
 * @Shortcode(
 *   id = "accordion",
 *   title = @Translation("Accordion"),
 *   description = @Translation("Builds an accordion with a summary and extended text.")
 * )
 */
class AccordionShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process(array $attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {
    $attributes = $this->getAttributes([
      'summary' => '',
      'text' => '',
      'color' => '',
    ],
      $attributes
    );

    $summary = $attributes['summary'];
    $text = $attributes['text'];
    $color = $attributes['color'];

    $output = [
      '#theme' => 'shortcode_accordion',
      '#summary' => $summary,
      '#text' => $text,
      '#color' => ($color == '') ? 'bg-light-teal' : $color,
    ];

    return $this->render($output);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $output = [];
    $output[] = '<p><strong>' . $this->t('[accordion summary="Question" text="Your text here" color="bg-light-teal"][/accordion]') . '</strong>';
    if ($long) {
      $output[] = $this->t('Builds an accordion with summary, text and color.') . '</p>';
    }
    else {
      $output[] = $this->t('Builds an accordion with summary, text and color.') . '</p>';
    }

    return implode(' ', $output);
  }

}
