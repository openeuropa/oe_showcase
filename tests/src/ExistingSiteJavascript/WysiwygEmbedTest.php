<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSiteJavascript;

use Drupal\Tests\ckeditor\Traits\CKEditorTestTrait;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\FilePatternAssert;
use Drupal\Tests\oe_showcase\Traits\EntityBrowserTrait;
use Drupal\Tests\oe_showcase\Traits\MediaCreationTrait;
use Drupal\Tests\oe_showcase\Traits\ScrollTrait;
use Drupal\Tests\oe_showcase\Traits\TraversingTrait;

/**
 * Tests embedding entities in the WYSIWYG.
 */
class WysiwygEmbedTest extends ShowcaseExistingSiteJavascriptTestBase {

  use CKEditorTestTrait;
  use EntityBrowserTrait;
  use MediaCreationTrait;
  use TraversingTrait;
  use ScrollTrait;

  /**
   * Tests the media embed functionality.
   */
  public function testMediaEmbed(): void {
    $this->markEntityTypeForCleanup('node');

    // Create a media entity for each existing bundle.
    $media = $this->createTestMedia();

    $user = $this->createUser();
    $user->addRole('editor');
    $user->save();
    $this->drupalLogin($user);

    $this->drupalGet('/node/add/oe_sc_news');
    $this->getSession()->getPage()->fillField('Title', 'Test embed');
    $this->assignNameToCkeditorIframe();
    $this->waitForEditor();

    // This presses the button inside the CKEditor.
    $this->getSession()->getPage()->pressButton('Embed media');
    $assert_session = $this->assertSession();
    $assert_session->assertWaitOnAjaxRequest();
    // Switch to the iframe that contains the embed views.
    $this->getSession()->switchToIFrame('entity_browser_iframe_embed_media');

    $assert_session->fieldExists('Filter by name');
    $assert_session->fieldExists('Language');
    $this->assertEquals([
      'All' => '- Any -',
      'av_portal_photo' => 'AV Portal Photo',
      'av_portal_video' => 'AV Portal Video',
      'document' => 'Document',
      'image' => 'Image',
      'remote_video' => 'Remote video',
    ], $this->getSelectOptions($assert_session->selectExists('Media type')));

    $expected_media_bundles = [
      'av_portal_photo',
      'av_portal_video',
      'document',
      'image',
      'remote_video',
    ];
    foreach ($expected_media_bundles as $bundle) {
      $assert_session->pageTextContains($media[$bundle]->label());
    }

    foreach (array_diff(array_keys($media), $expected_media_bundles) as $unwanted_bundle) {
      $assert_session->pageTextNotContains($media[$unwanted_bundle]->label());
    }
    // Currently showcase ships media entities with the default_content module.
    // To make sure the above assertions are correct, we check that no pager
    // is present, so we know that there are no media hidden in a second page.
    $assert_session->elementNotExists('css', 'nav.pager');

    // Check that the search tab is present.
    $this->clickLink('Search in AV Portal');
    $assert_session->fieldExists('Search');
    $assert_session->pageTextContains('Visit by Federica Mogherini, Vice-President of the EC');

    $this->clickLink('Media library');

    // Embed an image media.
    $this->getMediaBrowserTileByMediaName('Document title')->click();
    $assert_session->buttonExists('Select media')->press();

    $this->getSession()->switchToIFrame();
    $assert_session->assertWaitOnAjaxRequest();
    // Only one bundle is configured for embed.
    $assert_session->fieldNotExists('Display as');
    $modal_button_pane = $assert_session->elementExists('css', 'div.ui-dialog-buttonpane');
    $modal_button_pane->findButton('Embed')->press();
    $assert_session->assertWaitOnAjaxRequest();

    // Check that the element has been inserted in the editor.
    $this->getSession()->switchToIFrame('ckeditor');
    $assert_session->linkExists('Document title');

    $this->getSession()->switchToIFrame();
    $assert_session->buttonExists('Save')->press();
    $assert_session->pageTextContains('Test embed');

    $news_body = $assert_session->elementExists('css', 'div.oe-sc-news__body');
    // Assert the rendering of the document.
    $document_node = $assert_session->elementExists('css', 'div.mt-4', $news_body);
    $expected = [
      'file' => [
        'title' => 'Document title',
        'language' => 'English',
        'url' => $media['document']->get('oe_media_file')->entity->createFileUrl(FALSE),
        'meta' => '(2.96 KB - PDF)',
        'icon' => 'file-pdf-fill',
      ],
      'translations' => NULL,
      'link_label' => 'Download',
    ];
    $assert = new FilePatternAssert();
    $assert->assertPattern($expected, $document_node->getOuterHtml());

    $this->drupalGet($this->getNodeByTitle('Test embed')->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assignNameToCkeditorIframe();

    // Embed the remaining media.
    $this->embedMediaInWysiwyg('Image title');
    $this->embedMediaInWysiwyg('Euro with miniature figurines');
    $this->embedMediaInWysiwyg('Economic and Financial Affairs Council - Arrivals');
    $this->embedMediaInWysiwyg("Energy, let's save it!");
    $assert_session->buttonExists('Save')->press();
    $assert_session->pageTextContains('Test embed');

    // Extract the real file name of the image.
    $image_filename = $media['image']->get('oe_media_image')->entity->getFilename();
    $news_body = $assert_session->elementExists('css', 'div.oe-sc-news__body');
    // We expect only two images. The thumbnails shouldn't be rendered.
    $assert_session->elementsCount('css', 'img', 2, $news_body);
    // Assert that all the media are rendered.
    $assert_session->elementExists('css', sprintf('img.img-fluid[src*="/styles/wide/public/%s"]', $image_filename), $news_body);
    $assert_session->elementExists('css', 'img[src*="/styles/wide/avportal/P-038924/00-15.jpg"]', $news_body);
    $assert_session->elementExists('css', 'iframe[src^="//ec.europa.eu/avservices/play.cfm?ref=I-163162"]', $news_body);
    $assert_session->elementExists('css', 'iframe[src*="/media/oembed?url=https%3A//www.youtube.com/watch%3Fv%3D1-g73ty9v04"]', $news_body);
  }

  /**
   * Embeds into the WYSIWYG a media entity given its label.
   *
   * @param string $label
   *   The media label.
   */
  protected function embedMediaInWysiwyg(string $label): void {
    $this->scrollIntoView('.field--name-body');
    $this->moveCkeditorCursorToEnd();
    $this->getSession()->getPage()->pressButton('Embed media');
    $assert_session = $this->assertSession();
    $assert_session->assertWaitOnAjaxRequest();

    // Switch to the iframe that contains the embed views.
    $this->getSession()->switchToIFrame('entity_browser_iframe_embed_media');
    $this->getMediaBrowserTileByMediaName($label)->click();
    $assert_session->buttonExists('Select media')->press();

    $this->getSession()->switchToIFrame();
    $assert_session->assertWaitOnAjaxRequest();
    $modal_button_pane = $assert_session->elementExists('css', 'div.ui-dialog-buttonpane');
    $modal_button_pane->findButton('Embed')->press();
    $assert_session->assertWaitOnAjaxRequest();

    $this->getSession()->switchToIFrame('ckeditor');
    $assert_session->linkExists($label);

    $this->getSession()->switchToIFrame();
  }

  /**
   * Moves the cursor position to the end of the text inside the WYSIWYG.
   *
   * @param string $instance_id
   *   (optional) The CKEditor instance ID. Defaults to 'edit-body-0-value'.
   */
  protected function moveCkeditorCursorToEnd(string $instance_id = 'edit-body-0-value'): void {
    $javascript = <<<JS
(function(){
  var editor = CKEDITOR.instances['{$instance_id}'];
  var range = editor.createRange();
  range.moveToElementEditablePosition(range.root, true);
  editor.getSelection().selectRanges([range]);
})()
JS;
    $this->getSession()->evaluateScript($javascript);
  }

}
