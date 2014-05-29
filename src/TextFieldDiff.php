<?php

/**
 * @file
 * Contains Drupal\diff\TextFieldDiff.
 */

namespace Drupal\diff;

class TextFieldDiff implements FieldDiffInterface {

  /**
   * {@inheritdoc}
   */
  public function getFieldProvider() {
    return 'text';
  }

  /**
   * {@inheritdoc}
   */
  public function view($field_items, $context) {
    $result = array();
    $index = 0;
    foreach ($field_items as $item) {
      $result[$index++] = $item->value;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultOptions($field_type) {

  }

  /**
   * {@inheritdoc}
   */
  public function optionsForm($field_type, $settings) {

  }

}