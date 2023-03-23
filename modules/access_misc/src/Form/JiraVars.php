<?php

namespace Drupal\access_misc\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Component\Utility\Xss;

/**
 * Creates a form to update variables for the submit ticket button.
 */
class JiraVars extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jira_vars';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'access_misc.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = \Drupal::configFactory()->getEditable('access_misc.settings');
    $misc_var = $config->get('misc_var');
    $access_id = $config->get('access_id_var');

    $form['description'] = [
      '#markup' => $this->t('Set the names of the variables for constant contact that are used on the CreateTicket block for the email and access id fields.'),
    ];
    $form['access_id_var'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access ID'),
      '#default_value' => $access_id,
      '#required' => TRUE,
      '#description' => $this->t('Name of the field variable in constant contact for the access id.'),
    ];
    $form['misc_var'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Misc Field'),
      '#default_value' => $misc_var,
      '#required' => FALSE,
      '#description' => $this->t('Add in misc variables to set values.'),
    ];
    $form['tokens'] = \Drupal::service('token.tree_builder')->buildRenderable(['user']);

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update Variables'),
      '#submit' => [[$this, 'submitForm']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('access_misc.settings');
    $misc_var = Xss::filter($form_state->getValue('misc_var'));
    $access_id = Xss::filter($form_state->getValue('access_id_var'));
    $config->set('misc_var', $misc_var);
    $config->set('access_id_var', $access_id);
    $config->save();
  }

}
