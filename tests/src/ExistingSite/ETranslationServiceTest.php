<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

use Drupal\node\Entity\Node;

/**
 * Functional test for the eTranslation integration.
 */
class ETranslationServiceTest extends ShowcaseExistingSiteTestBase {

  /**
   * Tests that TMGMT EC eTranslation provider exists.
   */
  public function testEtranslationService() {
    // Create editor user.
    $user = $this->createUser();
    $user->addRole('editor');
    $user->save();
    $this->drupalLogin($user);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('node/add/oe_showcase_page');
    $page->fillField('Title', 'Example Page');
    $page->pressButton('Save');
    $page->clickLink('Translate');
    $assert_session->pageTextContains('There are 0 items in the translation cart.');
    $assert_session->buttonExists('Add to cart');
    $assert_session->linkExists('translation cart');
    $page->checkField('languages[fr]');
    $page->pressButton('Request translation');
    $assert_session->elementExists('css', 'select[name="target_language"] option[selected][value="fr"]');
    $assert_session->elementExists('css', 'select[name="translator"] option[selected][value="ec_etranslation"]');
  }

  /**
   * Tests alias fallback for a node without a translation.
   *
   * This test creates a node in English, which generates an alias like
   * /en/example-title. It then asserts that visiting the same alias with a
   * different language prefix (ex. /fr/example-title) correctly loads the
   * original English content instead of returning a 404 error.
   */
  public function testAliasFallback() {
    $assert_session = $this->assertSession();

    // Mark test content for deletion after the test has finished.
    $this->markEntityTypeForCleanup('node');

    $node = Node::create([
      'type' => 'oe_showcase_page',
      'title' => 'Example Page title',
      'moderation_state' => 'published',
      'status' => 1,
    ]);
    $node->save();

    $this->drupalGet('node/' . $node->id());
    $assert_session->addressEquals('/en/example-page-title');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('Example Page title');

    $url_options = ['language' => \Drupal::languageManager()->getLanguage('fr')];
    $this->drupalGet('node/' . $node->id(), $url_options);
    $assert_session->addressEquals('/fr/example-page-title');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('Example Page title');
  }

}
