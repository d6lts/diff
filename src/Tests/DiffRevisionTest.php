<?php

/**
 * @ingroup diff
 */

namespace Drupal\diff\Tests;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the diff revisions overview.
 *
 * @group diff
 */
class DiffRevisionTest extends DiffTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'diff_test',
    'content_translation',
    'field_ui'
  ];

  /**
   * Tests the revision diff overview.
   */
  public function testRevisionDiffOverview() {
    // Login as admin with the required permission.
    $this->loginAsAdmin(['delete any article content']);

    // Create an article.
    $title = 'test_title';
    $edit = array(
      'title[0][value]' => $title,
      'body[0][value]' => '<p>Revision 1</p>',
      'revision' => TRUE,
    );
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));
    $node = $this->drupalGetNodeByTitle($title);
    $created = $node->getCreatedTime();
    $this->drupalGet('node/' . $node->id());

    // Make sure the revision tab doesn't exist.
    $this->assertNoLink('Revisions');

    // Create a second revision, with a comment.
    $edit = array(
      'body[0][value]' => '<p>Revision 2</p>',
      'revision' => TRUE,
      'revision_log[0][value]' => 'Revision 2 comment'
    );
    $this->drupalGet('node/add/article');
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));
    $this->drupalGet('node/' . $node->id());

    // Check the revisions overview.
    $this->clickLink(t('Revisions'));
    $rows = $this->xpath('//tbody/tr');
    // Make sure only two revisions available.
    $this->assertEqual(count($rows), 2);

    // Compare the revisions in standard mode.
    $this->drupalPostForm(NULL, NULL, t('Compare'));
    $this->clickLink('Standard');
    // Extract the changes.
    $this->assertText('Changes to Body');
    $rows = $this->xpath('//tbody/tr');
    $head = $this->xpath('//thead/tr');
    $diff_row = $rows[3]->td;
    $comment = $head[0]->th[3];
    // Assert the revision comment.
    $this->assertEqual((string) $comment, 'Revision 2 comment');
    // Assert changes made to the body, text 1 changed to 2.
    $this->assertEqual((string) ($diff_row[0]), '-');
    $this->assertEqual((string) (($diff_row[1]->span)), '1');
    $this->assertEqual(htmlspecialchars_decode(strip_tags($diff_row[1]->asXML())), '<p>Revision 1</p>');
    $this->assertEqual((string) (($diff_row[2])), '+');
    $this->assertEqual((string) (($diff_row[3]->span)), '2');
    $this->assertEqual(htmlspecialchars_decode((strip_tags($diff_row[3]->asXML()))), '<p>Revision 2</p>');

    // Compare the revisions in markdown mode.
    $this->clickLink('Markdown');
    $rows = $this->xpath('//tbody/tr');
    $diff_row = $rows[3]->td;
    // Assert changes made to the body, text 1 changed to 2.
    $this->assertEqual((string) ($diff_row[0]), '-');
    $this->assertEqual((string) (($diff_row[1]->span)), '1');
    $this->assertEqual(htmlspecialchars_decode(strip_tags($diff_row[1]->asXML())), 'Revision 1');
    $this->assertEqual((string) (($diff_row[2])), '+');
    $this->assertEqual((string) (($diff_row[3]->span)), '2');
    $this->assertEqual(htmlspecialchars_decode((strip_tags($diff_row[3]->asXML()))), 'Revision 2');

    // Go back to revision overview.
    $this->clickLink(t('Back to Revision Overview'));
    // Revert the revision, confirm.
    $this->clickLink(t('Revert'));
    $this->drupalPostForm(NULL, NULL, t('Revert'));
    $this->assertText(t('Article @title has been reverted to the revision from @revision-date.', array(
      '@revision-date' => format_date($created),
      '@title' => $title
    )));

    // Make sure three revisions are available.
    $rows = $this->xpath('//tbody/tr');
    $this->assertEqual(count($rows), 3);
    // Make sure the reverted comment is there.
    $this->assertText(t('Copy of the revision from @date', array('@date' => date('D, m/d/Y - H:i', $created))));

    // Delete the first revision (last entry in table).
    $this->clickLink(t('Delete'), 0);
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $this->assertText(t('Revision from @date of Article @title has been deleted.', array(
      '@date' => date('D, m/d/Y - H:i', $created),
      '@title' => $title
    )));

    // Make sure two revisions are available.
    $rows = $this->xpath('//tbody/tr');
    $this->assertEqual(count($rows), 2);

    // Delete one revision so that we are left with only 1 revision.
    $this->clickLink(t('Delete'), 0);
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $this->assertText(t('Revision from @date of Article @title has been deleted.', array(
        '@date' => date('D, m/d/Y - H:i', $created),
        '@title' => $title
    )));

    // Make sure we only have 1 revision now.
    $rows = $this->xpath('//tbody/tr');
    $this->assertEqual(count($rows), 1);

    // Assert that there are no radio buttons for revision selection.
    $this->assertNoFieldByXPath('//input[@type="radio"]');
    // Assert that there is no submit button.
    $this->assertNoFieldByXPath('//input[@type="submit"]');
  }

  public function testOverviewPager() {
    $config = \Drupal::configFactory()->getEditable('diff.settings');
    $config->set('general_settings.revision_pager_limit', 10)->save();
    $admin_user = $this->drupalCreateUser(['view article revisions']);
    $this->drupalLogin($admin_user);
    $node = $this->drupalCreateNode([
      'type' => 'article',
    ]);
    // Create 50 more revisions in order to trigger paging on the revisions
    // overview screen.
    for ($i = 0; $i < 15; $i++) {
      $node->setNewRevision(TRUE);
      $node->save();
    }

    // Check the number of elements on the first page.
    $this->drupalGet('node/' . $node->id() . '/revisions');
    $element = $this->xpath('//*[@id="edit-node-revisions-table"]/tbody/tr');
    $this->assertEqual(count($element), 10);
    // Check that the pager exists.
    $this->assertRaw('page=1');

    $this->clickLinkPartialName('Next page');
    // Check the number of elements on the second page.
    $element = $this->xpath('//*[@id="edit-node-revisions-table"]/tbody/tr');
    $this->assertEqual(count($element), 6);
    $this->assertRaw('page=0');
    $this->clickLinkPartialName('Previous page');
  }

  /**
   * Tests the revisions overview error messages.
   */
  public function testRevisionOverviewErrorMessages() {
    // Enable some languages for this test.
    $language = ConfigurableLanguage::createFromLangcode('de');
    $language->save();

    // Login as admin with the required permissions.
    $this->loginAsAdmin([
      'administer node form display',
      'administer languages',
      'administer content translation',
      'create content translations',
      'translate any entity',
    ]);

    // Make article content translatable.
    $edit = [
      'entity_types[node]' => TRUE,
      'settings[node][article][translatable]' => TRUE,
      'settings[node][article][settings][language][language_alterable]' => TRUE,
    ];
    $this->drupalPostForm('admin/config/regional/content-language', $edit, t('Save configuration'));

    // Create an article.
    $title = 'test_title';
    $edit = [
      'title[0][value]' => $title,
      'body[0][value]' => '<p>Revision 1</p>',
      'revision' => TRUE,
    ];
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));
    $node = $this->drupalGetNodeByTitle($title);

    // Create a revision, changing the node language to German.
    $edit = [
      'langcode[0][value]' => 'de',
      'body[0][value]' => '<p>Revision 2</p>',
      'revision' => TRUE,
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));

    // Check the revisions overview, ensure only one revisions is available.
    $this->clickLink(t('Revisions'));
    $rows = $this->xpath('//tbody/tr');
    $this->assertEqual(count($rows), 1);

    // Compare the revisions and assert the first error message.
    $this->drupalPostForm(NULL, NULL, t('Compare'));
    $this->assertText('Multiple revisions are needed for comparison.');

    // Create another revision, changing the node language back to English.
    $edit = [
      'langcode[0][value]' => 'en',
      'body[0][value]' => '<p>Revision 3</p>',
      'revision' => TRUE,
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));

    // Check the revisions overview, ensure two revisions are available.
    $this->clickLink(t('Revisions'));
    $rows = $this->xpath('//tbody/tr');
    $this->assertEqual(count($rows), 2);
    $this->assertNoFieldChecked('edit-node-revisions-table-0-select-column-one');
    $this->assertFieldChecked('edit-node-revisions-table-0-select-column-two');
    $this->assertNoFieldChecked('edit-node-revisions-table-1-select-column-one');
    $this->assertNoFieldChecked('edit-node-revisions-table-1-select-column-two');

    // Compare the revisions and assert the second error message.
    $this->drupalPostForm(NULL, NULL, t('Compare'));
    $this->assertText('Select two revisions to compare.');

    // Check the same revisions twice and compare.
    $edit = [
      'radios_left' => 3,
      'radios_right' => 3,
    ];
    $this->drupalPostForm('/node/' . $node->id() . '/revisions', $edit, 'Compare');
    // Assert the third error message.
    $this->assertText('Select different revisions to compare.');

    // Check different revisions and compare. This time should work correctly.
    $edit = [
      'radios_left' => 3,
      'radios_right' => 1,
    ];
    $this->drupalPostForm('/node/' . $node->id() . '/revisions', $edit, 'Compare');
    $this->assertLinkByHref('node/1/revisions/view/1/3');
  }

}
