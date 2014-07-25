<?php

/**
 * @file
 * Contains Drupal\diff\Tests\TextFieldDiffBuilderTest
 */

namespace Drupal\diff\Tests;

use Drupal\Tests\UnitTestCase;
use Drupal\diff\Diff\Entity\TextFieldBuilder;

/**
 * @ingroup diff
 * @group diff
 */
class TextFieldBuilderTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Text Field Diff Support Unit Test',
      'description' => 'Test Text Field Diff builder',
      'group' => 'diff',
    );
  }

  /**
   * Tests if TextFieldsDiffBuilder applies to a field type
   * provided by the core text module.
   */
  public function testApplicableFieldType() {
    $field_definition = $this->getMockBuilder('\Drupal\Core\Field\FieldDefinitionInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_definition->expects($this->once())
      ->method('getType')
      ->will($this->returnValue('text_with_summary'));

    $entity_manager = $this->getMockBuilder('\Drupal\Core\Entity\EntityManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $form_manager = $this->getMockBuilder('\Drupal\Core\Form\FormBuilderInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $builder = new TextFieldBuilder($entity_manager, $form_manager);

    $this->assertEquals(TRUE, $builder->applies($field_definition));
  }

}