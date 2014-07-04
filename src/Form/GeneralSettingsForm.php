<?php

/**
 * @file
 * Contains \Drupal\diff\Form\GeneralSettingsForm.
 */

namespace Drupal\diff\Form;

use Drupal\Core\Form\ConfigFormBase;

class GeneralSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'diff_general_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $field_type = NULL) {
    $config = $this->config('diff.settings');

    $form['diff_theme'] = array(
      '#type' => 'select',
      '#title' => $this->t('CSS options'),
      '#default_value' => $config->get('general_settings' . '.' . 'theme'),
      '#options' => array(
        'default' => $this->t('Classic'),
        'boxes' => $this->t('Boxes'),
      ),
      '#empty_option' => $this->t('- None -'),
      '#description' => $this->t('Alter the CSS used when displaying diff results.'),
    );
    $form['diff_radio_behavior'] = array(
      '#type' => 'select',
      '#title' => $this->t('Diff radio behavior'),
      '#default_value' => $config->get('general_settings' . '.' . 'radio_behavior'),
      '#options' => array(
        'simple' => $this->t('Simple exclusion'),
        'linear' => $this->t('Linear restrictions'),
      ),
      '#empty_option' => $this->t('- None -'),
      '#description' => $this->t('<em>Simple exclusion</em> means that users will not be able to select the same revision, <em>Linear restrictions</em> means that users can only select older or newer revisions of the current selections.'),
    );

    $context_lines = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
    $options = array_combine($context_lines, $context_lines);
    $form['diff_context_lines_leading'] = array(
      '#type' => 'select',
      '#title' => $this->t('Leading context lines'),
      '#description' => $this->t('This governs the number of unchanged leading context "lines" to preserve.'),
      '#default_value' => $config->get('general_settings' . '.' . 'context_lines_leading'),
      '#options' => $options,
    );
    $form['diff_context_lines_trailing'] = array(
      '#type' => 'select',
      '#title' => $this->t('Trailing context lines'),
      '#description' => $this->t('This governs the number of unchanged trailing context "lines" to preserve.'),
      '#default_value' => $config->get('general_settings' . '.' . 'context_lines_trailing'),
      '#options' => $options,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $config = $this->config('diff.settings');
    $config->set('general_settings' . '.' . 'theme', $form_state['values']['diff_theme']);
    $config->set('general_settings' . '.' . 'radio_behavior', $form_state['values']['diff_radio_behavior']);
    $config->set('general_settings' . '.' . 'context_lines_leading', $form_state['values']['diff_context_lines_leading']);
    $config->set('general_settings' . '.' . 'context_lines_trailing', $form_state['values']['diff_context_lines_trailing']);
    $config->save();

    return parent::submitForm($form, $form_state);
  }

}