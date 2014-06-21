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
    return 'text_fields_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $field_type = NULL) {
    $form = array();

    $form['compare_format'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Compare format'),
//      '#default_value' => $settings['compare_format'],
      '#description' => $this->t('This is only used if the "Text processing" instance settings are set to <em>Filtered text (user selects text format)</em>.'),
    );
    if ($field_type == 'text_with_summary') {
      $form['compare_summary'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Compare summary separately'),
//        '#default_value' => $settings['compare_summary'],
        '#description' => $this->t('This is only used if the "Summary input" option is checked in the instance settings.'),
      );
    }
    return parent::buildForm($form, $form_state);
  }
}