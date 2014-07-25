<?php

/**
 * @file
 * Contains \Drupal\diff\Form\ListFieldSettingsForm.
 */

namespace Drupal\diff\Form;

class ListFieldSettingsForm extends DiffFieldBaseSettingsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'diff_list_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $field_type = NULL) {
    $config = $this->config('diff.settings');

    $form = array();
    $form['compare'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Comparison method'),
      '#options' => array(
        'label' => $this->t('Label'),
        'key' => $this->t('Key'),
        'both' => $this->t('Label (key)'),
      ),
      '#default_value' => $config->get('field_types.' . $field_type . '.' . 'compare'),
    );

    return parent::buildForm($form, $form_state, $field_type);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $config = $this->config('diff.settings');
    $field_type = $form_state['values']['field_type'];

    $config->set('field_types.' . $field_type . '.' . 'compare', $form_state['values']['compare']);
    $config->save();

    return parent::submitForm($form, $form_state);
  }

}
