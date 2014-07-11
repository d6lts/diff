<?php

/**
 * @file
 * Contains \Drupal\diff\Form\DiffFieldBaseSettingsForm.
 */

namespace Drupal\diff\Form;

use Drupal\Core\Form\ConfigFormBase;

class DiffFieldBaseSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'diff_field_base_settings';
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
    $form['show_header'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show field title'),
      '#weight' => -5,
      '#default_value' => $config->get($field_type . '.' . 'show_header'),
    );
    $form['markdown'] = array(
      '#type' => 'select',
      '#title' => $this->t('Markdown callback'),
      '#default_value' => $config->get($field_type . '.' . 'markdown'),
      '#options' => array(
        'drupal_html_to_text' => $this->t('Drupal HTML to Text'),
        'filter_xss' => $this->t('Filter XSS (some tags)'),
        'filter_xss_all' => $this->t('Filter XSS (all tags)'),
      ),
      '#description' => $this->t('These provide ways to clean markup tags to make comparisons easier to read.'),
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
    $config->set($field_type . '.' . 'markdown', $form_state['values']['markdown']);
    $config->save();

    return parent::submitForm($form, $form_state);
  }
}