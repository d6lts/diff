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
  public function buildForm(array $form, array &$form_state) {
    $form['show_header'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show field title'),
      '#weight' => -5,
    );

    return parent::buildForm($form, $form_state);
  }
}