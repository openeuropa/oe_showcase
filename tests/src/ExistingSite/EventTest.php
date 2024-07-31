<?php

declare(strict_types=1);

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
 * Class to test Event content type on existing site tests.
 */
class EventTest extends ShowcaseExistingSiteTestBase {

  use MediaTypeCreationTrait;
  use TestFileCreationTrait;
  use AssertPathAccessTrait;
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
   * Test Event type CRUD.
   */
  public function testEventType(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Assert user without permission can't create event types.
    $this->assertPathsRequireRole([
      'admin/structure/taxonomy/manage/event_type/overview',
      'admin/structure/taxonomy/manage/event_type/add',
    ], 'editor');

    // Assert editors can create event types.
    $user = $this->createUser([]);
    $user->addRole('editor');
    $user->save();
    $this->drupalLogin($user);

    $this->drupalGet('admin/structure/taxonomy/manage/event_type/add');
    $page->fillField('Name', 'Term one');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Created new term Term one.');

    // Assert editors can edit event types.
    $this->drupalGet('admin/structure/taxonomy/manage/event_type/overview');
    $page->clickLink('Edit');
    $page->fillField('Name', 'Term changed');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Updated term Term changed.');

    // Assert editors can delete event types.
    $this->drupalGet('admin/structure/taxonomy/manage/event_type/overview');
    $page->clickLink('Delete');
    $assert_session->pageTextContains('Are you sure you want to delete the taxonomy term Term changed?');
    $page->pressButton('Delete');
    $assert_session->pageTextContains('Deleted term Term changed.');
  }

  /**
   * Check creation Event content through the UI.
   */
  public function testCreateEvent(): void {
    // Mark test content for deletion after the test has finished.
    $this->markEntityTypeForCleanup('node');
    $this->markEntityTypeForCleanup('media');
    $this->markEntityTypeForCleanup('file');
    $this->markEntityTypeForCleanup('taxonomy_term');

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

    $term = Term::create([
      'vid' => 'event_type',
      'name' => 'Test term',
    ]);
    $term->save();

    // Assert user without permission can't create events.
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
    $page->selectFieldOption('Event type', 'Test term');
    $field = $page->findField('Content');
    $this->assertEquals('rich_text', $this->getWysiwigTextFormat($field));
    $field->setValue('Example Content');
    $this->assertEquals('simple_rich_text', $this->getWysiwigTextFormat($page->findField('Introduction')));
    $page->fillField('Introduction', 'Example Introduction');
    $page->fillField('oe_sc_event_dates[0][value][date]', '2022-01-24');
    $page->fillField('oe_sc_event_dates[0][value][time]', '20:00:00');
    $page->fillField('oe_sc_event_dates[0][end_value][date]', '2022-01-24');
    $page->fillField('oe_sc_event_dates[0][end_value][time]', '22:00:00');
    $page->fillField('oe_sc_event_registration_url[0][uri]', 'https://europa.eu');
    $media_name = $media_image->getName() . ' (' . $media_image->id() . ')';
    $page->fillField('oe_featured_media[0][featured_media][target_id]', $media_name);
    $document_name = $media_document->getName() . ' (' . $media_document->id() . ')';
    $page->fillField('oe_documents[0][target_id]', $document_name);
    $page->pressButton('Save');

    // Assert that event has been created.
    $assert_session->addressEquals('/en/events/example-title');
    $assert_session->pageTextContains('Event Example title has been created.');
    $assert_session->pageTextContains('Example title');
    $assert_session->pageTextContains('Test term');
    $assert_session->pageTextContains('Example Content');
    $assert_session->pageTextContains('Example Introduction');
    $assert_session->pageTextContains('24 January 2022');
    $assert_session->responseContains('image-test.png');
    $assert_session->responseContains('Starter Image test');
    $assert_session->responseContains('Starter Image test alt');
    $assert_session->elementContains('css', 'a[target="_blank"]', 'Register');

    $this->assertSocialShareBlock();
  }

}
