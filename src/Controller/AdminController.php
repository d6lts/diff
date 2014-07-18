<?php

/**
 * @file
 * Contains \Drupal\diff\Controller\AdminController.
 */

namespace Drupal\diff\Controller;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Controller\ControllerBase;


class AdminController extends ControllerBase {

  /**
   * The field type plugin manager manager service.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * Constructs a new AdminController object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   The Plugin manager service.
   */
  public function __construct(PluginManagerInterface $plugin_manager) {
    $this->fieldTypePluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.field.field_type')
    );
  }

  /**
   * Lists all the field types found on the system.
   */
  public function fieldTypesList() {
    $build['info'] = array(
      '#markup' => '<p>' . $this->t('This table provides a summary of the field type support found on the system. It is recommended that you use global settings whenever possible to configure field comparison settings.') . '</p>',
    );

    $header = array($this->t('Type'), $this->t('Provider'), $this->t('Operations'));
    $rows = array();
    // Load all field types which have UI.
    $field_types = $this->fieldTypePluginManager->getDefinitions();
    foreach ($field_types as $field_name => $field_type) {
      $row = array();
      $row[] = $this->t('@field_label (%field_type)', array(
        '@field_label' => $field_type['label'],
        '%field_type' => $field_name,
        )
      );
      $row[] = $field_type['provider'];
      $row[] = $this->l($this->t('Global settings'), 'diff.field_type_settings', array('field_type' => $field_name));
      $rows[] = $row;
    }

    $build['category_table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('The system has no configurable fields.'),
      '#attributes' => array('id' => array('diff-field-types-list-table')),
      '#attached' => array(
        'css' => array(
          drupal_get_path('module', 'diff') . '/css/diff.default.css',
        ),
      ),
    );

    return $build;
  }

}
