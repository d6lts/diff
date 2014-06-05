<?php

/**
 * @file
 * Contains \Drupal\diff\Diff\ChainFieldDiffBuilderInterface.
 */

namespace Drupal\diff\Diff;

/**
 * Defines an interface, a chained service that builds the array of strings
 * to be compared for a certain field.
 */
interface ChainFieldDiffBuilderInterface extends FieldDiffBuilderInterface {

  /**
   * Adds another field diff builder.
   *
   * @param FieldDiffBuilderInterface $builder
   *  The field diff builder to add.
   * @param $priority
   *   Priority of the field diff builder.
   */
  public function addBuilder(FieldDiffBuilderInterface $builder, $priority);

}
