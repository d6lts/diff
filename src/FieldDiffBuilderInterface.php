<?php

/**
 * @file
 * Contains \Drupal\diff\FieldDiffBuilderInterface.
 */

namespace Drupal\diff;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

interface FieldDiffBuilderInterface extends PluginFormInterface, ConfigurablePluginInterface {

  /**
   * Builds an array of strings to be compared by the Diff component.
   *
   * @param FieldItemListInterface $field_items
   *   Represents an entity field; that is a list of field item objects.
   *
   * @return mixed
   *   An array of strings to be compared. If an empty array is returned it
   *   means that a field is either empty or no properties need to be compared
   *   for that field.
   */
  public function build(FieldItemListInterface $field_items);

}