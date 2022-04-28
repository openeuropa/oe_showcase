<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_page\ExistingSite;

use Behat\Mink\Element\NodeElement;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Tests\oe_showcase\ExistingSite\ShowcaseExistingSiteTestBase;

/**
 * Tests the 'oe_showcase_page' content type.
 */
class PageTest extends ShowcaseExistingSiteTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->createUser([
      'create oe_showcase_page content',
      'view the administration theme',
    ]));
  }

  /**
   * Create test Showcase Page content.
   */
  public function testCreateShowCasePage() {
    // Mark test content for deletion after the test has finished.
    $this->markEntityTypeForCleanup('node');
    $this->markEntityTypeForCleanup('paragraph');

    $assert_session = $this->assertSession();

    // Create a Showcase Page node through the UI.
    $this->drupalGet('node/add/oe_showcase_page');
    $page = $this->getSession()->getPage();

    // Set page title.
    $page->fillField('title[0][value]', 'OE Showcase Demo Page');

    // Set page description.
    $page->fillField('field_description[0][value]', 'Page demo description');

    // Check the list of allowed paragraphs.
    $this->assertEqualsCanonicalizing(
      [
        'Add Accordion',
        'Add Banner',
        'Add Chart',
        'Add Contact form',
        'Add Content row',
        'Add Description list',
        'Add Document',
        'Add Facts and figures',
        'Add Links block',
        'Add Listing item block',
        'Add Map',
        'Add Quote',
        'Add Rich text',
        'Add Social feed',
        'Add Text with Featured media',
        'Add Timeline',
      ],
      $this->getParagraphAddMoreButtonList()
    );

    // Add Rich text paragraph.
    $page->pressButton('Add Rich text');
    $page->fillField(
      'field_body[0][subform][field_oe_title][0][value]',
      'Rich Text paragraph title'
    );
    $page->fillField(
      'field_body[0][subform][field_oe_text_long][0][value]',
      'Rich Text paragraph Body'
    );

    // Add a Banner paragraph.
    $page->pressButton('Add Banner');
    $page->selectFieldOption(
      'field_body[1][subform][field_oe_banner_type]',
      'page_center'
    );
    $page->fillField(
      'field_body[1][subform][field_oe_title][0][value]',
      'Banner 0 item title'
    );
    $page->fillField(
      'field_body[1][subform][field_oe_text][0][value]',
      'Banner 0 item Body'
    );

    // Add Quote Paragraph.
    $page->pressButton('Add Quote');
    $page->fillField(
      'field_body[2][subform][field_oe_plain_text_long][0][value]',
      'Every moment is a fresh beginning.'
    );
    $page->fillField(
      'field_body[2][subform][field_oe_text][0][value]',
      'T.S Eliot'
    );

    // Add a listing item block.
    $page->pressButton('Add Listing item block');

    $page->selectFieldOption(
      'field_body[3][variant]',
      'default'
    );
    $page->selectFieldOption(
      'field_body[3][subform][field_oe_list_item_block_layout]',
      'two_columns'
    );
    $page->fillField(
      'field_body[3][subform][field_oe_title][0][value]',
      'List item block example'
    );
    $page->fillField(
      'field_body[3][subform][field_oe_paragraphs][0][subform][field_oe_link][0][uri]',
      '<front>'
    );
    $page->fillField(
      'field_body[3][subform][field_oe_paragraphs][0][subform][field_oe_title][0][value]',
      'Home Page'
    );
    $page->fillField(
      'field_body[3][subform][field_oe_paragraphs][0][subform][field_oe_text_long][0][value]',
      'Listing item description'
    );

    $page->pressButton('Add Listing item');
    $page->fillField(
      'field_body[3][subform][field_oe_paragraphs][1][subform][field_oe_link][0][uri]',
      'https://example1.com'
    );
    $page->fillField(
      'field_body[3][subform][field_oe_paragraphs][1][subform][field_oe_title][0][value]',
      'Example 1 Page'
    );
    $page->fillField(
      'field_body[3][subform][field_oe_paragraphs][1][subform][field_oe_text_long][0][value]',
      'Listing item description for example 1'
    );

    // Add a Banner paragraph.
    $page->pressButton('Add Content row');

    // Verify that only the expect paragraphs can be referenced from a content
    // row.
    $content_row = $assert_session->elementExists('css', 'div[data-drupal-selector="edit-field-body-4"]');
    $this->assertEqualsCanonicalizing([
      'Add Accordion',
      'Add Document',
      'Add Links block',
      'Add Listing item block',
      'Add Quote',
      'Add Rich text',
      'Add Social media follow',
    ], $this->getParagraphAddMoreButtonList($content_row));

    $page->selectFieldOption(
      'field_body[4][variant]',
      'inpage_navigation'
    );
    $page->pressButton('Change variant');

    // Add a Rich text paragraph at the Content row.
    $page->pressButton('Add Rich text');
    $page->fillField(
      'field_body[4][subform][field_oe_paragraphs][0][subform][field_oe_title][0][value]',
      'Example title rich text 1'
    );
    $page->fillField(
      'field_body[4][subform][field_oe_paragraphs][0][subform][field_oe_text_long][0][value]',
      'Text description for rich text 1'
    );

    // Add Facts and figures paragraph.
    $page->pressButton('Add Facts and figures');
    $page->fillField(
      'field_body[5][subform][field_oe_title][0][value]',
      'Example Facts and Figures'
    );
    $page->fillField(
      'field_body[5][subform][field_oe_link][0][uri]',
      'http://read.more'
    );
    $page->fillField(
      'field_body[5][subform][field_oe_link][0][title]',
      'Read more'
    );

    $page->selectFieldOption(
      'field_body[5][subform][field_oe_paragraphs][0][subform][field_oe_icon]',
      'download'
    );
    $page->fillField(
      'field_body[5][subform][field_oe_paragraphs][0][subform][field_oe_title][0][value]',
      'Fact 1 title'
    );
    $page->fillField(
      'field_body[5][subform][field_oe_paragraphs][0][subform][field_oe_subtitle][0][value]',
      'Fact 1 subtitle'
    );
    $page->fillField(
      'field_body[5][subform][field_oe_paragraphs][0][subform][field_oe_plain_text_long][0][value]',
      'Fact 1 description'
    );

    $page->pressButton('Add Fact');
    $page->selectFieldOption(
      'field_body[5][subform][field_oe_paragraphs][1][subform][field_oe_icon]',
      'arrow-up'
    );
    $page->fillField(
      'field_body[5][subform][field_oe_paragraphs][1][subform][field_oe_title][0][value]',
      'Fact 2 title'
    );
    $page->fillField(
      'field_body[5][subform][field_oe_paragraphs][1][subform][field_oe_subtitle][0][value]',
      'Fact 2 subtitle'
    );
    $page->fillField(
      'field_body[5][subform][field_oe_paragraphs][1][subform][field_oe_plain_text_long][0][value]',
      'Fact 2 description'
    );

    $page->pressButton('Add Fact');
    $page->selectFieldOption(
     'field_body[5][subform][field_oe_paragraphs][2][subform][field_oe_icon]',
     'arrow-down'
    );
    $page->fillField(
      'field_body[5][subform][field_oe_paragraphs][2][subform][field_oe_title][0][value]',
      'Fact 3 title'
    );
    $page->fillField(
      'field_body[5][subform][field_oe_paragraphs][2][subform][field_oe_subtitle][0][value]',
      'Fact 3 subtitle'
    );
    $page->fillField(
      'field_body[5][subform][field_oe_paragraphs][2][subform][field_oe_plain_text_long][0][value]',
      'Fact 3 description'
    );

    // Add Description list paragraph.
    $page->pressButton('Add Description list');
    $page->selectFieldOption(
      'field_body[6][subform][oe_w_orientation]',
      'horizontal'
    );
    $page->fillField(
      'field_body[6][subform][field_oe_title][0][value]',
      'Example Description list'
    );
    $page->fillField(
      'field_body[6][subform][field_oe_description_list_items][0][term]',
      'First term'
    );
    $page->fillField(
      'field_body[6][subform][field_oe_description_list_items][0][description][value]',
      'First term description'
    );

    $page->pressButton('field_body_6_subform_field_oe_description_list_items_add_more');
    $page->fillField(
      'field_body[6][subform][field_oe_description_list_items][1][term]',
      'Second term'
    );
    $page->fillField(
      'field_body[6][subform][field_oe_description_list_items][1][description][value]',
      'Second term description'
    );

    // Create a media to be referenced.
    $file_uri = $this->container->get('file_system')->copy(
      $this->container->get('extension.list.module')->getPath('oe_media') . '/tests/fixtures/sample.pdf',
      'public://test.pdf'
    );
    $pdf = File::create(['uri' => $file_uri]);
    $pdf->save();
    $this->markEntityForCleanup($pdf);
    $local_media = Media::create([
      'bundle' => 'document',
      'name' => 'Local PDF file',
      'oe_media_file_type' => 'local',
      'oe_media_file' => [
        'target_id' => $pdf->id(),
      ],
    ]);
    $local_media->save();
    $this->markEntityForCleanup($local_media);

    // Press the "Add document" button of the main reference field.
    $assert_session
      ->elementExists('css', 'div[data-drupal-selector="edit-field-body-add-more"]')
      ->pressButton('Add Document');
    $page->fillField(
      'field_body[7][subform][field_oe_media][0][target_id]',
      sprintf('Local PDF file (%s)', $local_media->id())
    );

    // Save node.
    $page->pressButton('Save');

    // Assert node view.
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $assert_session->addressEquals($language . '/pages/oe-showcase-demo-page');

    // Assert Page title.
    $assert_session->pageTextContains('OE Showcase Demo Page');

    // Assert Description.
    $assert_session->pageTextContains('Page demo description');

    // Assert Rich Text Title.
    $assert_session->pageTextContains('Rich Text paragraph title');

    // Assert Rich Text Body.
    $assert_session->pageTextContains('Rich Text paragraph Body');

    // Assert Quote.
    $assert_session->pageTextContains('Every moment is a fresh beginning.');
    $assert_session->pageTextContains('T.S Eliot');

    // Assert Banner.
    $assert_session->pageTextContains('Banner 0 item title');
    $assert_session->pageTextContains('Banner 0 item Body');

    // Assert Listing item block.
    $assert_session->pageTextContains('List item block example');
    $assert_session->pageTextContains('Home Page');
    $assert_session->pageTextContains('Listing item description');
    $assert_session->pageTextContains('Example 1 Page');
    $assert_session->pageTextContains('Listing item description for example 1');

    $assert_session->pageTextContains('Local PDF file');
    $assert_session->linkByHrefExists(\Drupal::service('file_url_generator')->generateAbsoluteString($file_uri));

    // Assert Content row.
    $assert_session->pageTextContains('Example title rich text 1');
    $assert_session->pageTextContains('Text description for rich text 1');

    // Assert Facts and figures block.
    $assert_session->pageTextContains('Example Facts and Figures');
    $assert_session->pageTextContains('Read more');
    $assert_session->linkByHrefExists('http://read.more');
    $assert_session->pageTextContains('Fact 1 title');
    $assert_session->pageTextContains('Fact 1 subtitle');
    $assert_session->pageTextContains('Fact 1 description');
    $assert_session->pageTextContains('Fact 2 title');
    $assert_session->pageTextContains('Fact 2 subtitle');
    $assert_session->pageTextContains('Fact 2 description');
    $assert_session->pageTextContains('Fact 3 title');
    $assert_session->pageTextContains('Fact 3 subtitle');
    $assert_session->pageTextContains('Fact 3 description');

    // Assert Description list.
    $assert_session->pageTextContains('Example Description list');
    $assert_session->pageTextContains('First term');
    $assert_session->pageTextContains('First term description');
    $assert_session->pageTextContains('Second term');
    $assert_session->pageTextContains('Second term description');

    $this->assertSocialShareBlock();
  }

  /**
   * Returns the list of buttons that allow to add new paragraphs.
   *
   * @param \Behat\Mink\Element\NodeElement|null $container
   *   The element where to search for the list of buttons. If empty, the page
   *   element will be used.
   *
   * @return array
   *   A list of button values, e.g. "Add Accordion".
   */
  protected function getParagraphAddMoreButtonList(NodeElement $container = NULL): array {
    $container = $container ?: $this->getSession()->getPage();
    $button_wrapper = $container->findAll('css', '.paragraphs-dropbutton-wrapper');
    $this->assertCount(1, $button_wrapper, 'Multiple or no dropdown wrappers found, but one expected.');

    return array_map(function (NodeElement $button) {
      return $button->getValue();
    }, $button_wrapper[0]->findAll('css', 'input.field-add-more-submit'));
  }

}
