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

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['fields'] = array(
      '#type' => 'table',
      '#tree' => TRUE,
      '#header' => $this->getTableHeader(),
      '#empty' => $this->t('No configurable fields found.'),
      '#attributes' => array('id' => array('diff-field-types-list-table')),
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
      $form['fields'][$field_name] = array(
        'field_type' => array(
          '#markup' => $type,
        ),
        'provider' => array(
          '#markup' => $field_type['provider'],
        ),
        'plugin' => array(
          'type' => array(
            '#type' => 'select',
            '#options' => $plugin_options,
            '#title_display' => 'invisible',
            '#empty_option' => $this->t('- Hide -'),
          ),
          'settings_edit_form' => array(),
        ),
        'settings_edit' => $base_button + array(
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
        ),
      );

      $options['field_definition'] = FieldDefinition::create($field_name);
      $plugin = $this->diffBuilderManager->getInstance($options);
//      if ($field_name == 'text_with_summary') {
//        dsm($plugin);
//        dsm($form_state);
//        $form['plugin'] = $plugin->buildConfigurationForm($form, $form_state);
//      }
      if ($form_state['plugin_settings_edit'] == $field_name) {
        // We are currently editing this field's plugin settings. Display the
        // settings form and submit buttons.
        $form['fields'][$field_name]['plugin']['settings_edit_form'] = array();

        $form['fields'][$field_name]['plugin']['#cell_attributes'] = array('colspan' => 3);
        $form['fields'][$field_name]['plugin']['settings_edit_form'] = array(
          '#type' => 'container',
          '#attributes' => array('class' => array('field-plugin-settings-edit-form')),
          '#parents' => array('fields', $field_name, 'settings_edit_form'),
          'label' => array(
            '#markup' => $this->t('Plugin settings'),
          ),
          'settings' => $plugin->buildConfigurationForm($form, $form_state),
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
        $form['fields'][$field_name]['#attributes']['class'][] = 'field-plugin-settings-editing';
      }
    }

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
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  protected function getTableHeader() {
    return array(
      'field_type' => $this->t('Field Type'),
      'provider' => $this->t('Provider'),
      'plugin' => $this->t('Plugin'),
      'settings_edit' => $this->t(''),
    );
  }

  /**
   * Form submission handler for multistep buttons.
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
        if (isset($form_state['values']['fields'][$field_name]['settings_edit_form']['third_party_settings'])) {
          $form_state['plugin_settings'][$field_name]['third_party_settings'] = $form_state['values']['fields'][$field_name]['settings_edit_form']['third_party_settings'];
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
   * Ajax handler for multistep buttons.
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
        $updated_columns = array('plugin', 'settings_summary', 'settings_edit');
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
