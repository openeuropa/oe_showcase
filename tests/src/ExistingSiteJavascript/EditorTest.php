<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase\ExistingSiteJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\Tests\oe_showcase\Traits\UserTrait;
use Drupal\Tests\oe_showcase\Traits\WysiwygTrait;

/**
 * Tests editor configurations.
 */
class EditorTest extends ShowcaseExistingSiteJavascriptTestBase {

  use UserTrait;
  use WysiwygTrait;

  /**
   * Tests the buttons available in the "simple rich text" editor format.
   */
  public function testSimpleRichTextEditor(): void {
    $this->drupalLogin($this->createUserWithRoles(['editor']));
    $this->drupalGet('node/add/oe_sc_news');

    $introduction = $this->getWysiwyg('Introduction');
    $buttons = $introduction->findAll('css', 'a[title][aria-labelledby]');
    $assert_session = $this->assertSession();
    $titles = array_map(function (NodeElement $button) use ($assert_session) {
      return $assert_session->elementExists('css', '#' . $button->getAttribute('aria-labelledby'))->getHtml();
    }, $buttons);

    $this->assertEquals([
      'Bold',
      'Italic',
      'Link',
      'Unlink',
      'Source',
    ], $titles);
  }

}
