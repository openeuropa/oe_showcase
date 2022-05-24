<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_multilingual\ExistingSiteJavascript;

use Drupal\Tests\oe_showcase\ExistingSiteJavascript\ShowcaseExistingSiteJavascriptTestBase;

/**
 * Tests oe_showcase_multilingual translation is available.
 */
class WebToolsEtransTest extends ShowcaseExistingSiteJavascriptTestBase {

  /**
   * Test node translation.
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

    // Assert WebToolsEtrans Block exists if node and is placed in the footer.
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

    // Assert excluded language after changing the current language.
    $current_language = 'es';
    $this->clickLink('English');
    $this->assertSession()->waitForElementVisible('css', '#languageModal');
    $this->clickLink('español');

    $etrans_block = $page->find('css', 'div.page > div#block-openeuropa-webtools-etrans');
    $assert_session->waitForId('wtEtransRequester');
    $etrans_link = $etrans_block->find('css', 'div.etrans.wt.wt-etrans  > div#wtEtransRequester > a.wt-link');
    $this->assertEquals('Traducir esta página', $etrans_link->getText());
    $this->assertStringContainsString(
      '{"service":"etrans","languages":{"exclude":["' . $current_language . '"]},"renderAs":{"button":false,"icon":false,"link":true},"domain":"spd","delay":0,"renderTo":"webtools-etrans","include":"main, .bcl-header"}',
      $etrans_block->getHtml()
    );

    // Assert WebToolsEtrans Block is not visible out of a node.
    $this->drupalGet('/user/' . $user->id());
    $assert_session->elementNotExists('css', 'div#block-openeuropa-webtools-etrans');
  }

}
