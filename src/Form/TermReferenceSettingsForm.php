<?php

/**
 * @file
 * Contains \Drupal\diff\Form\TermReferenceSettingsForm.
 */

namespace Drupal\diff\Form;

class TermReferenceSettingsForm extends DiffFieldBaseSettingsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'diff_term_reference_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $field_type = NULL) {
    $config = $this->config('diff.settings');

    $form['show_name'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show term name'),
      '#default_value' => $config->get('field_types.' . $field_type . '.' . 'show_name'),
    );
    $form['show_id'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show term ID'),
      '#default_value' => $config->get('field_types.' . $field_type . '.' . 'show_id'),
    );

    return parent::buildForm($form, $form_state, $field_type);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $config = $this->config('diff.settings');
    $field_type = $form_state['values']['field_type'];

    $keys = array('show_name', 'show_id');
    foreach ($keys as $key) {
      $config->set('field_types.' . $field_type . '.' . $key, $form_state['values'][$key]);
    }
    $config->save();

    return parent::submitForm($form, $form_state);
  }

}
