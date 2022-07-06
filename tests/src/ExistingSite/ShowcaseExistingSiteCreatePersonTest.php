<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Class to test Person content type on existing site tests.
 */
class ShowcaseExistingSiteCreatePersonTest extends ShowcaseExistingSiteTestBase {

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
   * Check creation Person content through the UI.
   */
  public function testCreatePerson() {
    // Mark test content for deletion after the test has finished.
    $this->markEntityTypeForCleanup('node');
    $this->markEntityTypeForCleanup('media');
    $this->markEntityTypeForCleanup('file');

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

    // Create a sample document media entity to be embedded.
    $document_file = File::create([
      'uri' => $this->getTestFiles('text')[0]->uri,
    ]);
    $document_file->save();
    $media_document = Media::create([
      'bundle' => 'document',
      'name' => 'Event document test',
      'oe_media_file_type' => 'local',
      'oe_media_file' => [
        [
          'target_id' => (int) $document_file->id(),
          'alt' => 'Event document alt',
          'title' => 'Event document title',
        ],
      ],
    ]);
    $media_document->save();

    // Assert editors don't have permissions to create Person items.
    $this->drupalGet('node/add/oe_sc_person');
    $assert_session->pageTextContains('You are not authorized to access this page.');
    $assert_session->statusCodeEquals(403);

    // Create editor user.
    $user = $this->createUser([]);
    $user->addRole('editor');
    $user->save();
    $this->drupalLogin($user);

    // Assert that editors have access to the Simple/Rich text formats.
    $this->drupalGet('node/add/oe_sc_person');
    $assert_session->pageTextNotContains('This field has been disabled because you do not have sufficient permissions to edit it.');
    $page->fillField('First name', 'Stefan');
    $page->fillField('Last name', 'Mayer');
    $page->fillField('Short description', 'Example short description field.');
    $page->fillField('Use existing media', $media_image->getName() . ' (' . $media_image->id() . ')');
    $page->fillField('Country', 'DE');
    $page->fillField('Occupation', 'DG TEST');
    $page->fillField('Position', 'Director');
    $page->fillField('Additional information', 'Example additional information field.');
    $page->fillField('URL', 'https://linkedin.com');
    $page->fillField('Link text', 'Linkedin');
    $page->fillField('Link type', 'linkedin');
    $page->pressButton('Add new document reference');
    $page->fillField('oe_sc_person_documents[form][0][oe_document][0][target_id]', $media_document->getName() . ' (' . $media_document->id() . ')');
    $page->pressButton('Create document reference');
    $page->pressButton('Save');

    // Assert that person has been created.
    $assert_session->pageTextContains('Person Stefan Mayer has been created.');
    $assert_session->pageTextContains('Stefan Mayer');
    $assert_session->pageTextContains('Example short description field.');
    $assert_session->responseContains('image-test.png');
    $assert_session->responseContains('Starter Image test');
    $assert_session->responseContains('Starter Image test alt');
    $assert_session->pageTextContains('Germany');
    $assert_session->pageTextContains('DG TEST');
    $assert_session->pageTextContains('Director');
    $assert_session->pageTextContains('Linkedin');
    $assert_session->pageTextContains('Event document test');

    $this->assertSocialShareBlock();
  }

}
