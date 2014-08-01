<?php

/**
 * @file
 * Contains \Drupal\diff\Form\TextFieldsSettingsForm.
 */

namespace Drupal\diff\Form;

use Drupal\Core\Form\FormStateInterface;


class TextFieldsSettingsForm extends DiffFieldBaseSettingsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'diff_text_fields_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $field_type = NULL) {
    $config = $this->config('diff.settings');

    $form = array();
    $form['compare_format'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Compare format'),
      '#default_value' => $config->get('field_types.' . $field_type . '.' . 'compare_format'),
      '#description' => $this->t('This is only used if the "Text processing" instance settings are set to <em>Filtered text (user selects text format)</em>.'),
    );
    if ($field_type == 'text_with_summary') {
      $form['compare_summary'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Compare summary separately'),
        '#default_value' => $config->get('field_types.' . $field_type . '.' . 'compare_summary'),
        '#description' => $this->t('This is only used if the "Summary input" option is checked in the instance settings.'),
      );
    }
    return parent::buildForm($form, $form_state, $field_type);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('diff.settings');
    $field_type = $form_state['values']['field_type'];

    $keys = array('compare_format');
    if ($field_type == 'text_with_summary') {
      $keys[] = 'compare_summary';
    }
    foreach ($keys as $key) {
      $config->set('field_types.' . $field_type . '.' . $key, $form_state['values'][$key]);
    }
    $config->save();

    return parent::submitForm($form, $form_state);
  }

}
