<?php

namespace Drupal\access_affinitygroup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Block of attached affinity group.
 *
 * @Block(
 *   id = "Ci Community",
 *   admin_label = "Ci Community pulled via api",
 * )
 */
class CiCommunity extends BlockBase implements
  ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Invoke renderer.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routMatchInterface;

  /**
   * The http_client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container pulled in.
   * @param array $configuration
   *   Configuration added.
   * @param string $plugin_id
   *   Plugin_id added.
   * @param mixed $plugin_definition
   *   Plugin_definition added.
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('http_client'),
      $container->get('logger.factory'),
    );
  }

  /**
   * Construct object.
   */
  public function __construct(
                              array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entity_type_manager,
                              RouteMatchInterface $route_match_interface,
                              Client $http_client,
                              LoggerChannelFactoryInterface $logger_interface
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatchInterface = $route_match_interface;
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_interface->get('access_affinitygroup');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $html = [];
    $node = $this->routeMatchInterface->getParameter('node');
    // If on the layout page show node 327.
    $node = $node ? $node : $this->entityTypeManager->getStorage('node')->load(327);
    $qa_link = $node->get('field_ask_ci_locale')->getValue();
    $qa_link_set = $qa_link ?? $qa_link;
    if ($qa_link_set) {
      $qa_link_parts = explode('/', $qa_link[0]['uri']);
      $qa_link_id = end($qa_link_parts);
      $cid = $qa_link_parts[2] == 'ask.cyberinfrastructure.org' && is_numeric($qa_link_id) ? $qa_link_id : 0;
    }
    else {
      $cid = 0;
    }
    if ($cid) {
      $header = [
        [
          'data' => 'Topics',
          'class' => [
            'border-x-0',
            'border-b',
            'border-t-0',
            'border-black',
            'border-solid',
          ],
        ],
        [
          'data' => 'Last Update',
          'class' => [
            'border-x-0',
            'border-b',
            'border-t-0',
            'border-black',
            'border-solid',
          ],
        ]
      ];
      $rows = [];
      // Api call for grabbing the category.
      $category = "https://ask.cyberinfrastructure.org/c/$cid/show.json";
      $client = $this->httpClient;
      try {
        $request = $client->get($category);
        $result = $request->getBody()->getContents();
      }
      catch (RequestException $e) {
        $this->loggerFactory->error($e);
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
        $this->loggerFactory->error($e);
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
          $this->loggerFactory->error($e);
        }
        $result = json_decode($result);
        $last_update = $result->last_posted_at ? $result->last_posted_at : $result->created_at;
        $last_update = strtotime($last_update);
        $list_topics[$last_update] = [
          'title' => $result->title,
          'slug' => $result->slug,
          'id' => $result->id,
        ];
      }
      krsort($list_topics);
      $iteration = 0;
      foreach ($list_topics as $list_key => $topic) {
        $iteration++;
        if ($iteration > 5) {
          break;
        }
        $last_update = $list_key;
        $last_update = date('m/d/y', $last_update);

        $title = $topic['title'];
        $slug = $topic['slug'];
        $id = $topic['id'];
        $url = Url::fromUri("https://ask.cyberinfrastructure.org/t/$slug/$id");
        $external_link = Link::fromTextAndUrl($title, $url)->toString();

        $rows[] = [
          'name' => [
            'data' => [
              '#markup' => "$external_link",
            ],
            'class' => [
              'border-x-0',
              'border-b',
              'border-t-0',
              'border-black',
              'border-solid',
            ],
          ],
          'last_update' => [
            'data' => [
              '#markup' => $last_update,
            ],
            'class' => [
              'border-x-0',
              'border-b',
              'border-t-0',
              'border-black',
              'border-solid',
            ],
          ],
        ];
      }
      $ask_title = $this->t('Ask.CI Recent Topics');
      $ask_title = "<h3 class='text-white-er border-bottom pb-2 bg-dark-teal py-2 px-4'>$ask_title</h3>";
      $options = [
        'attributes' => [
          'class' => [
            'btn btn-primary m-2',
          ],
        ],
      ];
      $ci_url = Url::fromUri($qa_link[0]['uri'], $options);
      $ci_external_link = Link::fromTextAndUrl($this->t('View on Ask.CI'), $ci_url);
      $html['ask-ci'] = [
        '#theme' => 'table',
        '#prefix' => $ask_title,
        '#suffix' => $ci_external_link->toString(),
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => ['id' => 'ask-ci', 'class' => ['border-0 border-spacing-0']],
        // Expire in one day in seconds.
        '#cache' => ["max-age" => 86400],
      ];
    }
    return $html;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if ($node = $this->routeMatchInterface->getParameter('node')) {
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
    }
    else {
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
