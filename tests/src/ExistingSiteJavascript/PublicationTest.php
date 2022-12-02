<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSiteJavascript;

use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\oe_showcase\Traits\EntityBrowserTrait;
use Drupal\Tests\oe_showcase\Traits\MediaCreationTrait;
use Drupal\Tests\oe_showcase\Traits\TraversingTrait;
use Drupal\Tests\oe_showcase\Traits\WysiwygTrait;
use Drupal\Tests\oe_whitelabel\PatternAssertions\ContentBannerAssert;

/**
 * Tests the publication content type.
 */
class PublicationTest extends ShowcaseExistingSiteJavascriptTestBase {

  use EntityBrowserTrait;
  use MediaCreationTrait;
  use TraversingTrait;
  use WysiwygTrait;

  /**
   * Tests the creation of publication nodes.
   *
   * Covers the changes in the form and view modes introduced in oe_showcase.
   */
  public function testCreation(): void {
    $this->markEntityTypeForCleanup('node');

    $vocabulary = Vocabulary::load('publication_type');
    $this->createTerm($vocabulary, ['name' => 'Type 1']);
    $this->createTerm($vocabulary, ['name' => 'Type 2']);
    $this->createPerson('John', 'Red');
    $this->createPerson('Bob', 'Purple');
    $media = $this->createTestMedia();

    $user = $this->createUser([]);
    $user->addRole('editor');
    $user->save();
    $this->drupalLogin($user);

    $this->drupalGet('node/add/oe_sc_publication');
    $assert_session = $this->assertSession();
    $assert_session->fieldExists('Title')->setValue('Test publication');

    // Check that the publication type field is present but do not select a
    // value to check that it's not mandatory.
    $this->assertEquals([
      '- None -',
      'Type 1',
      'Type 2',
    ], array_values($this->getSelectOptions($assert_session->fieldExists('Publication type'))));

    // Assert the media browser for the thumbnail field.
    $thumbnail_fieldset = $assert_session->elementExists('css', '[data-drupal-selector="edit-oe-featured-media-wrapper"]');
    $assert_session->buttonExists('Select media', $thumbnail_fieldset)->press();
    $assert_session->assertWaitOnAjaxRequest();
    $this->getSession()->switchToIFrame('entity_browser_iframe_images');
    $assert_session->linkExistsExact('Media library');
    $assert_session->linkExistsExact('Search in AV Portal');

    // Assert the exposed filters.
    $assert_session->fieldExists('Filter by name');
    $assert_session->fieldExists('Language');
    $this->assertEquals([
      'All' => '- Any -',
      'av_portal_photo' => 'AV Portal Photo',
      'image' => 'Image',
    ], $this->getSelectOptions($assert_session->selectExists('Media type')));

    // Make sure this entity browser shows only the expected media bundles.
    $expected_media_bundles = [
      'av_portal_photo',
      'av_portal_video',
      'image',
      'remote_video',
    ];
    foreach (array_diff(array_keys($media), $expected_media_bundles) as $unwanted_bundle) {
      $assert_session->pageTextNotContains($media[$unwanted_bundle]->label());
    }

    $this->getMediaBrowserTileByMediaName('Euro with miniature figurines');
    $this->getMediaBrowserTileByMediaName('Image title')->click();
    $assert_session->buttonExists('Select media')->press();
    $this->getSession()->switchToIFrame();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Image title');

    $assert_session->fieldExists('Reference code')->setValue('I-9876987012');
    // @todo Assert formats.
    $assert_session->fieldExists('Short description')->setValue('Short description text.');
    $this->enterTextInWysiwyg('Description', 'Description text.');
    // @todo Setting dates in JS tests is not as straightforward as it seems.
    // @see oe_content/tests/src/Behat/DateFieldContext.php
    // Assert the media browser for the document field.
    $document_fieldset = $assert_session->elementExists('css', '[data-drupal-selector="edit-oe-sc-publication-document-wrapper"]');
    $assert_session->buttonExists('Select media', $document_fieldset)->press();
    $assert_session->assertWaitOnAjaxRequest();
    $this->getSession()->switchToIFrame('entity_browser_iframe_documents');
    $assert_session->linkExistsExact('Media library');
    $assert_session->linkExistsExact('Create new document');

    // Assert the exposed filters.
    $assert_session->fieldExists('Filter by name');
    $assert_session->fieldExists('Language');
    $assert_session->fieldNotExists('Media type');

    // Make sure this entity browser shows documents.
    $expected_media_bundles = ['document'];
    foreach (array_diff(array_keys($media), $expected_media_bundles) as $unwanted_bundle) {
      $assert_session->pageTextNotContains($media[$unwanted_bundle]->label());
    }

    // Assert that the "Create new document" tab allows to create a document
    // media.
    $this->getSession()->getPage()->findLink('Create new document')->click();
    $assert_session->fieldExists('Name');
    $assert_session->fieldExists('File Type');
    $assert_session->buttonExists('Create and select document');

    // Go back to the media library and select the test document.
    $this->getSession()->getPage()->findLink('Media library')->click();
    $this->getMediaBrowserTileByMediaName('Document title')->click();
    $assert_session->buttonExists('Select media')->press();
    $this->getSession()->switchToIFrame();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Document title');

    $assert_session->fieldExists('Authors')->setValue('John Red');
    $assert_session->buttonExists('Add another item')->press();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->fieldExists('oe_sc_publication_authors[1][target_id]')->setValue('Bob Purple');

    $assert_session->buttonExists('Save')->press();
    $assert_session->pageTextContains('Publication Test publication has been created.');
    // Since the rendering of the content banner view mode has been changed,
    // test that everything is rendered correctly when no publication type is
    // selected.
    $content_banner_assert = new ContentBannerAssert();
    $expected_content_banner = [
      'title' => 'Test publication',
      // @todo Add date test.
      'description' => 'Short description text.',
      'badges' => [],
      'image' => [
        'alt' => 'Alt text',
        'src' => $media['image']->get('oe_media_image')->entity->getFilename(),
      ],
    ];
    $content_banner_assert->assertPattern($expected_content_banner, $assert_session->elementExists('css', '.bcl-content-banner')->getOuterHtml());

    // Edit again the publication and select a type.
    $publication = $this->getNodeByTitle('Test publication');
    $this->drupalGet($publication->toUrl('edit-form'));
    $assert_session->fieldExists('Publication type')->selectOption('Type 1');
    $assert_session->buttonExists('Save')->press();
    $assert_session->pageTextContains('Publication Test publication has been updated.');

    $expected_content_banner['badges'] = ['Type 1'];
    $content_banner_assert->assertPattern($expected_content_banner, $assert_session->elementExists('css', '.bcl-content-banner')->getOuterHtml());

    $assert_session->pageTextContains('John Red');
    $assert_session->pageTextContains('Bob Purple');
    $assert_session->pageTextContains('I-9876987012');
    $assert_session->pageTextContains('Description text.');
    $assert_session->pageTextContains('Document title');
  }

  /**
   * Creates a person node with default values.
   *
   * @param string $first_name
   *   The first name value.
   * @param string $last_name
   *   The last name value.
   *
   * @return \Drupal\node\NodeInterface
   *   The person node.
   */
  protected function createPerson(string $first_name, string $last_name): NodeInterface {
    return $this->createNode([
      'type' => 'oe_sc_person',
      'oe_sc_person_first_name' => $first_name,
      'oe_sc_person_last_name' => $last_name,
      'oe_sc_person_country' => 'IT',
      'oe_sc_person_occupation' => $this->randomString(),
      'oe_sc_person_position' => $this->randomString(),
      'status' => 1,
    ]);
  }

}
