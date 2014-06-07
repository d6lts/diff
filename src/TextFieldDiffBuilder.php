<?php

/**
 * @file
 * Contains \Drupal\diff\TextFieldDiffBuilder.
 */

namespace Drupal\diff;

use Drupal\diff\Diff\FieldDiffBuilderInterface;
use Drupal\Core\Field\FieldItemList;


class TextFieldDiffBuilder implements FieldDiffBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(array $context) {
    // List of the field types for which this class provides diff support.
    $field_types = array('text_with_summary', 'text_long', 'text');
    // Check if this class can handle diff for a certain field.
    if (in_array($context['field_type'], $field_types)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(FieldItemList $field_items, array $context) {
    $result = array();

    foreach ($field_items as $key => $field_item) {
      $values = $field_item->getValue();
      foreach ($values as $id => $value) {
        if (isset($context['settings'][$id]) && $context['settings'][$id]) {
          $result[$key][] = $id;
          $result[$key][] = $value;
        }
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultOptions($context) {

  }

  /**
   * {@inheritdoc}
   */
  public function optionsForm($context) {

  }
}