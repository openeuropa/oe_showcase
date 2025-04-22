<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

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

}
