<?php

/**
 * @file
 * Contains \Drupal\diff\Form\FieldTypesSettingsForm.
 *
 * This form lists all the field types from the system and for every field type
 * it provides a select having as options all the FieldDiffBuilder plugins that
 * support that field type.
 */

namespace Drupal\diff\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Form\FormState;

/**
 * Provides a form for revision overview page.
 */
class FieldTypesSettingsForm extends FormBase {

  /**
   * Wrapper object for writing/reading simple configuration from diff.plugins.yml
   */
  protected $config;

  /**
   * The field type plugin manager service.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * The field diff plugin manager service.
   *
   * @var \Drupal\diff\DiffBuilderManager
   */
  protected $diffBuilderManager;

  /**
   * Constructs a FieldTypesListSettingsForm object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   * @param \Drupal\Component\Plugin\PluginManagerInterface $diffBuilderManager
   */
  public function __construct(PluginManagerInterface $plugin_manager, PluginManagerInterface $diffBuilderManager) {
    $this->config = $this->config('diff.plugins');
    $this->fieldTypePluginManager = $plugin_manager;
    $this->diffBuilderManager = $diffBuilderManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.field.field_type'),
      $container->get('plugin.manager.diff.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'diff.admin.plugins';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // The table containing all the field types discovered in the system.
    $form['fields'] = array(
      '#type' => 'table',
      '#tree' => TRUE,
      '#header' => $this->getTableHeader(),
      '#empty' => $this->t('No field types found.'),
      '#prefix' => '<div id="field-display-overview-wrapper">',
      '#suffix' => '</div>',
      '#attributes' => array(
        'class' => array('field-ui-overview'),
        'id' => 'field-display-overview',
      ),
    );

    // Get the definition of all @FieldDiffBuilder plugins.
    $diff_plugin_definitions = $this->diffBuilderManager->getDefinitions();
    $plugins = array();
    foreach ($diff_plugin_definitions as $plugin_definition) {
      if (isset($plugin_definition['field_types'])) {
        // Iterate through all the field types this plugin supports
        // and for every such field type add the id of the plugin.
        foreach ($plugin_definition['field_types'] as $id) {
          $plugins[$id][] = $plugin_definition['id'];
        }
      }
    }
    // Get all the field type plugins.
    $field_definitions = $this->fieldTypePluginManager->getDefinitions();
    foreach ($field_definitions as $field_id => $field_type) {
      // Build a row in the table for every field type.
      $form['fields'][$field_id] = $this->buildFieldRow($field_id, $field_type, $plugins, $diff_plugin_definitions, $form_state);
    }

    // Submit button for the form.
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Save'),
    );

    $form['#attached']['library'][] = 'field_ui/drupal.field_ui';

    return $form;
  }

