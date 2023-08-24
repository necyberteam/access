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
      $client = \Drupal::httpClient();
      try {
        $request = $client->get($category);
        $result = $request->getBody()->getContents();
      }
      catch (RequestException $e) {
        \Drupal::logger('access_affinitygroup')->error($e);
      }
      $result = json_decode($result);
      $topic_url = explode('/', $result->category->topic_url);
      $topic_url = end($topic_url);

      // Lookup Topics.
      $slug = $result->category->slug;
      $category_topics = "https://ask.cyberinfrastructure.org/c/$slug/$cid.json";
      try {
        $request = $client->get($category_topics);
        $result = $request->getBody()->getContents();
      }
      catch (RequestException $e) {
        \Drupal::logger('access_affinitygroup')->error($e);
      }
      $result = json_decode($result);
      $topics_list = $result->topic_list->topics;

      // Api call for grabbing the topic.
      foreach ($topics_list as $topic_list) {
        $topic_id = $topic_list->id;
        $single_topic = "https://ask.cyberinfrastructure.org/t/$topic_id.json";
        try {
          $request = $client->get($single_topic);
          $result = $request->getBody()->getContents();
        }
        catch (RequestException $e) {
          \Drupal::logger('access_affinitygroup')->error($e);
        }
        $result = json_decode($result);
        $topics = $result->suggested_topics;
        $list = '<h3 class="border-bottom pb-2">Ask CI</h3><ul>';
        $last_update = $result->last_posted_at ? $result->last_posted_at : $result->created_at;
        $last_update = strtotime($last_update);
        $list_topics[$last_update] = [
          'title' => $result->title,
          'slug' => $result->slug,
          'id' => $result->id,
        ];
      }
      krsort($list_topics);
      foreach ($list_topics as $list_key => $topic) {
        $last_update = $list_key;
        $last_update = date('m-d-Y', $last_update);

        $title = $topic['title'];
        $slug = $topic['slug'];
        $id = $topic['id'];

        $list .= "<li><a href='https://ask.cyberinfrastructure.org/t/$slug/$id'>$title</a> (Last Update: $last_update)</li>";
      }
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
