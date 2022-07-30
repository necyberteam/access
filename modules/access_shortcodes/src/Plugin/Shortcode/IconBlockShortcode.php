<?php

namespace Drupal\access_shortcodes\Plugin\Shortcode;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Render\RendererInterface;
use Drupal\shortcode\Plugin\ShortcodeBase;
use Symfony\Component\DependencyInjection\ContainerInterface;


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
      'link' => ''
    ],
      $attributes
    );

    $icon = $attributes['icon'];
    $img = $attributes['img'];
    $alt = $attributes['alt'];
    $title = $attributes['title'];
    $link = $attributes['link'];

    $output = [
        '#theme' => 'shortcode_icon_block',
        '#icon' => $icon,
        '#img' => $img,
        '#alt' => $alt,
        '#title' => $title,
        '#link' => $link
      ];
  
      return $this->render($output);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $output = [];
    $output[] = '<p><strong>' . $this->t('[icon_box icon="fa-camera-retro" img="optional image url" alt="alt text for image" title="Your title here" link="Link for icon box"][/icon_box]') . '</strong> ';
    if ($long) {
      $output[] = $this->t('Builds an icon box with the Font Awesome icon or image that you specify and the title text.') . '</p>';
    }
    else {
      $output[] = $this->t('Builds an icon box.') . '</p>';
    }

    return implode(' ', $output);
  }

}
