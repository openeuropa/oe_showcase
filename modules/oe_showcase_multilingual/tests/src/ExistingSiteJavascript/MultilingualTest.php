<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_multilingual\ExistingSiteJavascript;

use Drupal\Tests\oe_showcase\ExistingSiteJavascript\ShowcaseExistingSiteJavascriptTestBase;

/**
 * Tests oe_showcase_multilingual translation is available.
 */
class MultilingualTest extends ShowcaseExistingSiteJavascriptTestBase {

  /**
   * Test node translation.
   */
  public function testOeMultilingualTranslation(): void {
    // Mark test content for deletion after the test has finished.
    $this->markEntityTypeForCleanup('node');

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->createUser([
      'create oe_showcase_page content',
      'delete any oe_showcase_page content',
      'delete own oe_showcase_page content',
      'edit own oe_showcase_page content',
      'translate oe_showcase_page node',
      'administer content translation',
      'create content translations',
      'delete content translations',
      'update content translations',
    ]));

    // Create a node and assert we can see all languages in the list but
    // no operations.
    $values = [
      'type' => 'oe_showcase_page',
      'title' => 'Test page',
      'status' => 1,
    ];
    $node = $this->createNode($values);
    $this->drupalGet('/node/' . $node->id() . '/translations');

    // Assert all 24 EU languages are available.
    $rows = $page->findAll('css', 'table tbody tr');
    $this->assertCount(24, $rows);
    $assert_session->linkNotExists('Translate locally');
    $this->assertCount(23, $page->findAll('xpath', '//a[text()=\'Add\']'));

    // Add a translation.
    $node->addTranslation('pt-pt', ['title' => 'Translated to PT'] + $node->toArray());
    $node->save();
    $this->drupalGet($node->toUrl());
    $this->clickLink('English');
    $modal = $assert_session->waitForElementVisible('xpath', '//div[@id=\'languageModal\']');
    $this->assertTrue($modal->isVisible());
    $this->clickLink('português');
    $assert_session->pageTextContains('Translated to PT');

    // Assert the language interface block is hidden.
    $modal = $page->find('xpath', '//div[@id=\'languageModal\']');
    $this->assertFalse($modal->isVisible());

    // Show the language switcher block by selecting an untranslated language.
    $this->clickLink('português');
    $this->assertSession()->waitForElementVisible('css', '#languageModal');
    $this->clickLink('français');
    $language_switcher_block = $page->find(
      'xpath',
      '//div[@id="block-oe-showcase-theme-content-language-switcher"]'
    );
    $this->assertTrue($language_switcher_block->isVisible());

    // Assert a valid translation is available.
    $button = $page->findButton('Choisir une autre langue');
    $button->click();
    $language_switcher_block->findLink('português')->click();
    $assert_session->pageTextContains('Translated to PT');
  }

}
