<?php

namespace Drupal\access_affinitygroup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Block of attached affinity group.
 *
 * @Block(
 *   id = "Ci Community",
 *   admin_label = "Ci Community pulled via api",
 * )
 */
class CiCommunity extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $list = '';
    $node = \Drupal::routeMatch()->getParameter('node');
    // If on the layout page show node 327.
    $node = $node ? $node : \Drupal::entityTypeManager()->getStorage('node')->load(327);
    $qa_link = $node->get('field_ask_ci_locale')->getValue();
    $qa_link = $qa_link ?? $qa_link;
    if ($qa_link) {
      $qa_link = explode('/', $qa_link[0]['uri']);
      $qa_link_id = end($qa_link);
      $cid = $qa_link[2] == 'ask.cyberinfrastructure.org' && is_numeric($qa_link_id) ? $qa_link_id : 0;
    }
    else {
      $cid = 0;
    }
    if ($cid) {
      // Api call for grabbing the category.
      $category = "https://ask.cyberinfrastructure.org/c/$cid/show.json";
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept:application/json"]);
      curl_setopt($ch, CURLOPT_URL, $category);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
      $result = json_decode(curl_exec($ch));
      $topic_url = explode('/', $result->category->topic_url);
      $topic_url = end($topic_url);
      curl_close($ch);
      // Api call for grabbing the topic.
      $category = "https://ask.cyberinfrastructure.org/t/$topic_url.json";
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept:application/json"]);
      curl_setopt($ch, CURLOPT_URL, $category);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
      $result = json_decode(curl_exec($ch));
      $topics = $result->suggested_topics;
      $list = '<h3 class="border-bottom pb-2">Ask CI</h3><ul>';
      foreach ($topics as $topic) {
        $list_topics[$topic->last_posted_at] = [
          'title' => $topic->title,
          'slug' => $topic->slug,
          'id' => $topic->id,
        ];
      }
      krsort($list_topics);
      foreach ($list_topics as $list_key => $topic) {
        $last_update = $list_key;
        $last_update = strtotime($last_update);
        $last_update = date('m-d-Y', $last_update);

        $title = $topic['title'];
        $slug = $topic['slug'];
        $id = $topic['id'];

        $list .= "<li><a href='https://ask.cyberinfrastructure.org/t/$slug/$id'>$title</a> (Last Update: $last_update)</li>";
      }
      curl_close($ch);
      $list .= '</ul>';
    }
    return [
      '#markup' => $list,
      '#cache' => [
        // Expire in one day in seconds.
        'max-age' => 86400,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
    }
    else {
      return parent::getCacheTags();
    }
  }

  /**
   *
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
