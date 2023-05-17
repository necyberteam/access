<?php

namespace Drupal\cssn\Plugin\Util;

use Drupal\webform\Entity\WebformSubmission;

/**
 * Lookup connected Project+ nodes.
 *
 * @ProjectLookup(
 *   id = "project_lookup",
 *   title = @Translation("Project Lookup"),
 *   description = @Translation("Lookup Users project+ connections for community
 *   persona.")
 * )
 */
class ProjectLookup {
  /**
   * Store project submissions.
   * $var array
   */
  private $projects;

  /**
   * Array of sorted projects.
   * $var array
   */
  private $projects_sorted;

  /**
   * Function to return projects.
   */
  public function __construct($project_fields, $project_user_id, $project_user_email, $public = FALSE) {
    $this->runQuery($project_fields, $project_user_id, $project_user_email);
  }

  /**
   * Function to Run entity query by type.
   */
  public function runQuery($project_fields, $project_user_id, $project_user_email) {
    $query = \Drupal::database()->select('webform_submission_data', 'wsd');
    $orGroup = $query->orConditionGroup()
      ->condition('wsd.value', $project_user_id)
      ->condition('wsd.value', $project_user_email);
    $query->fields('wsd', ['sid', 'name']);
    $query->condition($orGroup);
    $query->condition('wsd.webform_id', 'project');
    $result = $query->execute()->fetchAll();
    if ($result != NULL) {
      foreach ($result as $project_result) {
        $wf = WebformSubmission::load($project_result->sid);
        $wf_lookup = $wf->getData();
        $this->projects[] = [
          'title' => $wf_lookup['project_title'],
          'name' => $project_fields[$project_result->name],
          'status' => $wf_lookup['status'],
          'sid' => $project_result->sid,
        ];
      }
    }
  }

  /**
   * Function to sort by status.
   */
  public function sortStatusProjects() {
    $projects = $this->projects;
    $recruiting = $this->arrayPickSort($projects, 'Recruiting');
    $in_progress = $this->arrayPickSort($projects, 'In Progress');
    $in_review = $this->arrayPickSort($projects, 'Reviewing Applicants');
    $on_hold = $this->arrayPickSort($projects, 'On Hold');
    $finishing_up = $this->arrayPickSort($projects, 'Finishing Up');
    $complete = $this->arrayPickSort($projects, 'Complete');
    $halted = $this->arrayPickSort($projects, 'Halted');
    // Combine all of the arrays.
    $projects_sorted = $recruiting + $in_progress + $in_review + $on_hold + $finishing_up + $complete + $halted;
    $this->projects_sorted = $projects_sorted;
  }

  /**
   * Function to pick out a status into an array and sort by title.
   */
  public function arrayPickSort($array, $sortby) {
    $sorted = [];
    if ($array == NULL) {
      return;
    }
    foreach ($array as $key => $value) {
      if ($value['status'] && $value['status'] == $sortby) {
        $sorted[$key] = $value;
      }
    }
    return $sorted;
  }

  /**
   * Function to return styled list.
   */
  public function getProjectList() {
    $n = 1;
    $project_link = '';
    if ($this->projects_sorted == NULL) {
      return;
    }
    foreach ($this->projects_sorted as $project) {
      $stripe_class = $n % 2 == 0 ? 'bg-light' : '';
      $title = $project['title'];
      $sid = $project['sid'];
      $project_status = $project['status'];
      $project_name = $project['name'];
      // if (($project_status == 'Recruiting' && $project_name == 'Interested') || $project_name != 'Interested') {
        $lowercase = lcfirst($project_name);
        $first_letter = substr($lowercase, 0, 1);
        $project_name = "<div data-bs-toggle='tooltip' data-bs-placement='left' title='$project_name'>
          <i class='text-dark fa-solid fa-circle-$first_letter h2'></i>
        </div>";
        $project_link .= "<li class='d-flex p-3 $stripe_class'>
          <div class='text-truncate' style='width: 400px;'>
            <a href='/admin/structure/webform/manage/project/submission/$sid'>$title</a>
          </div>
          <div class='font-weight-bold ms-5'>
            $project_name
          </div>
          <div class='ms-2'>
            $project_status
          </div>
        </li>";
        $n++;
      // }
    }
    return $project_link;
  }

  /**
   * Function to return projects.
   */
  public function getProjects() {
    return $this->projects;
  }

}
