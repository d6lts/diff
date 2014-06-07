<?php

/**
 * @file
 * Contains \Drupal\diff\Diff\FieldDiffBuilderInterface.
 */

namespace Drupal\diff\Diff;

use Drupal\Core\Field\FieldItemList;

/**
 * Defines an interface for classes that handle field comparisons.
 */
interface FieldDiffBuilderInterface {

  /**
   * Whether this field builder should be used to build the field diff.
   *
   * @param array $context
   *   Information about the field to be compared.
   *
   * @return bool
   *   TRUE if this builder should be used or FALSE to let other builders
   *   decide.
   */
  public function applies(array $context);

  /**
   * Builds the field data to be compared based on the context.
   *
   * Example: this function may or may not include the summary of a
   * text_with_summary field in the comparison based on the given context.
   *
   * @param FieldItemList $field_items
   *   Represents an entity field; that is, a list of field item objects.
   *
   * @param array $context
   *   An array of containing information about what properties of a field
   *   item to be included into comparison.
   *
   * @return mixed
   *   An array of data to be compared. If an empty array is returned it means
   *   that a field is either empty or no properties need to be compared for
   *   that field.
   */
  public function build(FieldItemList $field_items, array $context);

  /**
   * @param $context
   *  An array containing information about the current context
   *  E.g. field type for which to build the diff settings form.
   *
   * @return mixed
   *   A settings form for a field type.
   */
  public function optionsForm($context);

  /**
   * @param $context
   *   An array containing information about the current context
   *   E.g. the field type for which to return diff default options.
   *
   * @return mixed
   *  Array containing default options for field type settings form.
   */
  public function defaultOptions($context);

}
