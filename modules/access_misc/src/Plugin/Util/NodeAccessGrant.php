<?php

namespace Drupal\access_misc\Plugin\Util;

use Drupal\Core\Database\Connection;

/**
 * Set grant for node using node access.
 *
 * @NodeAccessGrant(
 *   id = "node_access_grant",
 *   title = @Translation("Node access grant"),
 *   description = @Translation("Grant access to a node")
 * )
 */
class NodeAccessGrant {

  /**
   * Run Database query.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Construct object.
   */
  public function __construct(
    Connection $connection,
  ) {
    $this->connection = $connection;
  }

  /**
   * Function to grant access to node.
   */
  public function grant($authorized_users, $nid) {
    $this->connection->delete('node_access')
      ->condition('nid', $nid)
      ->execute();
    if ($authorized_users) {
      $authorized_users[] = 6;
      $authorized_users[] = 1;
      foreach ($authorized_users as $uid) {
        $realm = $uid == 1 || $uid == 6 ? 'nodeaccess_rid' : 'nodeaccess_uid';
        $update = $uid == 1 || $uid == 6 ? 0 : 1;
        $this->connection->insert('node_access')
          ->fields([
            'nid' => $nid,
            'langcode' => 'en',
            'fallback' => 1,
            'gid' => $uid,
            'realm' => $realm,
            'grant_view' => 1,
            'grant_update' => $update,
            'grant_delete' => 0,
          ])
          ->execute();
      }
    }
  }

}
