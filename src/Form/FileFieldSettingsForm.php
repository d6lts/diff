<?php

/**
 * @file
 * Contains \Drupal\diff\Form\FileFieldSettingsForm.
 */

namespace Drupal\diff\Form;

class FileFieldSettingsForm extends DiffFieldBaseSettingsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'diff_file_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $field_type = NULL) {
    $config = $this->config('diff.settings');

    $form = array();
    $form['show_id'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show file ID'),
      '#default_value' => $config->get('field_types.' . $field_type . '.' . 'show_id'),
    );
    $form['compare_description_field'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Compare description field'),
      '#default_value' => $config->get('field_types.' . $field_type . '.' . 'compare_description_field'),
      '#description' => $this->t('This is only used if the "Enable <em>Description</em> field" is checked in the instance settings.'),
    );
    $form['compare_display_field'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Compare display state field'),
      '#default_value' => $config->get('field_types.' . $field_type . '.' . 'compare_display_field'),
      '#description' => $this->t('This is only used if the "Enable <em>Display</em> field" is checked in the field settings.'),
    );
    $form['property_separator'] = array(
      '#type' => 'select',
      '#title' => $this->t('Property separator'),
      '#default_value' => $config->get('field_types.' . $field_type . '.' . 'property_separator'),
      '#description' => $this->t('Provides the ability to show properties inline or across multiple lines.'),
      '#options' => array(
        ', ' => $this->t('Comma (,)'),
        '; ' => $this->t('Semicolon (;)'),
        ' ' => $this->t('Space'),
        'nl' => $this->t('New line'),
      ),
    );

    return parent::buildForm($form, $form_state, $field_type);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $config = $this->config('diff.settings');
    $field_type = $form_state['values']['field_type'];

    $keys = array('show_id', 'compare_description_field', 'compare_display_field', 'property_separator');
    foreach ($keys as $key) {
      $config->set('field_types.' . $field_type . '.' . $key, $form_state['values'][$key]);
    }
    $config->save();

    return parent::submitForm($form, $form_state);
  }

}
