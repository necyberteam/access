<?php

namespace Drupal\access_shortcodes\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * Provides a shortcode for icon blocks.
 *
 * @Shortcode(
 *   id = "icon_block",
 *   title = @Translation("Icon Block"),
 *   description = @Translation("Builds a div with an icon and a title.")
 * )
 */
class IconBlockShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process(array $attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {
    $attributes = $this->getAttributes([
      'icon' => '',
      'img' => '',
      'alt' => '',
      'title' => '',
      'text' => '',
      'link' => '',
      'boxed' => '',
      'btnlink' => '',
      'btntext' => '',
    ],
      $attributes
    );

    $icon = $attributes['icon'];
    $img = $attributes['img'];
    $alt = $attributes['alt'];
    $title = $attributes['title'];
    $text = $attributes['text'];
    $link = $attributes['link'];
    $boxed = $attributes['boxed'];
    $btnlink = $attributes['btnlink'];
    $btntext = $attributes['btntext'];

    $output = [
      '#theme' => 'shortcode_icon_block',
      '#icon' => $icon,
      '#img' => $img,
      '#alt' => $alt,
      '#title' => $title,
      '#text' => $text,
      '#link' => $link,
      '#boxed' => $boxed,
      '#btnlink' => $btnlink,
      '#btntext' => $btntext,
    ];

    return $this->render($output);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $output = [];
    $output[] = '<p><strong>' . $this->t('[icon_box icon="fa-camera-retro" img="optional image url" alt="alt text for image" title="Your title here" text="Your text here" link="Link for icon box" boxed="boxed" btnLink="https://example.com" btnText="Optional button Link"][/icon_box] ') . '</strong> ';
    if ($long) {
      $output[] = $this->t('Builds an icon box with the Font Awesome icon or image that you specify and the title text.') . '</p>';
    }
    else {
      $output[] = $this->t('Builds an icon box.') . '</p>';
    }

    return implode(' ', $output);
  }

}
