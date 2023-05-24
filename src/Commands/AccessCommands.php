<?php

namespace Drupal\access\Commands;

use Drush\Commands\DrushCommands;

/**
 * Various utility commands for Access.
 */
class AccessCommands extends DrushCommands {

  /**
   * Ingest ACCESS organizations.
   *
   * @command access:ingest-orgs
   * @aliases ingest-organizations
   * @options limit The number of records to process
   */
  public function ingestOrganizations($options = ['limit' => 100]) {
    $orgs = [];

    $path = \Drupal::service('file_system')->realpath("private://") . '/.keys/secrets.json';
    if (!file_exists($path)) {
      if ($options['verbose']) {
        $this->output()->writeln('<error>Unable to find ACCESS API key file</error>');
      }
      return;
    }
    $secrets_json_text = file_get_contents($path);
    $secrets_data = json_decode($secrets_json_text, TRUE);
    if (!isset($secrets_data['ramps_api_key']) || !$secrets_data['ramps_api_key']) {
      if ($options['verbose']) {
        $this->output()->writeln('<error>Unable to find ACCESS API key</error>');
      }
      return;
    }

    try {
      $curl = curl_init();

      curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => 'https://allocations-api.access-ci.org/identity/profiles/v1/organizations',
      ]);
      curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'XA-REQUESTER: MATCH',
        'XA-API-KEY: ' . $secrets_data['ramps_api_key'],
      ]);

      $response = curl_exec($curl);

      if (curl_errno($curl)) {
        $error_msg = curl_error($curl);

        throw new \Exception($error_msg);
      }

      curl_close($curl);

      $orgs = json_decode($response);

      if (empty($orgs)) {
        throw new \Exception('No organization results found from source API.');
      }

      /*
      Example API response

      [
      {
      "organization_id": 2735,
      "org_type_id": 4,
      "organization_abbrev": "Exa Corp.",
      "organization_name": "Exa Corporation",
      "organization_url": null,
      "organization_phone": null,
      "nsf_org_code": "T103902",
      "is_reconciled": true,
      "amie_name": null,
      "country_id": 210,
      "state_id": 23,
      "latitude": "42.497508",
      "longitude": "-71.234265",
      "is_msi": null,
      "is_active": true,
      "carnegieCategories":[],
      "state": "Massachusetts",
      "country": "United States",
      "org_type": "Industrial"
      },
      {
      "organization_id": 1231,
      "org_type_id": 6,
      "organization_abbrev": "Quetzal Computationa",
      "organization_name": "Quetzal Computational Associates",
      "organization_url": null,
      "organization_phone": null,
      "nsf_org_code": "6103790",
      "is_reconciled": true,
      "amie_name": null,
      "country_id": null,
      "state_id": null,
      "latitude": "35.124329",
      "longitude": "-106.586556",
      "is_msi": null,
      "is_active": false,
      "carnegieCategories":[],
      "state": null,
      "country": null,
      "org_type": "Other or Unknown"
      }
      ]
       */

      foreach ($orgs as $org) {
        $record = \Drupal::entityQuery('node')
          ->condition('type', 'access_organization')
          ->condition('field_organization_id', $org->organization_id, '=')
          ->execute();

        if (!empty($record)) {
          if ($options['verbose']) {
            $this->output()->writeln('<comment>Record already exists for "' . $org->organization_name . '".</comment>');
          }
          continue;
        }

        // Try to find code by institution name.
        $query = \Drupal::database()
          ->select('carnegie_codes', 'cc')
          ->fields('cc', ['UNITID']);
        $query->condition($query->orConditionGroup()
          ->condition('NAME', $org->organization_name, '=')
          ->condition('NAME', $org->organization_name . '%', 'LIKE')
          );
        $carnegie_code = $query->execute()
          ->fetchField();

        if (!$carnegie_code) {
          if ($options['verbose']) {
            $this->output()->writeln('<comment>Failed to find Carnegie Code for "' . $org->organization_name . '".</comment>');
          }
          $carnegie_code = 0;
        }
        else {
          if ($options['verbose']) {
            $this->output()->writeln('<info>Assigned Carnegie Code ' . $carnegie_code . ' for "' . $org->organization_name . '".</info>');
          }
        }

        $lat = 0;
        if ($org->latitude >= -90 && $org->latitude <= 90) {
          $lat = $org->latitude;
        }
        else {
          if ($options['verbose']) {
            $this->output()->writeln('<error>Latitude out of range for ' . $org->organization_name . ' (' . $org->organization_id . ')</error>');
          }
        }

        $lon = 0;
        if ($org->longitude >= -180 && $org->longitude <= 180) {
          $lon = $org->longitude;
        }
        else {
          if ($options['verbose']) {
            $this->output()->writeln('<error>Longitude out of range for ' . $org->organization_name . ' (' . $org->organization_id . ')</error>');
          }
        }

        // Create database entry.
        $node = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->create([
            'type' => 'access_organization',
            'title' => $org->organization_name,
            'field_organization_id' => $org->organization_id,
            'field_organization_abbrev' => $org->organization_abbrev,
            'field_organization_name' => $org->organization_name,
            'field_organization_url' => $org->organization_url,
            'field_organization_phone' => $org->organization_phone,
            'field_latitude' => $lat,
            'field_longitude' => $lon,
            'field_is_msi' => $org->is_msi ? 1 : 0,
            'field_is_active' => $org->is_active ? 1 : 0,
            'field_carnegie_code' => $carnegie_code,
            'field_state' => $org->state,
            'field_country' => $org->country,
            'field_org_type' => $org->org_type,
          ]);
        $result = $node->save();

        if (!$result) {
          if ($options['verbose']) {
            $this->output()->writeln('<error>Failed to create record for ' . $org->organization_name . ' (' . $org->organization_id . '): ' . $e->getMessage() . '</error>');
          }
          continue;
        }

        if ($options['verbose']) {
          $this->output()->writeln('<info>Created record for ' . $org->organization_name . ' (' . $org->organization_id . ')</info>');
        }
      }
    }
    catch (\Exception $e) {
      if ($options['verbose']) {
        $this->output()->writeln('<error>' . $e->getMessage() . '</error>');
      }
      return;
    }

    if ($options['verbose']) {
      $this->output()->writeln('Finished.');
    }
  }

}
