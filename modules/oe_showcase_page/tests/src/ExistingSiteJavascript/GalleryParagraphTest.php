<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_page\ExistingSiteJavascript;

use Drupal\Tests\oe_showcase\ExistingSiteJavascript\ShowcaseExistingSiteJavascriptTestBase;
use Drupal\Tests\oe_showcase\Traits\EntityBrowserTrait;
use Drupal\Tests\oe_showcase\Traits\MediaCreationTrait;
use Drupal\Tests\oe_showcase\Traits\NativeBrowserValidationTrait;
use Drupal\Tests\oe_showcase\Traits\TraversingTrait;

/**
 * Tests the gallery paragraph.
 */
class GalleryParagraphTest extends ShowcaseExistingSiteJavascriptTestBase {

  use EntityBrowserTrait;
  use MediaCreationTrait;
  use NativeBrowserValidationTrait;
  use TraversingTrait;

  /**
   * Tests the paragraph form.
   */
  public function testParagraphForm(): void {
    $this->markEntityTypeForCleanup('node');
    $this->markEntityTypeForCleanup('paragraph');

    // Create a media entity for each existing bundle.
    $media = $this->createTestMedia();

    $editor = $this->createUser([]);
    $editor->addRole('editor');
    $editor->save();
    $this->drupalLogin($editor);

    $this->drupalGet('node/add/oe_showcase_page');
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $page->fillField('Title', 'Gallery test page');

    $page->pressButton('List additional actions');
    $page->pressButton('Add Gallery');
    $assert_session->assertWaitOnAjaxRequest();
    $this->disableNativeBrowserRequiredFieldValidation();
    $page->pressButton('Save');
    $assert_session->elementTextContains('css', '.messages--error', 'Title field is required.');
    $assert_session->elementTextContains('css', '.messages--error', 'Medias field is required.');

    $page->fillField('field_body[0][subform][field_oe_title][0][value]', 'Gallery title');
    $page->fillField('field_body[0][subform][field_oe_plain_text_long][0][value]', 'Example <strong>description</strong> for the gallery paragraph.');
    $fieldset = $assert_session->elementExists('css', '[data-drupal-selector="edit-field-body-0-subform-field-oe-gallery-items-wrapper"]');
    // Open the <details> element that contains the medias field.
    $fieldset->click();
    $assert_session->pageTextContains('You can select up to 50 media items (50 left).');
    $assert_session->buttonExists('Select media', $fieldset)->press();
    $assert_session->assertWaitOnAjaxRequest();
    $this->getSession()->switchToIFrame('entity_browser_iframe_images_and_videos');
    $assert_session->linkExistsExact('Media library');
    $assert_session->linkExistsExact('Search in AV Portal');

    // Assert the exposed filters.
    $assert_session->fieldExists('Filter by name');
    $assert_session->fieldExists('Language');
    $this->assertEquals([
      'All' => '- Any -',
      'av_portal_photo' => 'AV Portal Photo',
      'av_portal_video' => 'AV Portal Video',
      'image' => 'Image',
      'remote_video' => 'Remote video',
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

    // Currently showcase ships media entities with the default_content module.
    // To make sure the above assertions are correct, we check that no pager
    // is present, so we know that there are no medias hidden in a second page.
    $assert_session->elementNotExists('css', 'nav.pager');

    $this->getMediaBrowserTileByMediaName('Euro with miniature figurines')->click();
    $this->getMediaBrowserTileByMediaName('Economic and Financial Affairs Council - Arrivals')->click();
    $this->getMediaBrowserTileByMediaName('Energy, let\'s save it!')->click();
    $this->getMediaBrowserTileByMediaName('Image title')->click();
    $assert_session->buttonExists('Select media')->press();
    $this->getSession()->switchToIFrame();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('You can select up to 50 media items (46 left).');
    $assert_session->buttonExists('Save')->press();
    $assert_session->pageTextContains('Page Gallery test page has been created.');

    // Do a simple smoke test to check that the gallery has been rendered.
    // No changes on gallery rendering are introduced in oe_showcase compared to
    // oe_whitelabel.
    $assert_session->elementTextEquals('css', '.paragraph--type--oe-gallery > h2.bcl-heading.mb-4', 'Gallery title');
    $assert_session->elementTextEquals('css', '.paragraph--type--oe-gallery > p', 'Example <strong>description</strong> for the gallery paragraph.');

    $node = $this->getNodeByTitle('Gallery test page');
    $this->drupalGet($node->toUrl('edit-form'));

    // Test that the gallery paragraph can be created inside a content row.
    $page->pressButton('List additional actions');
    $page->pressButton('Add Content row');
    $assert_session->assertWaitOnAjaxRequest();
    $content_row_wrapper = $assert_session->elementExists('css', '[data-drupal-selector="edit-field-body-1-subform-field-oe-paragraphs-wrapper"]');
    $assert_session->buttonExists('Add Gallery', $content_row_wrapper);
  }

}
