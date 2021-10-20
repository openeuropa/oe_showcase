<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_page\ExistingSite;

use Drupal\Tests\oe_showcase\ExistingSite\ShowcaseExistingSiteTestBase;

/**
 * Tests the 'oe_showcase_page' content type.
 */
class PageTest extends ShowcaseExistingSiteTestBase {

  /**
   * A user with permission to create 'oe_showcase_page' content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $permissions = [
      'create oe_showcase_page content',
    ];

    $this->user = $this->createUser($permissions);
  }

  /**
   * Create test Showcase Page content.
   */
  public function testCreateShowCasePage() {
    // Mark test content for deletion after the test has finished.
    $this->markEntityTypeForCleanup('node');
    $this->markEntityTypeForCleanup('paragraph');

    $assert_session = $this->assertSession();
    $this->drupalLogin($this->user);

    // Create a Showcase Page node through the UI.
    $this->drupalGet('node/add/oe_showcase_page');
    $page = $this->getSession()->getPage();

    // Set page title.
    $page->fillField('title[0][value]', 'OE Showcase Demo Page');

    // Set page description.
    $page->fillField('field_description[0][value]', 'Page demo description');

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

    // Add a listing item.
    $page->pressButton('Add Listing item');
    $page->fillField(
      'field_body[3][subform][field_oe_link][0][uri]',
      '<front>'
    );

    $page->fillField(
      'field_body[3][subform][field_oe_title][0][value]',
      'Home Page'
    );

    $page->fillField(
      'field_body[3][subform][field_oe_text_long][0][value]',
      'Listing item description'
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

    // Assert Listing item.
    $assert_session->pageTextContains('Home');
    $assert_session->pageTextContains('Listing item description');
  }

}
