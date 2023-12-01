<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\oe_showcase\Traits\AssertPathAccessTrait;
use Drupal\Tests\oe_showcase\Traits\UserTrait;
use Drupal\Tests\oe_showcase\Traits\WysiwygTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Class to test News content type on existing site tests.
 */
class NewsTest extends ShowcaseExistingSiteTestBase {

  use AssertPathAccessTrait;
  use MediaTypeCreationTrait;
  use TestFileCreationTrait;
  use UserTrait;
  use WysiwygTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create user.
    $user = $this->createUser([]);
    $this->drupalLogin($user);
  }

  /**
   * Test News type CRUD.
   */
  public function testNewsType(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Assert user without permission can't create news types.
    $this->assertPathsRequireRole([
      'admin/structure/taxonomy/manage/news_type/overview',
      'admin/structure/taxonomy/manage/news_type/add',
    ], 'editor');

    // Assert editors can create news types.
    $user = $this->createUser([]);
    $user->addRole('editor');
    $user->save();
    $this->drupalLogin($user);

    $this->drupalGet('admin/structure/taxonomy/manage/news_type/add');
    $page->fillField('Name', 'Term one');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Created new term Term one.');

    // Assert editors can edit news types.
    $this->drupalGet('admin/structure/taxonomy/manage/news_type/overview');
    $page->clickLink('Edit');
    $page->fillField('Name', 'Term changed');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Updated term Term changed.');

    // Assert editors can delete news types.
    $this->drupalGet('admin/structure/taxonomy/manage/news_type/overview');
    $page->clickLink('Delete');
    $assert_session->pageTextContains('Are you sure you want to delete the taxonomy term Term changed?');
    $page->pressButton('Delete');
    $assert_session->pageTextContains('Deleted term Term changed.');
  }

  /**
   * Check creation News content through the UI.
   */
  public function testCreateNews() {
    // Mark test content for deletion after the test has finished.
    $this->markEntityTypeForCleanup('node');
    $this->markEntityTypeForCleanup('media');
    $this->markEntityTypeForCleanup('file');
    $this->markEntityTypeForCleanup('taxonomy_term');

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Create a sample media entity to be embedded.
    $file = File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ]);
    $file->save();
    $media_image = Media::create([
      'bundle' => 'image',
      'name' => 'Starter Image test',
      'oe_media_image' => [
        [
          'target_id' => $file->id(),
          'alt' => 'Starter Image test alt',
          'title' => 'Starter Image test title',
        ],
      ],
    ]);
    $media_image->save();

    for ($i = 1; $i < 4; $i++) {
      Term::create([
        'vid' => 'news_type',
        'name' => "News type $i",
      ])->save();
    }

    // Assert editors don't have permissions to create News items.
    $this->drupalGet('node/add/oe_sc_news');
    $assert_session->pageTextContains('You are not authorized to access this page.');
    $assert_session->statusCodeEquals(403);

    // Create editor user.
    $user = $this->createUser([]);
    $user->addRole('editor');
    $user->save();
    $this->drupalLogin($user);

    // Assert that the content field is required.
    $this->drupalGet('node/add/oe_sc_news');
    $page->fillField('Title', 'Example title');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Content field is required.');

    // Assert that editors have access to the Simple/Rich text formats.
    $assert_session->pageTextNotContains('This field has been disabled because you do not have sufficient permissions to edit it.');
    $page->fillField('Title', 'Example title');
    $page->fillField('News types', 'News type 1');
    $page->pressButton('Add another item');
    $page->fillField('News types (value 2)', 'News type 2');
    $page->pressButton('Add another item');
    $page->fillField('News types (value 3)', 'News type 3');
    $field = $page->findField('Content');
    $this->assertEquals('rich_text', $this->getWysiwigTextFormat($field));
    $field->setValue('Example Content');
    $page->fillField('Introduction', 'Example Introduction');
    // Assert that publication date was filled with a default value.
    $publication_date = $page->find('css', 'input[name="oe_publication_date[0][value][date]"]');
    $this->assertMatchesRegularExpression("/\d+\-\d+\-\d+/", $publication_date->getValue());
    // Set a custom publication date.
    $page->fillField('Date', '2022-01-24');
    $media_name = $media_image->getName() . ' (' . $media_image->id() . ')';
    $page->fillField('Media item', $media_name);
    $page->pressButton('Save');

    // Assert that news has been created.
    $assert_session->pageTextContains('News Example title has been created.');
    $assert_session->pageTextContains('Example title');
    $assert_session->pageTextContains('News type 1');
    $assert_session->pageTextContains('News type 2');
    $assert_session->pageTextContains('News type 3');
    $assert_session->pageTextContains('Example Content');
    $assert_session->pageTextContains('Example Introduction');
    $assert_session->pageTextContains('24 January 2022');
    $assert_session->responseContains('image-test.png');
    $assert_session->responseContains('Starter Image test');
    $assert_session->responseContains('Starter Image test alt');

    $this->assertSocialShareBlock();
  }

}
