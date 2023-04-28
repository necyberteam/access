<?php

namespace Drupal\cssn\Plugin\Block;

use Drupal\access_misc\Plugin\Util\RolesLabelLookup;
use Drupal\Core\Block\BlockBase;
use Drupal\cssn\Plugin\Util\EndUrl;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\user\Entity\User;

/**
 * Provides a 'Community Persona' Block.
 *
 * @Block(
 *   id = "cssn_block",
 *   admin_label = "Community persona block",
 * )
 */
class PersonaBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get last item in url.
    $end_url = new EndUrl();
    $url_end = $end_url->getUrlEnd();
    $public = TRUE;
    $should_user_load = FALSE;
    if ($url_end == 'community-persona') {
      $public = FALSE;
      $should_user_load = TRUE;
    }
    if (is_numeric($url_end)) {
      $user = User::load($url_end);
      if ($user !== NULL) {
        $should_user_load = TRUE;
      }
    }
    if ($should_user_load) {
      $user = $public ? $user : \Drupal::currentUser();
      $user_entity = \Drupal::entityTypeManager()->getStorage('user')->load($user->id());
      $user_image = $user_entity->get('user_picture');
      if ($user_image->entity !== NULL) {
        $user_image = $user_image->entity->getFileUri();
        $user_image = \Drupal::service('file_url_generator')->generateAbsoluteString($user_image);
        $user_image = '<img src="' . $user_image . '" class="img-fluid mb-3 border border-black" />';
      } else {
        $user_image = '<svg version="1.1" class="mb-3 border border-black" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
           viewBox="0 0 448 448" style="enable-background:new 0 0 448 448;" xml:space="preserve">
            <style type="text/css">
              .st0{fill:#ECF9F8;}
              .st1{fill:#B7CDD1;}
            </style>
            <rect class="st0" width="448" height="448"/>
            <path class="st1" d="M291,158v14.5c0,40-32.4,72.5-72.5,72.5s-72.5-32.4-72.5-72.5V158c0-5,0.5-9.8,1.4-14.5h27.5
              c27,0,50.6-14.8,63-36.7c10.6,13.5,27.1,22.2,45.6,22.2h1.2C288.8,137.9,291,147.7,291,158z M102.6,158v14.5
              c0,64,51.9,115.9,115.9,115.9s115.9-51.9,115.9-115.9V158c0-64-51.9-115.9-115.9-115.9S102.6,94,102.6,158z"/>
            <path class="st1" d="M151.2,306.3c-71.9,7.8-128.2,68.1-130,141.7h405.6c-1.8-73.6-58.1-133.8-130.1-141.6
              c-4.8-0.5-9.5,1.7-12.4,5.6l-48.8,65c-5.8,7.7-17.4,7.7-23.2,0l-48.8-65l0.1-0.1C160.7,308,156,305.9,151.2,306.3z"/>
            </svg>
            ';
      }
      // Create Drupal 9 link to edit user profile with ?destination=community-persona.
      $edit_url = Url::fromUri('internal:/user/' . $user->id() . '/edit?destination=community-persona');
      $edit_link = Link::fromTextAndUrl('Edit Profile', $edit_url);
      $edit_link = $edit_link->toRenderable();
      $edit_link['#attributes'] = [
        'class' => [
          'btn',
          'btn-primary',
          'btn-sm',
          'w-100',
        ],
      ];
      $edit_link = $public ? "" : $edit_link;
      $first_name = $user_entity->get('field_user_first_name')->value;
      $last_name = $user_entity->get('field_user_last_name')->value;
      $institution = $user_entity->get('field_institution')->value;
      $roles = $user_entity->getRoles();
      $is_student = array_search('student', $roles) !== FALSE;
      $academic_status = $is_student
        ? $user_entity->get('field_academic_status')->value : '';
      $academic_status = $this->getAcademicStatuslongform($academic_status);
      $key = array_search('authenticated', $roles);
      if ($key !== false) {
        unset($roles[$key]);
      }
      $role = new RolesLabelLookup($roles);
      $roles = $role->getRoleLabelsString();
      $regions = $user_entity->get('field_region')->getValue();
      $terms = [];
      foreach ($regions as $region) {
        $region_tid = $region['target_id'];
        $terms[$region_tid] = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($region_tid)->getName();
      }
      if (!$public) {
        $cssn_role_url = Url::fromUri('internal:/form/edit-your-cssn-roles?destination=community-persona');
        $cssn_role_link = Link::fromTextAndUrl('Edit Roles', $cssn_role_url);
        $cssn_role_renderable = $cssn_role_link->toRenderable();
        $cssn_role = $cssn_role_renderable;
        $cssn_role['#attributes']['class'] = ['text-dark'];
      } else {
        $cssn_role = "";
      }

      // Programs.
      $program = implode(', ', $terms);
      // If $terms contains 'ACCESS CSSN', then the user is a CSSN member.
      $cssn_member = in_array('ACCESS CSSN', $terms) ? TRUE : FALSE;
      //$ws_query = \Drupal::entityQuery('webform_submission')
      //  ->condition('uid', $user->id())
      //  ->condition('uri', '/form/join-the-cssn-network');
      //$ws_results = $ws_query->execute();
      $cssn_indicator = "";
      if ($cssn_member) {
        $cssn_indicator = "<span class='text-primary'><i class='fa-solid fa-square'></i></span>";
        $cssn = "CSSN Member";
      } elseif ($public) {
        $cssn_indicator = "<span class='text-secondary'><i class='fa-solid fa-square'></i></span>";
        $cssn = "Not a CSSN Member";
      } else {
        $cssn_url = Url::fromUri('internal:/form/join-the-cssn-network');
        $cssn_link = Link::fromTextAndUrl('Join the CSSN', $cssn_url);
        $cssn_renderable = $cssn_link->toRenderable();
        $cssn = $cssn_renderable;
        $cssn['#attributes']['class'] = ['btn', 'btn-primary', 'btn-sm', 'py-1', 'px-2'];
      }
      $cssn_more_url = Url::fromUri('internal:/cssn');
      $cssn_more_link = Link::fromTextAndUrl('Find out More', $cssn_more_url);
      $cssn_more_renderable = $cssn_more_link->toRenderable();
      $cssn_more = $cssn_more_renderable;
      $cssn_more['#attributes']['class'] = ['text-dark'];

      // Get the user's email address.
      $user_id = $user->id();
      // Show the email button on public profiles.
      $send_email = $public ? "<a href='/user/$user_id/contact?destination=community-persona/$user_id' class='w-100 btn btn-primary btn-sm py-1 px-2'><i class='fa-solid fa-envelope'></i> Send Email</a>" : "";

      $persona_block['string'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="p-3">
                          {{ user_image | raw }}
                          <h2>{{ first_name }} {{ last_name }}</h2>
                          <h4>{{ institution }}</h4>
                          {% if academic_status %}
                            <div id="academic_status_style-persona">{{ academic_status }}</div>
                          {% endif %}
                          <div class="d-flex justify-content-between">
                            <p>{{ cssn_indicator | raw }} <strong>{{ cssn }}</strong></p>
                            <div><i class="text-dark fa-regular fa-circle-info"></i> {{ cssn_more }}</div>
                          </div>
                          <div class="d-flex justify-content-between border-top border-bottom mb-3 py-3 border-secondary">
                            <div><b>{{ role_text }}:</b><br />{{ roles | raw }}</div>
                            {% if cssn_role %}
                              <div><i class="text-dark fa-solid fa-pen-to-square"></i> {{ cssn_role }}</div>
                            {% endif %}
                          </div>
                          <p><b>{{ program_text }}:</b><br /> {{ program }}</p>
                          <div class="w-100">
                           {{ send_email | raw }}
                          {{ edit_link | raw }}
                          </div>
                        </div>',
        '#context' => [
          'user_image' => $user_image,
          'edit_link' => $edit_link,
          'first_name' => $first_name,
          'last_name' => $last_name,
          'institution' => $institution,
          'academic_status' => $academic_status,
          'cssn' => $cssn,
          'cssn_indicator' => $cssn_indicator,
          'cssn_more' => $cssn_more,
          'roles' => $roles,
          'role_text' => t('Roles'),
          'cssn_role' => $cssn_role,
          'program' => $program,
          'program_text' => t('Programs'),
          'send_email' => $send_email,
        ],
      ];
      return $persona_block;
    } else {
      return [];
    }
  }

  /**
   * Map the short name to the long name for academic status.
   *
   * @param string $short short name for academic status
   * @return string long name for academic status
   */
  private function getAcademicStatuslongform($short) {
    switch ($short) {
      case 'first':
        return 'Undergraduate student - first year';
      case 'second':
        return 'Undergraduate student - second year';
      case 'third':
        return 'Undergraduate student - third year';
      case 'fourth':
        return 'Undergraduate student - fourth year';
      case 'masters':
        return 'Graduate student - masters program';
      case 'phd':
        return 'Graduate student - PhD program';
      case 'postdoc':
        return 'Postdoctoral student';
      case 'fellow':
        return 'Fellow';
      case 'other':
        return 'Not in an academic program but interested in research computing facilitation';
      default:
        return '';
    }
  }

  /**
   * @return int
   */
  public function getCacheMaxAge() {
    return 0;
  }
}
