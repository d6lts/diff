<?php

/**
 * @file
 * Contains \Drupal\diff\TextFieldDiffBuilder.
 */

namespace Drupal\diff\Diff\Entity;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\diff\Diff\FieldDiffBuilderInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;


class TextFieldDiffBuilder implements FieldDiffBuilderInterface {
  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a TextFieldDiffBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entityManager) {
    $this->entityManager = $entityManager;
  }

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
  public function build(FieldItemListInterface $field_items, array $context) {
    $result = array();
    $compare = $context['settings']['compare'];

    // Every item from $field_items is of type FieldItemInterface.
    foreach ($field_items as $field_key => $field_item) {
      $values = $field_item->getValue();
      foreach ($compare as $value_key) {
        if (isset($values[$value_key])) {
          if ($value_key == 'format') {
            $controller = $this->entityManager->getStorage('filter_format');
            $format = $controller->load($values[$value_key]);
            // The format loaded successfully.
            $label = $this->t(ucfirst($value_key));
            if ($format != null) {
              $result[$field_key][] = $label . ": " . $format->name;
            }
            else {
              $result[$field_key][] = $label . ": " . $this->t('Missing format !format', array('!format' => $values[$value_key]));
            }
          }
          // Handle the text summary.
          else if ($value_key == 'summary') {
            $label = $this->t(ucfirst($value_key));
            if ($values[$value_key] == '') {
              $result[$field_key][] = $label . ":\n" . $this->t('Empty');
            }
            else {
              $result[$field_key][] = $label . ":\n" . $values[$value_key];
            }
          }
          // Value of the text field.
          else if ($value_key == 'value') {
            $value_only = FALSE;

            // Check if summary or text format are included in the diff.
            if (count($compare) == 1) {
              $value_only = TRUE;
            }

            $label = $this->t(ucfirst($value_key));
            if ($value_only) {
              // Don't display 'value' label.
              $result[$field_key][] = $values[$value_key];
            }
            else {
              $result[$field_key][] = $label . ":\n" . $values[$value_key];
            }
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
    return 'Drupal\diff\Form\TextFieldsSettingsForm';
  }

  /**
   * {@inheritdoc}
   */
  public function defaultOptions($context) {

  }
}