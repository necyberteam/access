<?php

namespace Drupal\access_misc\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;

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
    $email_var = $config->get('email_var', 'customfield_10026');
    $access_id = $config->get('access_id_var', 'customfield_10027');

    $form['description'] = [
      '#markup' => $this->t('Set the names of the variables for constant contact that are used on the CreateTicket block for the email and access id fields.'),
    ];
    $form['email_var'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email Field'),
      '#default_value' => $email_var,
      '#required' => TRUE,
      '#description' => $this->t('Name of the field variable in constant contact for email.'),
    ];
    $form['access_id_var'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access ID'),
      '#default_value' => $access_id,
      '#required' => TRUE,
      '#description' => $this->t('Name of the field variable in constant contact for the access id.'),
    ];

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
    $config->set('email_var', $form_state->getValue('email_var'));
    $config->set('access_id_var', $form_state->getValue('access_id_var'));
    $config->save();
  }

}
