<?php

/**
 * @file
 * Contains \Drupal\diff\Form\DiffBaseSettingsForm.
 */

namespace Drupal\diff\Form;

use Drupal\Core\Form\ConfigFormBase;

class DiffBaseSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'diff_global_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $field_type = NULL) {
    $config = $this->config('diff.settings');

    $form['field_type'] = array(
      '#type' => 'hidden',
      '#value' => $field_type,
    );
    $form['settings']['show_header'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show field title'),
      '#weight' => -5,
      '#default_value' => $config->get($field_type . '.' . 'show_header'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $config = $this->config('diff.settings');
    $field_type = $form_state['values']['field_type'];

    $config->set($field_type . '.' . 'show_header', $form_state['values']['show_header']);
    $config->save();

    return parent::submitForm($form, $form_state);
  }
}