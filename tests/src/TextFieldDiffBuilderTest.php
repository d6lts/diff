<?php

/**
 * @file
 * Contains Drupal\diff\Tests\TextFieldDiffBuilderTest
 */

namespace Drupal\diff\Tests;

use Drupal\Tests\UnitTestCase;
use Drupal\diff\TextFieldDiffBuilder;

/**
 * @ingroup diff
 * @group diff
 */
class TextFieldDiffBuilderTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Text Field Diff Unit Test',
      'description' => 'Test Text Field Diff builder function',
      'group' => 'Diff',
    );
  }

  /**
   *
   */
  public function testAdd() {
    $this->assertEquals(2 + 3, 5);
  }

}
