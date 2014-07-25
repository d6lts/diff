<?php

/**
 * @file
 * Contains \Drupal\diff\Diff\Entity\ListDiffBuilder.
 */

namespace Drupal\diff\Diff\Entity;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\diff\Diff\FieldDiffBuilderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Field\FieldDefinitionInterface;


class ListDiffBuilder implements FieldDiffBuilderInterface {
  use StringTranslationTrait;

  /**
   * Form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;


  /**
   * Constructs a TaxonomyReferenceDiffBuilder object.
   *
   * @param FormBuilderInterface $form_builder
   *   Form builder service.
   */
  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(FieldDefinitionInterface $field_definition) {
    // List of the field types for which this class provides diff support.
    $field_types = array('list_boolean', 'list_text', 'list_float', 'list_integer');
    // Check if this class can handle diff for a certain field.
    if (in_array($field_definition->getType(), $field_types)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(FieldItemListInterface $field_items, array $context) {
    $result = array();
    $compare = $context['settings']['compare'];

    // Every item from $field_items is of type FieldItemInterface.
    foreach ($field_items as $field_key => $field_item) {
      // Build the array for comparison only if the field is not empty.
      if (!$field_item->isEmpty()) {
        $possible_options = $field_item->getPossibleOptions();
        $values = $field_item->getValue();
        if (isset($compare['compare'])) {
          switch ($compare['compare']) {
            case 'both':
              $result[$field_key][] = $possible_options[$values['value']] . ' (' . $values['value'] . ')';
              break;
            case 'label':
              $result[$field_key][] = $possible_options[$values['value']];
              break;
            default:
              $result[$field_key][] = $values['value'];
              break;
          }
        }
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm($field_type) {
    return $this->formBuilder->getForm('Drupal\diff\Form\ListFieldSettingsForm', $field_type);
  }

}