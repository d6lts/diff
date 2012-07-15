<?php

/**
 * @file
 * Administration page callbacks and forms.
 */

/**
 * General configuration form for controlling the diff behaviour.
 */
function diff_admin_settings($form, $form_state) {
  $form['diff_theme'] = array(
    '#type' => 'select',
    '#title' => t('CSS'),
    '#default_value' => variable_get('diff_theme', 'default'),
    '#options' => array(
      'default' => t('Classic'),
      'boxes' => t('Boxes'),
    ),
    '#empty_option' => t('None'),
  );
  $form['diff_script_revisioning'] = array(
    '#type' => 'select',
    '#title' => t('Revisioning JScript Options'),
    '#default_value' => variable_get('diff_script_revisioning', 'simple'),
    '#options' => array(
      'simple' => t('Simple exclusion'),
      'linear' => t('Linear restrictions'),
    ),
    '#empty_option' => t('None'),
    '#description' => t('<em>Simple exclusion</em> means that users will not be able to select the same revision, <em>Linear restrictions</em> means that users can only select older or newer revisions of the current selections.'),
  );

  $options = drupal_map_assoc(array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10));
  $form['diff_leading_context_lines'] = array(
    '#type' => 'select',
    '#title' => t('Leading context lines'),
    '#description' => t('This governs the number of unchanged leading context "lines" to preserve.'),
    '#default_value' => variable_get('diff_leading_context_lines', 2),
    '#options' => $options,
  );
  $form['diff_trailing_context_lines'] = array(
    '#type' => 'select',
    '#title' => t('Trailing context lines'),
    '#description' => t('This governs the number of unchanged trailing context "lines" to preserve.'),
    '#default_value' => variable_get('diff_trailing_context_lines', 2),
    '#options' => $options,
  );
  $form['diff_normalise_text'] = array(
    '#type' => 'checkbox',
    '#title' => t('Normalise text'),
    '#description' => t('Convert all Windows and Mac newlines to a single newline, so diff only need to deal with one newline type. Unix users should have no need to enable this option.'),
    '#default_value' => variable_get('diff_normalise_text', 0),
  );

  return system_settings_form($form);
}

/**
 * Global entity settings.
 */
function diff_admin_global_entity_settings($form, $form_state, $entity_type) {
  $form['diff_show_header_' . $entity_type] = array(
    '#type' => 'checkbox',
    '#title' => t('Show entity label header'),
		'#default_value' => variable_get('diff_show_header_' . $entity_type, 1),
  );
  $form['diff_admin_path_' . $entity_type] = array(
    '#type' => 'checkbox',
    '#title' => t('Treat diff pages as administrative'),
    '#description' => t('Diff pages are treated as administrative pages by default, although it is up to each module to enforce this and to implement this optional setting.'),
	  '#default_value' => variable_get('diff_admin_path_' . $entity_type, 1),
  );
  return system_settings_form($form);
}

/**
 * Menu callback - provides an overview of Diff support and global settings.
 */
function diff_admin_field_overview() {
  $build['info'] = array(
    '#markup' => t('<p>This table provides a summary of the field type support found on the system. It is recommended that you use global settings whenever possible to configure field comparison settings.</p>'),
  );

  $header = array(t('Type'), t('Module'), t('Operations'));
  $rows = array();

  // Skip field types which have no widget types.
  $field_types = field_info_field_types();
  $widgets = array();
  foreach (field_info_widget_types() as $name => $widget_type) {
    foreach ($widget_type['field types'] as $widget_field_type) {
      if (isset($field_types[$widget_field_type])) {
        $widgets[$widget_field_type][$name] = $widget_type['label'];
      }
    }
  }

  foreach ($field_types as $field_name => $field_type) {
    if (!empty($widgets[$field_name])) {
      $row = array();
      $row[] = t('@field_label (%field_type)', array(
        '@field_label' => $field_type['label'],
        '%field_type' => $field_name,
      ));
      $row[] = $field_type['module'];
      $row[] = l(t('Global settings'), 'admin/config/content/diff/fields/' . $field_name);
      $rows[] = $row;
    }
  }

  $build['category_table'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => t('The system has no configurable fields.'),
  );
  return $build;
}

/**
 * Menu form callback for the field settings.
 */
function diff_admin_global_field_settings($form, $form_state, $type) {
  module_load_include('diff.inc', 'diff');

  $field_types = field_info_field_types();
  if (!isset($field_types[$type])) {
    drupal_set_message(t('Invalid field type.'), 'error');
    drupal_goto('admin/config/content/diff/fields');
  }
  $field_type = $field_types[$type];

  // Set the title to give more context to this page.
  drupal_set_title(t('Global settings for %label fields', array(
    '%label' => $field_type['label'],
  )), PASS_THROUGH);

  $variable_name = "diff_{$field_type['module']}_field_{$type}_default_options";
  $settings = variable_get($variable_name, array());
  $settings = _diff_field_default_settings($field_type['module'], $type, $settings);
  $func = $field_type['module'] . '_field_diff_options_form';
  if (function_exists($func) && $options_form = $func($type, $settings)) {
    $form[$variable_name] = $options_form;
  }
  $form[$variable_name]['#tree'] = TRUE;

  diff_global_settings_form($form[$variable_name], $form_state, $type, $settings);
  return system_settings_form($form);
}
