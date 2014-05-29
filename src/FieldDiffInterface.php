<?php

/**
 * @file
 * Contains \Drupal\diff\FieldDiffInterface.
 */

namespace Drupal\diff;

interface FieldDiffInterface {

  /**
   * Returns the name of the module which defined the fields for which a
   * class implementing this interface provides diff support.
   * A class implementing this interface provides diff support only for
   * field types defined by the module who's name is returned by this method.
   *
   * E.g. if a class implementing this interface provides the following
   * implementation for getFieldProvider() method:
   *
   * public function getFieldProvider() {
   *   return 'text';
   * }
   *
   * it means that this class handles diff for fields defined by the text module.
   */
  public function getFieldProvider();

  /**
   * Parses text field comparative values.
   *
   * This method should return an array
   */
  public function view($items, $context);

  /**
   * Provide default options for field comparison.
   */
  public function defaultOptions($field_type);

  /**
   * Provide a form for setting the field comparison options.
   */
  public function optionsForm($field_type, $settings);

} 