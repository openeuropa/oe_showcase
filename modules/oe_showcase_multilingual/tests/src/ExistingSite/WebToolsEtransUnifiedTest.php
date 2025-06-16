<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase_multilingual\ExistingSiteJavascript;

use Drupal\Tests\oe_showcase\ExistingSite\ShowcaseExistingSiteTestBase;

/**
 * Tests oe_webtools_etrans_unified block is displaying properly.
 */
class WebToolsEtransUnifiedTest extends ShowcaseExistingSiteTestBase {

  /**
   * Test oe_webtools_etrans block in oe_showcase.
   */
  public function testWebToolsEtransUnified(): void {
    $assert_session = $this->assertSession();

    $etrans_block_json = '{"service":"etrans","renderAs":{"icon":false,"link":false,"button":false},"languages":{"source":"en"},"config":{"targets":{"receiver":"#etrans-widget"}},"delay":0}';
    $link_text = 'Translate to French';
    $message = "French is available via eTranslation, the European Commission's machine translation service.";
    $url_options = ['language' => \Drupal::languageManager()->getLanguage('fr')];

    // Assert Etrans unified block is not loaded on the node's source page.
    $values = [
      'type' => 'oe_showcase_page',
      'title' => 'Test page',
      'body' => 'This profile is aimed to provide developers with a series of examples on how the OpenEuropa Library features are built and configured.',
      'status' => 1,
      'language' => 'en',
    ];
    $node = $this->createNode($values);
    $this->drupalGet($node->toUrl());
    $assert_session->elementNotExists('css', 'main > div.container > div.row > div.col-12 > div#block-oe-showcase-theme-etrans-unified');
    $assert_session->pageTextNotContains($message);
    $assert_session->linkNotExists($link_text);

    // Assert Etrans Unified block is not loaded on the local translation page.
    $node_es = $node->addTranslation('es', $node->toArray());
    $node_es->setTitle('Spanish Translation');
    $node_es->save();
    $this->drupalGet($node_es->toUrl());
    $assert_session->elementNotExists('css', 'main > div.container > div.row > div.col-12 > div#block-oe-showcase-theme-etrans-unified');
    $assert_session->pageTextNotContains($message);
    $assert_session->linkNotExists($link_text);

    // Assert Etrans Unified is loaded on the translation page without local
    // translation.
    $this->drupalGet($node->toUrl('canonical', $url_options));
    $etrans_block = $assert_session->elementExists('css', 'main > div.container > div.row > div.col-12 > div#block-oe-showcase-theme-etrans-unified');
    $this->assertStringContainsString(
      $etrans_block_json,
      $etrans_block->getHtml()
    );
    $assert_session->pageTextContains($message);
    $assert_session->linkExists($link_text);

    // Assert Etrans unified block is not loaded on the default language page,
    // outside nodes.
    $user = $this->createUser();
    $this->drupalGet($user->toUrl());
    $assert_session->elementNotExists('css', 'main > div.container > div.row > div.col-12 > div#block-oe-showcase-theme-etrans-unified');
    $assert_session->pageTextNotContains($message);
    $assert_session->linkNotExists($link_text);

    // Assert Etrans unified block is loaded on non-default language pages,
    // outside nodes.
    $this->drupalGet($user->toUrl('canonical', $url_options));
    $etrans_block = $assert_session->elementExists('css', 'main > div.container > div.row > div.col-12 > div#block-oe-showcase-theme-etrans-unified');
    $this->assertStringContainsString(
      $etrans_block_json,
      $etrans_block->getHtml()
    );
    $assert_session->pageTextContains($message);
    $assert_session->linkExists($link_text);
  }

}
