<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_multilingual\ExistingSiteJavascript;

use Drupal\Tests\oe_showcase\ExistingSite\ShowcaseExistingSiteTestBase;

/**
 * Tests oe_webtools_etrans block is displaying properly.
 */
class WebToolsEtransTest extends ShowcaseExistingSiteTestBase {

  /**
   * Test oe_webtools_etrans block in oe_showcase.
   */
  public function testWebToolsEtrans(): void {
    $assert_session = $this->assertSession();

    // Assert Etrans is loaded for nodes with no translations.
    $values = [
      'type' => 'oe_showcase_page',
      'title' => 'Test page',
      'body' => 'This profile is aimed to provide developers with a series of examples on how the OpenEuropa Library features are built and configured.',
      'status' => 1,
      'language' => 'en',
    ];
    $node = $this->createNode($values);
    $this->drupalGet($node->toUrl());

    $etrans_block = $assert_session->elementExists('css', 'main > div.container > div.row > div.col-12 > div#block-showcase-etrans');
    $this->assertStringContainsString(
      '{"service":"etrans","languages":{"exclude":["en"]},"renderAs":{"button":false,"icon":false,"link":true},"domain":"spd","delay":0,"renderTo":"webtools-etrans","include":"main, .bcl-header"}',
      $etrans_block->getHtml()
    );

    // Assert Etrans is not loaded for nodes with translations.
    $node_es = $node->addTranslation('es', $node->toArray());
    $node_es->setTitle('Spanish Translation');
    $node_es->save();
    $this->drupalGet($node->toUrl());
    $assert_session->elementNotExists('css', 'main > div.container > div.row > div.col-12 > div#block-showcase-etrans');

    // Assert Etrans is not loaded outside nodes.
    $user = $this->createUser();
    $this->drupalGet('/user/' . $user->id());
    $assert_session->elementNotExists('css', 'main > div.container > div.row > div.col-12 > div#block-showcase-etrans');
  }

}
