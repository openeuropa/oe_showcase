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
use Drupal\Tests\oe_whitelabel\PatternAssertions\InPageNavigationAssert;

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

    $assert_session->buttonExists('Save')->press();
    $assert_session->pageTextContains('Publication Test publication has been created.');
    // Since the rendering of the content banner view mode has been changed,
    // test that everything is rendered correctly when no publication type is
    // selected.
    $content_banner_assert = new ContentBannerAssert();
    $expected_content_banner = [
      'title' => 'Test publication',
      'meta' => [
        date('d F Y'),
      ],
      'description' => 'Short description text.',
      'badges' => [],
      'image' => [
        'alt' => 'Alt text',
        'src' => $media['image']->get('oe_media_image')->entity->getFilename(),
      ],
    ];
    $content_banner_assert->assertPattern($expected_content_banner, $assert_session->elementExists('css', '.bcl-content-banner')->getOuterHtml());

    $assert_session->pageTextContains('I-9876987012');
    $assert_session->pageTextContains('Description text.');
    $assert_session->pageTextContains('Document title');

    // Edit again the publication and select a type and date.
    $publication = $this->getNodeByTitle('Test publication');
    $this->drupalGet($publication->toUrl('edit-form'));
    $assert_session->fieldExists('Publication type')->selectOption('Type 1');
    $assert_session->fieldExists('oe_publication_date[0][value][date]')->setValue('11.01.2022');
    $assert_session->buttonExists('Save')->press();
    $assert_session->pageTextContains('Publication Test publication has been updated.');

    $expected_content_banner['badges'] = ['Type 1'];
    $expected_content_banner['meta'] = ['01 November 2022'];
    $content_banner_assert->assertPattern($expected_content_banner, $assert_session->elementExists('css', '.bcl-content-banner')->getOuterHtml());

    // Edit again to add an author.
    $this->createPerson('John', 'Red');
    $this->drupalGet($publication->toUrl('edit-form'));
    $assert_session->fieldExists('Authors')->setValue('John Red');
    $assert_session->buttonExists('Save')->press();
    $assert_session->pageTextContains('Publication Test publication has been updated.');

    $inpage_nav_assert = new InPageNavigationAssert();
    $inpage_nav_assert->assertPattern([
      'title' => 'Page content',
      'links' => [
        [
          'label' => 'Authors',
          'href' => '#authors',
        ],
        [
          'label' => 'Reference code',
          'href' => '#reference-code',
        ],
        [
          'label' => 'Description',
          'href' => '#description',
        ],
        [
          'label' => 'Document',
          'href' => '#document',
        ],
      ],
    ], $assert_session->elementExists('css', 'nav.bcl-inpage-navigation')->getOuterHtml());

    $this->assertEquals('Authors', $assert_session->elementExists('css', 'h2#authors')->getText());
    $author_list = $assert_session->elementExists('css', 'h2#authors + div.mb-4-5 ul');
    $this->assertCount(1, $author_list->findAll('css', 'li'));
    $this->assertEquals('John Red', trim($author_list->getText()));

    // Test that up to two authors, they are rendered in an unordered list.
    $this->createPerson('Bob', 'Purple');
    $this->drupalGet($publication->toUrl('edit-form'));
    $assert_session->buttonExists('Add another item')->press();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->fieldExists('field_publication_authors[1][target_id]')->setValue('Bob Purple');
    $assert_session->buttonExists('Save')->press();
    $assert_session->pageTextContains('Publication Test publication has been updated.');

    $author_list = $assert_session->elementExists('css', 'h2#authors + div.mb-4-5 ul');
    $list_items = $author_list->findAll('css', 'li');
    $this->assertCount(2, $list_items);
    $this->assertEquals('John Red', trim($list_items[0]->getText()));
    $this->assertEquals('Bob Purple', trim($list_items[1]->getText()));

    // When three authors or more are present, they are rendered separated by a
    // bullet.
    $this->createPerson('Mia', 'Green');
    $this->drupalGet($publication->toUrl('edit-form'));
    $assert_session->buttonExists('Add another item')->press();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->fieldExists('field_publication_authors[2][target_id]')->setValue('Mia Green');
    $assert_session->buttonExists('Save')->press();
    $assert_session->pageTextContains('Publication Test publication has been updated.');

    $assert_session->elementNotExists('css', 'h2#authors + div.mb-4-5 ul');
    $this->assertEquals('John Red • Bob Purple • Mia Green', trim($assert_session->elementExists('css', 'h2#authors + div.mb-4-5 p')->getText()));
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