  /**
   * Builds a row for the table. Each row corresponds to a field type.
   *
   * @param $field_id
   *   ID of the field type.
   * @param $field_type
   *   Metadata about the field type.
   * @param $plugins
   *   An array of field types and the associated field diff builder plugins ids.
   * @param $diff_plugin_definitions
   *   Definitions of all field diff builder plugins.
   * @param FormStateInterface $form_state
   *   THe form state object.
   *
   * @return array
   *   A table row for the field type listing table.
   */
  protected function buildFieldRow($field_id, $field_type, $plugins, $diff_plugin_definitions, FormStateInterface $form_state) {
    $display_options = $this->config->get($field_id);
    $field_type_label = $this->t('@field_label (%field_type)', array(
        '@field_label' => $field_type['label'],
        '%field_type' => $field_id,
      )
    );
    // Build a list of all the plugins for this field type.
    $plugin_options = array();
    if (isset($plugins[$field_id])) {
      foreach ($plugins[$field_id] as $id) {
        $plugin_options[$id] = $diff_plugin_definitions[$id]['label']->render();
      }
    }
    // Base button element for the various plugin settings actions.
    $base_button = array(
      '#submit' => array(array($this, 'multistepSubmit')),
      '#ajax' => array(
        'callback' => array($this, 'multistepAjax'),
        'wrapper' => 'field-display-overview-wrapper',
        'effect' => 'fade',
      ),
      '#field_name' => $field_id,
    );
    $field_row['field_type_label'] = array(
        '#markup' => $field_type_label,
    );
    // Check the currently selected plugin, and merge persisted values for its
    // settings.
    if (isset($form_state['values']['fields'][$field_id]['plugin']['type'])) {
      $display_options['type'] = $form_state['values']['fields'][$field_id]['plugin']['type'];
    }
    if (isset($form_state['plugin_settings'][$field_id]['settings'])) {
      $display_options['settings'] = $form_state['plugin_settings'][$field_id]['settings'];
    }
    $field_row['plugin'] = array(
      'type' => array(
        '#type' => 'select',
        '#options' => $plugin_options,
        '#title_display' => 'invisible',
        '#attributes' => array(
          'class' => array('field-plugin-type'),
        ),
        '#empty_option' => array('hidden' => $this->t('- Don\'t compare -')),
        '#default_value' => $display_options ? $display_options['type'] : 'hidden',
        '#ajax' => array(
          'callback' => array($this, 'multistepAjax'),
          'method' => 'replace',
          'wrapper' => 'field-display-overview-wrapper',
          'effect' => 'fade',
        ),
        '#field_name' => $field_id,
      ),
      'settings_edit_form' => array(),
    );

    $plugin = $this->getPlugin($display_options);

    // We are currently editing this field's plugin settings. Display the
    // settings form and submit buttons.
    if ($form_state['plugin_settings_edit'] == $field_id) {
      $field_row['plugin']['settings_edit_form'] = array(
        '#type' => 'container',
        '#attributes' => array('class' => array('field-plugin-settings-edit-form')),
        '#parents' => array('fields', $field_id, 'settings_edit_form'),
        'label' => array(
          '#markup' => $this->t('Plugin settings:' . ' <span class="plugin-name">' . $plugin_options[$display_options['type']] . '</span>'),
        ),
        'settings' => $plugin->buildConfigurationForm(array(), $form_state),
        'actions' => array(
          '#type' => 'actions',
          'save_settings' => $base_button + array(
              '#type' => 'submit',
              '#button_type' => 'primary',
              '#name' => $field_id . '_plugin_settings_update',
              '#value' => $this->t('Update'),
              '#op' => 'update',
            ),
          'cancel_settings' => $base_button + array(
              '#type' => 'submit',
              '#name' => $field_id . '_plugin_settings_cancel',
              '#value' => $this->t('Cancel'),
              '#op' => 'cancel',
              // Do not check errors for the 'Cancel' button, but make sure we
              // get the value of the 'plugin type' select.
              '#limit_validation_errors' => array(),
            ),
        ),
      );
      $field_row['settings_edit'] = array();
      $field_row['#attributes']['class'][] = 'field-plugin-settings-editing';
    }
    else {
      $field_row['settings_edit'] = array();
      if ($plugin) {
        $field_row['settings_edit'] = $base_button + array(
            '#type' => 'image_button',
            '#name' => $field_id . '_settings_edit',
            '#src' => 'core/misc/configure-dark.png',
            '#attributes' => array('class' => array('field-plugin-settings-edit'), 'alt' => $this->t('Edit')),
            '#op' => 'edit',
            // Do not check errors for the 'Edit' button, but make sure we get
            // the value of the 'plugin type' select.
//            '#limit_validation_errors' => array(array('fields', $field_id, 'type')),
            '#prefix' => '<div class="field-plugin-settings-edit-wrapper">',
            '#suffix' => '</div>',
        );
      }
    }

    return $field_row;
  }

  /**
   * Form submission handler for multi-step buttons.
   */
  public function multistepSubmit($form, FormStateInterface $form_state) {
    $trigger = $form_state['triggering_element'];
    $op = $trigger['#op'];

    switch ($op) {
      case 'edit':
        // Store the field whose settings are currently being edited.
        $field_name = $trigger['#field_name'];
        $form_state['plugin_settings_edit'] = $field_name;
        break;

      case 'update':
        $form_state['nimic'] = $form_state;
        // Store the saved settings, and set the field back to 'non edit' mode.
        $field_name = $trigger['#field_name'];
        if (isset($form_state['values']['fields'][$field_name]['settings_edit_form']['settings'])) {
          $form_state['plugin_settings'][$field_name]['settings'] = $form_state['values']['fields'][$field_name]['settings_edit_form']['settings'];
        }
        unset($form_state['plugin_settings_edit']);
        break;

      case 'cancel':
        // Set the field back to 'non edit' mode.
        unset($form_state['plugin_settings_edit']);
        break;
    }

    $form_state['rebuild'] = TRUE;
//    $form_state->set('rebuild', TRUE);

  }

