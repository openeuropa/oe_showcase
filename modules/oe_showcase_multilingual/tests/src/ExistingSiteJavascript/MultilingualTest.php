<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_multilingual\ExistingSiteJavascript;

use Drupal\Tests\oe_showcase\ExistingSiteJavascript\ShowcaseExistingSiteJavascriptTestBase;

/**
 * Tests oe_showcase_multilingual translation is available.
 */
class MultilingualTest extends ShowcaseExistingSiteJavascriptTestBase {

  /**
   * Create a page node and test language switcher block.
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

    // Go to the translation page, assert the enabled languages are present.
    $this->drupalGet('/node/' . $node->id() . '/translations');
    $rows = $page->findAll('css', 'table tbody tr');
    // Assert all 24 EU languages are available.
    $this->assertCount(24, $rows);
    $this->assertSession()->linkNotExists('Translate locally');
    // Assert translations can be added to each language.
    $this->assertCount(23, $page->findAll('xpath', '//a[text()=\'Add\']'));

    // Add a translation and assert that the values are present.
    $node->addTranslation('pt-pt', ['title' => 'Translated to PT'] + $node->toArray());
    $node->save();
    $this->drupalGet($node->toUrl());
    $this->clickLink('English');
    $this->getSession()->wait(10000);
    $modal = $page->find('xpath', '//div[@id=\'languageModal\']');
    $this->assertTrue($modal->isVisible());
    $this->clickLink('português');
    $assert_session->pageTextContains('Translated to PT');

    // Assert the language interface block is hidden.
    $modal = $page->find('xpath', '//div[@id=\'languageModal\']');
    $this->assertFalse($modal->isVisible());

    // Change the interface language to an untranslated item
    // to show the language switcher block.
    $this->clickLink('português');
    $this->getSession()->wait(10000);
    $this->clickLink('français');
    $language_switcher_block = $page->find('xpath', '//div[@id="block-openeuropa-content-language-switcher"]');
    $this->assertTrue($language_switcher_block->isVisible());

    // Click on one language to assert the correct values are in the page.
    $language_switcher_block->findLink('português')->click();
    $assert_session->pageTextContains('Translated to PT');

  }

}
