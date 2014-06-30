<?php

/**
 * @file
 * Contains \Drupal\diff\Form\TextFieldsSettingsForm.
 */

namespace Drupal\diff\Form;

class TextFieldsSettingsForm extends DiffBaseSettingsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'diff_text_fields_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $field_type = NULL) {
    $config = $this->config('diff.settings');

    $form = array();
    $form['field_type'] = array(
      '#type' => 'hidden',
      '#value' => $field_type,
    );
    $form['settings']['compare_format'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Compare format'),
      '#default_value' => $config->get($field_type . '.' . 'compare_format'),
      '#description' => $this->t('This is only used if the "Text processing" instance settings are set to <em>Filtered text (user selects text format)</em>.'),
    );
    if ($field_type == 'text_with_summary') {
      $form['settings']['compare_summary'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Compare summary separately'),
        '#default_value' => $config->get($field_type . '.' . 'compare_summary'),
        '#description' => $this->t('This is only used if the "Summary input" option is checked in the instance settings.'),
      );
    }
    return parent::buildForm($form, $form_state, $field_type);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $config = $this->config('diff.settings');
    $field_type = $form_state['values']['field_type'];

    $config->set($field_type . '.' . 'compare_format', $form_state['values']['compare_format']);
    if ($field_type == 'text_with_summary') {
      $config->set($field_type . '.' . 'compare_summary', $form_state['values']['compare_summary']);
    }
    $config->save();

    return parent::submitForm($form, $form_state);
  }

}
