<?php

/**
 * @file
 * Contains \Drupal\diff\TextFieldDiffBuilder.
 */

namespace Drupal\diff;

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
    $settings = $context['settings'];

    // Every item from $field_items is of type FieldItemInterface.
    foreach ($field_items as $field_key => $field_item) {
      $values = $field_item->getValue();
      foreach ($values as $value_key => $value) {
        if (isset($settings[$value_key]) && $settings[$value_key] == TRUE) {
          // Handle the text filter format.
          if ($value_key == 'format') {
            $controller = $this->entityManager->getStorage('filter_format');
            $format = $controller->load($value);
            // The format loaded successfully.
            if ($format != null) {
              $result[$field_key][] = $value_key . ": " . $format->name;
            }
            else {
              $result[$field_key][] = $value_key . ": " . $this->t('Missing format !format', array('!format' => $value));
            }
          }
          // Handle the text summary.
          else if ($value_key == 'summary') {
            if ($value == '') {
              $result[$field_key][] = $value_key . ": " . $this->t('Empty');
            }
            else {
              $result[$field_key][] = $value_key . ": " . $value;
            }
          }
          // Value of the text field.
          else {
            $value_only = TRUE;

            // Check if summary or text format are included in the diff.
            foreach ($settings as $setting => $val) {
              if ($setting != 'value' && $val == TRUE) {
                $value_only = FALSE;
                break;
              }
            }

            if ($value_only) {
              // Don't display 'value' label.
              $result[$field_key][] = $value;
            }
            else {
              $result[$field_key][] = $value_key . ": " . $value;
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
  public function defaultOptions($context) {

  }

  /**
   * {@inheritdoc}
   */
  public function optionsForm($context) {

  }
}