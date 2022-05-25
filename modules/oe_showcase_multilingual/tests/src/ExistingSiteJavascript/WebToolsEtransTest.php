<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_multilingual\ExistingSiteJavascript;

use Drupal\Tests\oe_showcase\ExistingSiteJavascript\ShowcaseExistingSiteJavascriptTestBase;

/**
 * Tests oe_webtools_etrans block is displaying properly.
 */
class WebToolsEtransTest extends ShowcaseExistingSiteJavascriptTestBase {

  /**
   * Test oe_webtools_etrans block in oe_showcase.
   */
  public function testWebToolsEtrans(): void {
    $this->markEntityTypeForCleanup('node');

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $user = $this->createUser([
      'create oe_showcase_page content',
      'delete own oe_showcase_page content',
      'edit own oe_showcase_page content',
    ]);
    $this->drupalLogin($user);

    // Assert Etrans is loaded for nodes.
    $current_language = 'en';
    $values = [
      'type' => 'oe_showcase_page',
      'title' => 'Test page',
      'body' => 'This profile is aimed to provide developers with a series of examples on how the OpenEuropa Library features are built and configured.',
      'status' => 1,
      'language' => $current_language,
    ];
    $node = $this->createNode($values);
    $this->drupalGet('/node/' . $node->id());

    $etrans_block = $page->find('css', 'div.page > div#block-openeuropa-webtools-etrans');
    $assert_session->waitForId('wtEtransRequester');
    $etrans_link = $etrans_block->find('css', 'div.etrans.wt.wt-etrans  > div#wtEtransRequester > a.wt-link');
    $this->assertEquals('Translate this page', $etrans_link->getText());
    $this->assertStringContainsString(
      '{"service":"etrans","languages":{"exclude":["' . $current_language . '"]},"renderAs":{"button":false,"icon":false,"link":true},"domain":"spd","delay":0,"renderTo":"webtools-etrans","include":"main, .bcl-header"}',
      $etrans_block->getHtml()
    );

    // Assert the current language is excluded after language switching.
    $current_language = 'es';
    $this->drupalGet('/es/node/' . $node->id());

    $etrans_block = $page->find('css', 'div.page > div#block-openeuropa-webtools-etrans');
    $assert_session->waitForId('wtEtransRequester');
    $etrans_link = $etrans_block->find('css', 'div.etrans.wt.wt-etrans  > div#wtEtransRequester > a.wt-link');
    $this->assertEquals('Traducir esta página', $etrans_link->getText());
    $this->assertStringContainsString(
      '{"service":"etrans","languages":{"exclude":["' . $current_language . '"]},"renderAs":{"button":false,"icon":false,"link":true},"domain":"spd","delay":0,"renderTo":"webtools-etrans","include":"main, .bcl-header"}',
      $etrans_block->getHtml()
    );

    // Assert Etrans is not visible outside of nodes.
    $this->drupalGet('/user/' . $user->id());
    $assert_session->elementNotExists('css', 'div#block-openeuropa-webtools-etrans');
  }

}
