<?php

/**
 * @file
 * Contains \Drupal\diff\Diff\Entity\TextFieldDiffBuilder.
 */

namespace Drupal\diff\Diff\Entity;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\diff\Diff\FieldDiffBuilderInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Field\FieldDefinitionInterface;


class TextFieldDiffBuilder implements FieldDiffBuilderInterface {
  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;


  /**
   * Constructs a TextFieldDiffBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   * @param FormBuilderInterface $form_builder
   *   Form builder service.
   */
  public function __construct(EntityManagerInterface $entityManager, FormBuilderInterface $form_builder) {
    $this->entityManager = $entityManager;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(FieldDefinitionInterface $field_definition) {
    // List of the field types for which this class provides diff support.
    $field_types = array('text_with_summary', 'text_long', 'text');
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
      $values = $field_item->getValue();
      // Compare text formats.
      if (isset($compare['compare_format']) && $compare['compare_format'] == 1) {
        if (isset($values['format'])) {
          $controller = $this->entityManager->getStorage('filter_format');
          $format = $controller->load($values['format']);
          // The format loaded successfully.
          $label = $this->t('Format');
          if ($format != null) {
            $result[$field_key][] = $label . ": " . $format->name;
          }
          else {
            $result[$field_key][] = $label . ": " . $this->t('Missing format !format', array('!format' => $values[$value_key]));
          }
        }
      }
      // Handle the text summary.
      if (isset($compare['compare_summary']) && $compare['compare_summary'] == 1) {
        if (isset($values['summary'])) {
          $label = $this->t('Summary');
          if ($values['summary'] == '') {
            $result[$field_key][] = $label . ":\n" . $this->t('Empty');
          }
          else {
            $result[$field_key][] = $label . ":\n" . $values['summary'];
          }
        }
      }
      // Compare field values.
      if (isset($values['value'])) {
        $value_only = TRUE;
        // Check if summary or text format are included in the diff.
        if ($compare['compare_format'] && $compare['compare_format'] == 1 || isset($compare['compare_summary']) && $compare['compare_summary'] == 1) {
          $value_only = FALSE;
        }

        $label = $this->t('Value');
        if ($value_only) {
          // Don't display 'value' label.
          $result[$field_key][] = $values['value'];
        }
        else {
          $result[$field_key][] = $label . ":\n" . $values['value'];
        }
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm($field_type) {
    return $this->formBuilder->getForm('Drupal\diff\Form\TextFieldsSettingsForm', $field_type);
  }

}