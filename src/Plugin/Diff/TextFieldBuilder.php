<?php

/**
 * @file
 * Contains \Drupal\diff\Plugin\Diff\TextFieldBuilder
 */

namespace Drupal\diff\Plugin\Diff;

use Drupal\diff\FieldDiffBuilderBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * @FieldDiffBuilder(
 *   id = "text_field_diff_builder",
 *   label = @Translation("Text Field Diff"),
 *   field_types = {
 *     "text_with_summary",
 *     "text",
 *     "text_long"
 *   },
 * )
 */
class TextFieldBuilder extends FieldDiffBuilderBase {

  function build(FieldItemListInterface $field_items) {
    $result = array();
//    $compare = $context['settings']['compare'];
//    // Every item from $field_items is of type FieldItemInterface.
//    foreach ($field_items as $field_key => $field_item) {
//      $values = $field_item->getValue();
//      // Compare text formats.
//      if (isset($compare['compare_format']) && $compare['compare_format'] == 1) {
//        if (isset($values['format'])) {
//          $controller = $this->entityManager->getStorage('filter_format');
//          $format = $controller->load($values['format']);
//          // The format loaded successfully.
//          $label = $this->t('Format');
//          if ($format != NULL) {
//            $result[$field_key][] = $label . ": " . $format->name;
//          }
//          else {
//            // @todo Solve $value_key is undefined.
//            $result[$field_key][] = $label . ": " . $this->t('Missing format !format', array('!format' => $values[$value_key]));
//          }
//        }
//      }
//      // Handle the text summary.
//      if (isset($compare['compare_summary']) && $compare['compare_summary'] == 1) {
//        if (isset($values['summary'])) {
//          $label = $this->t('Summary');
//          if ($values['summary'] == '') {
//            $result[$field_key][] = $label . ":\n" . $this->t('Empty');
//          }
//          else {
//            $result[$field_key][] = $label . ":\n" . $values['summary'];
//          }
//        }
//      }
//      // Compare field values.
//      if (isset($values['value'])) {
//        $value_only = TRUE;
//        // Check if summary or text format are included in the diff.
//        if ($compare['compare_format'] && $compare['compare_format'] == 1 || isset($compare['compare_summary']) && $compare['compare_summary'] == 1) {
//          $value_only = FALSE;
//        }
//        $label = $this->t('Value');
//        if ($value_only) {
//          // Don't display 'value' label.
//          $result[$field_key][] = $values['value'];
//        }
//        else {
//          $result[$field_key][] = $label . ":\n" . $values['value'];
//        }
//      }
//    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['compare_format'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Compare format'),
//      '#default_value' => $config->get('field_types.' . $field_type . '.' . 'compare_format'),
      '#description' => $this->t('This is only used if the "Text processing" instance settings are set to <em>Filtered text (user selects text format)</em>.'),
    );
//    if ($field_type == 'text_with_summary') {
//      $form['compare_summary'] = array(
//        '#type' => 'checkbox',
//        '#title' => $this->t('Compare summary separately'),
//        '#default_value' => $config->get('field_types.' . $field_type . '.' . 'compare_summary'),
//        '#description' => $this->t('This is only used if the "Summary input" option is checked in the instance settings.'),
//      );
//    }

    return parent::buildConfigurationForm($form, $form_state);

  }
}
