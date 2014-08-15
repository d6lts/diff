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
 *   id = "image_field_diff_builder",
 *   label = @Translation("Image Field Diff"),
 *   field_types = {
 *     "text",
 *     "image"
 *   },
 * )
 */
class ImageFieldBuilder extends FieldDiffBuilderBase {

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
    $form['show_id'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show image ID'),
//      '#default_value' => $config->get('field_types.' . $field_type . '.' . 'show_id'),
    );
    $form['compare_alt_field'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Compare <em>Alt</em> field'),
//      '#default_value' => $config->get('field_types.' . $field_type . '.' . 'compare_alt_field'),
      '#description' => $this->t('This is only used if the "Enable <em>Alt</em> field" is checked in the instance settings.'),
    );
    $form['compare_title_field'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Compare <em>Title</em> field'),
//      '#default_value' => $config->get('field_types.' . $field_type . '.' . 'compare_title_field'),
      '#description' => $this->t('This is only used if the "Enable <em>Title</em> field" is checked in the instance settings.'),
    );
    $form['property_separator'] = array(
      '#type' => 'select',
      '#title' => $this->t('Property separator'),
//      '#default_value' => $config->get('field_types.' . $field_type . '.' . 'property_separator'),
      '#description' => $this->t('Provides the ability to show properties inline or across multiple lines.'),
      '#options' => array(
        ', ' => $this->t('Comma (,)'),
        '; ' => $this->t('Semicolon (;)'),
        ' ' => $this->t('Space'),
        'nl' => $this->t('New line'),
      ),
    );

    return parent::buildConfigurationForm($form, $form_state);

  }
}