  /**
   * Ajax handler for multi-step buttons.
   */
  public function multistepAjax(array $form, FormStateInterface $form_state) {
    $trigger = $form_state['triggering_element'];
    $op = $trigger['#op'];

    // Pick the elements that need to receive the ajax-new-content effect.
    switch ($op) {
      case 'edit':
        $updated_rows = array($trigger['#field_name']);
        $updated_columns = array('plugin');
        break;

      case 'update':
      case 'cancel':
        $updated_rows = array($trigger['#field_name']);
        $updated_columns = array('plugin', 'settings_edit');
        break;
    }

    foreach ($updated_rows as $name) {
      foreach ($updated_columns as $key) {
        $element = &$form['fields'][$name][$key];
        $element['#prefix'] = '<div class="ajax-new-content">' . (isset($element['#prefix']) ? $element['#prefix'] : '');
        $element['#suffix'] = (isset($element['#suffix']) ? $element['#suffix'] : '') . '</div>';
      }
    }

    // Return the whole table.
    return $form['fields'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
//    $field_types = $form_state['values']['fields'];
//
//    foreach ($field_types as $field_type => $field_type_values) {
//      // If there is no plugin selected erase all configuration.
//      if ($field_type_values['plugin']['type'] == 'hidden') {
//        $this->config->clear('field_types.' . $field_type);
//      }
//      else {
//        // Get plugin settings. They lie either directly in submitted form
//        // values (if the whole form was submitted while some plugin settings
//        // were being edited), or have been persisted in $form_state.
//        $settings = array();
//        $key = NULL;
//        // Form submitted without pressing update button on plugin settings form.
//        if (isset($field_type_values['settings_edit_form']['settings'])) {
//          $settings = $field_type_values['settings_edit_form']['settings'];
//          $key = 1;
//        }
//        // Form submitted after settings were updated.
//        elseif (isset($form_state['plugin_settings'][$field_type]['settings'])) {
//          $settings = $form_state['plugin_settings'][$field_type]['settings'];
//          $key = 2;
//        }
//        if (!empty($settings)) {
//          $state = new FormState(array(
//            'values' => $settings,
//            'field_type' => $field_type,
//          ));
//          $plugin = $this->diffBuilderManager->createInstance($field_type_values['plugin']['type'], array());
//          $plugin->validateConfigurationForm($form, $state);
//          if ($key == 1) {
//            $field_type_values['settings_edit_form']['settings'] = $state['values'];
//          }
//          elseif ($key == 2) {
//            $form_state['plugin_settings'][$field_type]['settings'] = $state['values'];
//          }
//        }
//      }
//    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $field_types = $form_state['values']['fields'];
    foreach ($field_types as $field_type => $field_type_values) {
      // If there is no plugin selected erase all configuration.
      if ($field_type_values['plugin']['type'] == 'hidden') {
        $this->config->clear($field_type);
      }
      else {
        // Get plugin settings. They lie either directly in submitted form
        // values (if the whole form was submitted while some plugin settings
        // were being edited), or have been persisted in $form_state.
        $plugin = $this->diffBuilderManager->createInstance($field_type_values['plugin']['type']);
        // Form submitted without pressing update button on plugin settings form.
        if (isset($field_type_values['settings_edit_form']['settings'])) {
          $settings = $field_type_values['settings_edit_form']['settings'];
        }
        // Form submitted after settings were updated.
        elseif (isset($form_state['plugin_settings'][$field_type]['settings'])) {
          $settings = $form_state['plugin_settings'][$field_type]['settings'];
        }
        // If the settings are not set anywhere in the form state just save the
        // default configuration for the current plugin.
        else {
          $settings = $plugin->defaultConfiguration();
        }
        // Build a FormState object and call the plugin submit handler.
        $state = new FormState(array(
          'values' => $settings,
          'field_type' => $field_type,
        ));
        $plugin->submitConfigurationForm($form, $state);
      }
    }
//    $this->config->save();

    drupal_set_message($this->t('Your settings have been saved.'));
  }

  /**
   * Returns a plugin object or NULL based on configuration.
   */
  protected function getPlugin($configuration) {
    $plugin = NULL;

    if ($configuration && isset($configuration['type']) && $configuration['type'] != 'hidden') {
      if (!isset($configuration['settings'])) {
        $configuration['settings'] = array();
      }
      $plugin = $this->diffBuilderManager->createInstance(
        $configuration['type'], $configuration['settings']
      );
    }

    return $plugin;
  }



  /**
   * Returns the header for the table.
   */
  protected function getTableHeader() {
    return array(
      'field_type' => $this->t('Field Type'),
      'plugin' => $this->t('Plugin'),
      'settings_edit' => $this->t(''),
    );
  }

}
