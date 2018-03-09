<?php

namespace Drupal\diff\Tests;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests the revision overview with content moderation enabled.
 *
 * @group diff
 */
class DiffRevisionContentModerationTest extends DiffRevisionTest {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['content_moderation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Enable moderation on articles.
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = Workflow::load('editorial');
    /** @var \Drupal\content_moderation\Plugin\WorkflowType\ContentModeration $plugin */
    $plugin = $workflow->getTypePlugin();
    $plugin->addEntityTypeAndBundle('node', 'article');
    $workflow->save();

    // Add necessary admin permissions for moderated content.
    $this->adminPermissions = array_merge([
      'use editorial transition create_new_draft',
      'use editorial transition publish',
      'use editorial transition archive',
      'use editorial transition archived_draft',
      'use editorial transition archived_published',
      'view latest version',
      'view any unpublished content',
    ], $this->adminPermissions);
  }

  /**
   * {@inheritdoc}
   *
   * Override form submission to work with content moderation.
   */
  protected function drupalPostNodeForm($path, array $edit, $submit) {
    // New revisions are automatically enabled, so remove the manual value.
    unset($edit['revision']);
    parent::drupalPostNodeForm($path, $edit, $submit);
  }

  /**
   * {@inheritdoc}
   */
  public function testAll() {
    // Ensure revision tab still works as expected.
    parent::testAll();

    // Specifically test for content moderation functionality.
    $this->doTestContentModeration();
  }

  /**
   * Test content moderation integration.
   */
  protected function doTestContentModeration() {
    $title = $this->randomString();
    $node = $this->createNode([
      'type' => 'article',
      'title' => $title,
    ]);

    // Add another draft.
    $node->title = $title . ' change 1';
    $node->save();

    // Publish.
    $node->moderation_state = 'published';
    $node->save();

    // Another draft.
    $node->title = $title . ' change 2';
    $node->moderation_state = 'draft';
    $node->save();

    // Verify moderation state information appears on revision overview.
    $this->drupalGet($node->toUrl('version-history'));

    // Verify proper moderation states are displayed.
    $diff_rows = $this->xpath('//tbody/tr/td[1]/p');
    $this->assertEqual('Changes on: Title (Draft)', (string) $diff_rows[0]);
    $this->assertEqual('No changes. (Published)', (string) $diff_rows[1]);
    $this->assertEqual('Changes on: Title (Draft)', (string) $diff_rows[2]);
    $this->assertEqual('Initial revision. (Draft)', (string) $diff_rows[3]);
  }

}
