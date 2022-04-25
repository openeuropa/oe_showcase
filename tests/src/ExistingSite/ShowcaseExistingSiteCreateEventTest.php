<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Class to test Event content type on existing site tests.
 */
class ShowcaseExistingSiteCreateEventTest extends ShowcaseExistingSiteTestBase {

  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create user.
    $user = $this->createUser([]);
    $this->drupalLogin($user);
  }

  /**
   * Check creation Event content through the UI.
   */
  public function testCreateEvent() {
    // Mark test content for deletion after the test has finished.
    $this->markEntityTypeForCleanup('node');
    $this->markEntityTypeForCleanup('media');
    $this->markEntityTypeForCleanup('file');

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Create a sample media entity to be embedded.
    $file_text = File::create([
      'uri' => $this->getTestFiles('text')[0]->uri,
    ]);
    $file_text->save();
    $media_document = Media::create([
      'bundle' => 'document',
      'name' => 'Event document test',
      'oe_media_file_type' => 'local',
      'oe_media_file' => [
        [
          'target_id' => $file_text->id(),
          'alt' => 'Event document alt',
          'title' => 'Event document title',
        ],
      ],
    ]);
    $media_document->save();

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

    // Assert editors don't have permissions to create Event items.
    $this->drupalGet('node/add/oe_sc_event');
    $assert_session->pageTextContains('You are not authorized to access this page.');
    $assert_session->statusCodeEquals(403);

    // Create editor user.
    $user = $this->createUser([]);
    $user->addRole('editor');
    $user->save();
    $this->drupalLogin($user);

    // Assert that editors have access to the Simple/Rich text formats.
    $this->drupalGet('node/add/oe_sc_event');
    $assert_session->pageTextNotContains('This field has been disabled because you do not have sufficient permissions to edit it.');
    $page->fillField('Title', 'Example title');
    $page->fillField('Content', 'Example Content');
    $page->fillField('Introduction', 'Example Introduction');
    $page->fillField('oe_sc_event_dates[0][value][date]', '2022-01-24');
    $page->fillField('oe_sc_event_dates[0][value][time]', '20:00:00');
    $page->fillField('oe_sc_event_dates[0][end_value][date]', '2022-01-24');
    $page->fillField('oe_sc_event_dates[0][end_value][time]', '22:00:00');
    $media_name = $media_image->getName() . ' (' . $media_image->id() . ')';
    $page->fillField('oe_featured_media[0][featured_media][target_id]', $media_name);
    $document_name = $media_document->getName() . ' (' . $media_document->id() . ')';
    $page->fillField('oe_documents[0][target_id]', $document_name);
    $page->pressButton('Save');

    // Assert that event has been created.
    $assert_session->pageTextContains('Event Example title has been created.');
    $assert_session->pageTextContains('Example title');
    $assert_session->pageTextContains('Example Content');
    $assert_session->pageTextContains('Example Introduction');
    $assert_session->pageTextContains('24 January 2022');
    $assert_session->responseContains('image-test.png');
    $assert_session->responseContains('Starter Image test');
    $assert_session->responseContains('Starter Image test alt');

    // Assert Social Share block.
    $assert_session->elementExists('xpath', '//div[@class="container mt-5"]/*//div[contains(., "Share this page") and @id="block-showcase-socialshare"]');
  }

}
