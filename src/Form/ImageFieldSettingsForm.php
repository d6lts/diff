<?php

/**
 * @file
 * Contains \Drupal\diff\Form\TextFieldsSettingsForm.
 */

namespace Drupal\diff\Form;

class ImageFieldSettingsForm extends DiffBaseSettingsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'diff_image_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $field_type = NULL) {
    $config = $this->config('diff.settings');

    $form = array();
    $form['show_id'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show image ID'),
      '#default_value' => $config->get($field_type . '.' . 'show_id'),
    );
    $form['compare_alt_field'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Compare <em>Alt</em> field'),
      '#default_value' => $config->get($field_type . '.' . 'compare_alt_field'),
      '#description' => $this->t('This is only used if the "Enable <em>Alt</em> field" is checked in the instance settings.'),
    );
    $form['compare_title_field'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Compare <em>Title</em> field'),
      '#default_value' => $config->get($field_type . '.' . 'compare_title_field'),
      '#description' => $this->t('This is only used if the "Enable <em>Title</em> field" is checked in the instance settings.'),
    );
    $form['property_separator'] = array(
      '#type' => 'select',
      '#title' => $this->t('Property separator'),
      '#default_value' => $config->get($field_type . '.' . 'property_separator'),
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

    $config->set($field_type . '.' . 'show_id', $form_state['values']['show_id']);
    $config->set($field_type . '.' . 'compare_alt_field', $form_state['values']['compare_alt_field']);
    $config->set($field_type . '.' . 'compare_title_field', $form_state['values']['compare_title_field']);
    $config->set($field_type . '.' . 'property_separator', $form_state['values']['property_separator']);

    $config->save();

    return parent::submitForm($form, $form_state);
  }

}
