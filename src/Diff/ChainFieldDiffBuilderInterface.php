<?php

/**
 * @file
 * Contains \Drupal\diff\Diff\ChainFieldDiffBuilderInterface.
 */

namespace Drupal\diff\Diff;

/**
 * Defines an interface, a chained service that decides which field properties
 * should be compared and provides field type specific diff settings form.
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
