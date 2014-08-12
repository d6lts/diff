<?php

/**
 * @file
 * Contains the revision overview form.
 */

namespace Drupal\diff\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Field\FieldDefinition;


/**
 * Provides a form for revision overview page.
 */
class Test extends FormBase {

  /**
   * Wrapper object for writing/reading simple configuration from diff.settings.yml
   */
  protected $config;

  /**
   * The field type plugin manager manager service.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $fieldTypePluginManager;

  protected $diffBuilderManager;

  /**
   * Constructs a RevisionOverviewForm object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   * @param \Drupal\Component\Plugin\PluginManagerInterface $diffBuilderManager
   */
  public function __construct(PluginManagerInterface $plugin_manager, PluginManagerInterface $diffBuilderManager) {
    $this->config = $this->config('diff.settings');
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
    return 'diff.test';
  }

  protected function buildFieldRow($field_name, $field_type, $diff_plugin_definitions, array $form, FormStateInterface $form_state) {
    $type = $this->t('@field_label (%field_type)', array(
        '@field_label' => $field_type['label'],
        '%field_type' => $field_name
      )
    );
    $plugin_options = array();
    if (isset($plugins[$field_name])) {
      foreach ($plugins[$field_name] as $id) {
        $plugin_options[] = $diff_plugin_definitions[$id]['label']->render();
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
      '#field_name' => $field_name,
    );
    $field_row = array(
      'field_type' => array(
        '#markup' => $type,
      ),
    );
    // @todo Here we need to get the default plugin from the config entity and create an instance of plugin.
//      // Check the currently selected plugin, and merge persisted values for its
//      // settings.
//      if (isset($form_state['values']['fields'][$field_name]['type'])) {
//        $display_options['type'] = $form_state['values']['fields'][$field_name]['type'];
//      }
//      if (isset($form_state['plugin_settings'][$field_name]['settings'])) {
//        $display_options['settings'] = $form_state['plugin_settings'][$field_name]['settings'];
//      }

    // @todo Here we need to create an instance of the plugin based on field definition and settings.
    // This needs to be replaced.
    $options['field_definition'] = FieldDefinition::create($field_name);
    $plugin = $this->diffBuilderManager->getInstance($options);

    if ($form_state['plugin_settings_edit'] == $field_name) {
      // We are currently editing this field's plugin settings. Display the
      // settings form and submit buttons.
      $field_row['plugin']['settings_edit_form'] = array(
        '#type' => 'container',
        '#attributes' => array('class' => array('field-plugin-settings-edit-form')),
        '#parents' => array('fields', $field_name, 'settings_edit_form'),
        'label' => array(
          '#markup' => $this->t('Plugin settings'),
        ),
        'settings' => $plugin->buildConfigurationForm(array(), $form_state),
        'actions' => array(
          '#type' => 'actions',
          'save_settings' => $base_button + array(
              '#type' => 'submit',
              '#button_type' => 'primary',
              '#name' => $field_name . '_plugin_settings_update',
              '#value' => $this->t('Update'),
              '#op' => 'update',
            ),
          'cancel_settings' => $base_button + array(
              '#type' => 'submit',
              '#name' => $field_name . '_plugin_settings_cancel',
              '#value' => $this->t('Cancel'),
              '#op' => 'cancel',
              // Do not check errors for the 'Cancel' button, but make sure we
              // get the value of the 'plugin type' select.
              '#limit_validation_errors' => array(array('fields', $field_name, 'type')),
            ),
        ),
      );
      $field_row['settings_edit'] = array(
        '#markup' => '',
      );
      $field_row['provider'] = array(
        '#markup' => '',
      );
      $field_row['#attributes']['class'][] = 'field-plugin-settings-editing';
    }
    else {
      $field_row['provider'] = array(
        '#markup' => $field_type['provider'],
      );
      $field_row['plugin'] = array(
        'type' => array(
          '#type' => 'select',
          '#options' => $plugin_options,
          '#title_display' => 'invisible',
          '#empty_option' => $this->t('- Hide -'),
          // @todo This needs to be taken form config entity.
//            '#default_value' => '',
        ),
        'settings_edit_form' => array(),
      );
      if ($plugin) {
        $field_row['settings_edit'] = $base_button + array(
            '#type' => 'image_button',
            '#name' => $field_name . '_settings_edit',
            '#src' => 'core/misc/configure-dark.png',
            '#attributes' => array('class' => array('field-plugin-settings-edit'), 'alt' => $this->t('Edit')),
            '#op' => 'edit',
            // Do not check errors for the 'Edit' button, but make sure we get
            // the value of the 'plugin type' select.
            '#limit_validation_errors' => array(array('fields', $field_name, 'type')),
            '#prefix' => '<div class="field-plugin-settings-edit-wrapper">',
            '#suffix' => '</div>',
          );
      }
    }

    return $field_row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['fields'] = array(
      '#type' => 'table',
      '#tree' => TRUE,
      '#header' => $this->getTableHeader(),
      '#empty' => $this->t('No configurable fields found.'),
      '#prefix' => '<div id="field-display-overview-wrapper">',
      '#suffix' => '</div>',
      '#attributes' => array(
        'class' => array('field-ui-overview'),
        'id' => 'field-display-overview',
      ),
    );

    $diff_plugin_definitions = $this->diffBuilderManager->getDefinitions();
    $plugins = array();
    foreach ($diff_plugin_definitions as $plugin_definition) {
      if (isset($plugin_definition['field_types'])) {
        foreach ($plugin_definition['field_types'] as $id) {
          $plugins[$id][] = $plugin_definition['id'];
        }
      }
    }
    $field_definitions = $this->fieldTypePluginManager->getDefinitions();
    foreach ($field_definitions as $field_name => $field_type) {
      $form['fields'][$field_name] = $this->buildFieldRow($field_name, $field_type, $diff_plugin_definitions, $form, $form_state);
    }

    $form['#attached']['library'][] = 'field_ui/drupal.field_ui';
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Returns the header for the table.
   */
  protected function getTableHeader() {
    return array(
      'field_type' => $this->t('Field Type'),
      'provider' => $this->t('Provider'),
      'plugin' => $this->t('Plugin'),
      'settings_edit' => $this->t(''),
    );
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
  }

  /**
   * Ajax handler for multi-step buttons.
   */
  public function multistepAjax($form, FormStateInterface $form_state) {
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
        $updated_columns = array('provider', 'plugin', 'settings_edit');
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

}
