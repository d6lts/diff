<?php

/**
 * @file
 * Contains \Drupal\diff\Controller\SettingsController.
 */

namespace Drupal\diff\Controller;

use Drupal\Core\StringTranslation\StringTranslationTrait;

class AdminController {
  use StringTranslationTrait;

  /**
   * General settings for Diff.
   */
  public function generalSettings() {
    return array(
      '#markup' => $this->t('Here are the general settings for Diff module'),
    );
  }

  /**
   * Lists all the field types from the system.
   */
  public function fieldTypesList() {
    // @todo This content will be replaced. Only testing purposes.
    $build['help'] = array(
      '#type' => 'markup',
      '#markup' => $this->t('This page will list all the fields types found on the system.<br />')
    );

    $build['links'] = array(
      '#type' => 'markup',
      '#markup' => t('<a href="@url1">text</a><br /><a href="@url2">text_long</a><br /><a href="@url3">text_with_summary</a><br />',
        array(
          '@url1' => url('admin/config/content/diff/fields/text'),
          '@url2' => url('admin/config/content/diff/fields/text_long'),
          '@url3' => url('admin/config/content/diff/fields/text_with_summary')
        )
      )
    );

    return $build;
  }
}
