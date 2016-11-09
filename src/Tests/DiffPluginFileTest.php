<?php

namespace Drupal\diff\Tests;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field_ui\Tests\FieldUiTestTrait;

/**
 * Tests the Diff module entity plugins.
 *
 * @group diff
 */
class DiffPluginFileTest extends DiffPluginTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'file',
    'image',
    'field_ui',
  ];

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->fileSystem = \Drupal::service('file_system');

    // FieldUiTestTrait checks the breadcrumb when adding a field, so we need
    // to show the breadcrumb block.
    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * Tests the File plugin.
   *
   * @see \Drupal\diff\Plugin\diff\Field\FileFieldBuilder
   */
  public function testFilePlugin() {
    // Add file field to the article content type.
    $file_field_name = 'field_file';
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => $file_field_name,
      'entity_type' => 'node',
      'type' => 'file'
    ));
    $field_storage->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'field_storage' => $field_storage,
      'bundle' => 'article',
      'label' => 'File',
    ])->save();

    // Make the field visible in the form and desfault display.
    $this->viewDisplay->load('node.article.default')
      ->setComponent('test_field')
      ->setComponent($file_field_name)
      ->save();
    $this->formDisplay->load('node.article.default')
      ->setComponent('test_field', ['type' => 'entity_reference_autocomplete'])
      ->setComponent($file_field_name, ['type' => 'file_generic'])
      ->save();

    // Create an article.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Test article',
    ]);
    $revision1 = $node->getRevisionId();

    // Upload a file to the article.
    $test_files = $this->drupalGetTestFiles('text');
    $edit['files[field_file_0]'] = $this->fileSystem->realpath($test_files['0']->uri);
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, 'Upload');
    $edit['revision'] = TRUE;
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));
    $node = $this->drupalGetNodeByTitle('Test article', TRUE);
    $revision2 = $node->getRevisionId();

    // Replace the file by a different one.
    $this->drupalPostForm('node/' . $node->id() . '/edit', [], 'Remove');
    $this->drupalPostForm(NULL, ['revision' => FALSE], t('Save and keep published'));
    $edit['files[field_file_0]'] = $this->fileSystem->realpath($test_files['1']->uri);
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, 'Upload');
    $edit['revision'] = TRUE;
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));
    $node = $this->drupalGetNodeByTitle('Test article', TRUE);
    $revision3 = $node->getRevisionId();

    // Check differences between revisions.
    $this->clickLink(t('Revisions'));
    $edit = [
      'radios_left' => $revision1,
      'radios_right' => $revision3,
    ];
    $this->drupalPostForm(NULL, $edit, t('Compare'));
    $this->assertText('File');
    $this->assertText('File: text-1.txt');
    $this->assertText('File ID: 4');

    // Use the unified fields layout.
    $this->clickLink('Unified fields');
    $this->assertResponse(200);
    $this->assertText('File');
    $this->assertText('File: text-1.txt');
    $this->assertText('File ID: 4');
  }

  /**
   * Tests the Image plugin.
   *
   * @see \Drupal\diff\Plugin\diff\Field\ImageFieldBuilder
   */
  public function testImagePlugin() {
    // Add image field to the article content type.
    $image_field_name = 'field_image';
    FieldStorageConfig::create([
      'field_name' => $image_field_name,
      'entity_type' => 'node',
      'type' => 'image',
      'settings' => [],
      'cardinality' => 1,
    ])->save();

    $field_config = FieldConfig::create([
      'field_name' => $image_field_name,
      'label' => 'Image',
      'entity_type' => 'node',
      'bundle' => 'article',
      'required' => FALSE,
      'settings' => ['alt_field' => 1],
    ]);
    $field_config->save();

    $this->formDisplay->load('node.article.default')
      ->setComponent($image_field_name, [
        'type' => 'image_image',
        'settings' => [],
      ])
      ->save();

    $this->viewDisplay->load('node.article.default')
      ->setComponent($image_field_name, [
        'type' => 'image',
        'settings' => [],
      ])
      ->save();

    // Create an article.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Test article',
    ]);
    $revision1 = $node->getRevisionId();

    // Upload an image to the article.
    $test_files = $this->drupalGetTestFiles('image');
    $edit = ['files[field_image_0]' => $this->fileSystem->realpath($test_files['1']->uri)];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));
    $edit = [
      'field_image[0][alt]' => 'Image alt',
      'revision' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));
    $node = $this->drupalGetNodeByTitle('Test article', TRUE);
    $revision2 = $node->getRevisionId();

    // Replace the image by a different one.
    $this->drupalPostForm('node/' . $node->id() . '/edit', [], 'Remove');
    $this->drupalPostForm(NULL, ['revision' => FALSE], t('Save and keep published'));
    $edit = ['files[field_image_0]' => $this->fileSystem->realpath($test_files['1']->uri)];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));
    $edit = [
      'field_image[0][alt]' => 'Image alt updated',
      'revision' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));
    $node = $this->drupalGetNodeByTitle('Test article', TRUE);
    $revision3 = $node->getRevisionId();

    // Check differences between revisions.
    $this->clickLink(t('Revisions'));
    $edit = [
      'radios_left' => $revision1,
      'radios_right' => $revision3,
    ];
    $this->drupalPostForm(NULL, $edit, t('Compare'));
    $this->assertText('Image');
    $this->assertText('Image: image-test-transparent-indexed.gif');
    $this->assertText('File ID: 2');
  }

}
