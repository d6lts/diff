<?php

/**
 * @file
 * Contains \Drupal\diff\Diff\FieldDiffManager.
 */

namespace Drupal\diff\Diff;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Field\FieldDefinitionInterface;


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
   * Form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

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
   * @param FormBuilderInterface $form_builder
   *   Form builder service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, FormBuilderInterface $form_builder) {
    $this->moduleHandler = $module_handler;
    $this->formBuilder = $form_builder;
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
  public function applies(FieldDefinitionInterface $field_definition) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(FieldItemListInterface $field_items, array $context) {
    $build = array();
    // Call the build method of registered field builders,
    // until one of them returns an array.
    foreach ($this->getSortedBuilders() as $builder) {
      if (!$builder->applies($field_items->getFieldDefinition())) {
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
      ksort($this->builders);
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
  public function getSettingsForm($field_type) {
    $form = NULL;
    // Call the getSettingsForm method of registered builders,
    // until one of them returns a renderable form array.
    foreach ($this->getSortedBuilders() as $builder) {
      if (!$builder->applies(array('field_type' => $field_type))) {
        // The builder does not apply, so we continue with the other builders.
        continue;
      }

      $form = $builder->getSettingsForm($field_type);
    }

    // @todo make sure that for field types that doesn't exist we don't return the base settings form but 404.
    // If no service applies return the default settings form.
    if ($form == NULL) {
      return $this->formBuilder->getForm('Drupal\diff\Form\DiffFieldBaseSettingsForm', $field_type);
    }
    else {
      return $form;
    }
  }

}
