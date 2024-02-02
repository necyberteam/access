<?

namespace Drupal\access_misc\Plugin\Util;

/**
  * Find the access organization entity reference for the given access org id.
  *
  * This is the field that is on the user profile.
  */
class FindAccessOrg {
  public function get($accessOrgId) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'access_organization')
      ->condition('field_organization_id', $accessOrgId)
      ->accessCheck(FALSE)
      ->execute();

    return $query;
  }
}
