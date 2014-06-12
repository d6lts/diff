<?php

/**
 * @file
 * Contains \Drupal\diff\Diff\FieldDiffManager.
 */

namespace Drupal\diff\Diff;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemListInterface;


/**
 * Provides a field diff manager.
 *
 * Can be assigned any number of FieldDiffBuilderInterface objects by calling
 * the addBuilder() method. When build() is called it iterates over the objects
 * in priority order and uses the first one that returns TRUE from
 * FieldDiffBuilderInterface::applies() to build the array to be compared for
 * a field by using the given settings.
 *
 * @see \Drupal\Core\DependencyInjection\Compiler\TaggedHandlersPass
 */
class FieldDiffManager implements ChainFieldDiffBuilderInterface {

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Holds arrays of field diff builders, keyed by priority.
   *
   * @var array
   */
  protected $builders = array();

  /**
   * Holds the array of field diff builders sorted by priority.
   *
   * Set to NULL if the array needs to be re-calculated.
   *
   * @var array|null
   */
  protected $sortedBuilders;

  /**
   * Constructs a \Drupal\diff\Diff\FieldDiffManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function addBuilder(FieldDiffBuilderInterface $builder, $priority) {
    $this->builders[$priority][] = $builder;
    // Force the builders to be re-sorted.
    $this->sortedBuilders = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(array $attributes) {
    return TRUE;
  }

  /**
   * @todo make sure here that the service with greater priority is picked
   * instead of the service with lower priority (theoretically this should
   * happen but now it seems like it's the other way around.)
   * {@inheritdoc}
   */
  public function build(FieldItemListInterface $field_items, array $context) {
    $build = array();
    // Call the build method of registered field builders,
    // until one of them returns an array.
    foreach ($this->getSortedBuilders() as $builder) {
      if (!$builder->applies($context)) {
        // The builder does not apply, so we continue with the other builders.
        continue;
      }

      $build = $builder->build($field_items, $context);

    }
    // Allow modules to alter the field data to be compared.
    $this->moduleHandler->alter('field_diff_view', $build, $context);
    // Fall back to an empty field diff view.
    return $build;
  }

  /**
   * Returns the sorted array of field diff builders.
   *
   * @return array
   *   An array of field diff builder objects.
   */
  protected function getSortedBuilders() {
    if (!isset($this->sortedBuilders)) {
      // Sort the builders according to priority.
      krsort($this->builders);
      // Merge nested builders from $this->builders into $this->sortedBuilders.
      $this->sortedBuilders = array();
      foreach ($this->builders as $builders) {
        $this->sortedBuilders = array_merge($this->sortedBuilders, $builders);
      }
    }

    return $this->sortedBuilders;
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