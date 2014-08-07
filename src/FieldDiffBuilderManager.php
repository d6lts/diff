<?php

/**
 * @file
 * Contains \Drupal\diff\FieldDiffBuilderManager.
 */

namespace Drupal\diff;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Field\FieldTypePluginManagerInterface;

/**
 * Plugin type manager for field diff builders.
 *
 * @ingroup field_diff_builder
 */
class FieldDiffBuilderManager extends DefaultPluginManager {

  /**
   * Constructs a FieldDiffBuilderManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The 'field type' plugin manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, FieldTypePluginManagerInterface $field_type_manager) {
    parent::__construct('Plugin/Field/DiffBuilder', $namespaces, $module_handler, 'Drupal\diff\Annotation\FieldDiffBuilder');
    $this->setCacheBackend($cache_backend, 'field_diff_builder_plugins');
    $this->alterInfo('field_diff_builder_info');
  }

}
