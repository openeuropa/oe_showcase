<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

/**
 * Test user can embed media.
 */
class EmbedMediaTest extends ShowcaseExistingSiteTestBase {

  /**
   * Test format rich_text_with_media can embed media.
   */
  public function testEmbedMedia(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Create user.
    $user = $this->createUser([
      'create oe_sc_news content',
      'use text format rich_text_with_media',
    ]);
    $this->drupalLogin($user);

    // Mark test content for deletion after the test has finished.
    $this->markEntityTypeForCleanup('node');

    $this->drupalGet('node/add/oe_sc_news');

    $page->fillField('Title', 'News with embedded media');
    $page->fillField('edit-body-0-value', '<p data-oembed="https://oembed.ec.europa.eu?url=https%3A//data.ec.europa.eu/ewp/media/36b24637-901a-46df-8e8d-b7bc0618a2fc%3Fview_mode%3Ddefault"><a href="https://data.ec.europa.eu/ewp/media/36b24637-901a-46df-8e8d-b7bc0618a2fc">Euro with miniature figurines</a></p>');
    $page->pressButton('Save');

    $assert_session->responseContains('Euro with miniature figurines');
  }

}
