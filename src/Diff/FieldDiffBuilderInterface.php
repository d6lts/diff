<?php

/**
 * @file
 * Contains \Drupal\diff\Diff\FieldDiffBuilderInterface.
 */

namespace Drupal\diff\Diff;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Defines an interface for classes that handle field comparisons.
 */
interface FieldDiffBuilderInterface {

  /**
   * Whether this field builder should be used to build the field diff.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @return bool
   *   TRUE if this builder should be used or FALSE to let other builders
   *   decide.
   */
  public function applies(FieldDefinitionInterface $field_definition);

  /**
   * Builds the field data to be compared based on the context.
   *
   * Example: this function may or may not include the summary of a
   * text_with_summary field in the comparison based on the given context.
   *
   * @param FieldItemListInterface $field_items
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
  public function build(FieldItemListInterface $field_items, array $context);

  /**
   * @param $field_type
   *   Field type for which to build the settings form.
   *
   * @return array
   *   A render form array with the settings form for this particular field type.
   *   If no additional settings are needed it is recommended to return the
   *   base settings form:
   * @code
   * $this->formBuilder->getForm('Drupal\diff\Form\DiffFieldBaseSettingsForm', $field_type);
   * @endcode
   */
  public function getSettingsForm($field_type);

}
