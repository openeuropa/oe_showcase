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
   * Tests the editor associated to a format.
   *
   * @param string $field_name
   *   The field name.
   * @param array $expected_buttons
   *   The expected buttons.
   *
   * @dataProvider formatEditorProvider
   */
  public function testFormatEditor(string $field_name, array $expected_buttons): void {
    $this->drupalLogin($this->createUserWithRoles(['editor']));
    $this->drupalGet('node/add/oe_sc_news');
    $this->assertEquals($expected_buttons, $this->getButtonTitles($this->getWysiwyg($field_name)));
  }

  /**
   * Returns the titles of buttons present in a CKEditor 4 instance.
   *
   * @param \Behat\Mink\Element\NodeElement $wysiwyg
   *   The instance.
   *
   * @return array
   *   An array of titles.
   */
  protected function getButtonTitles(NodeElement $wysiwyg): array {
    $assert_session = $this->assertSession();
    return array_map(function (NodeElement $button) use ($assert_session) {
      return $assert_session->elementExists('css', '#' . $button->getAttribute('aria-labelledby'))->getHtml();
    }, $wysiwyg->findAll('css', 'a[title][aria-labelledby]'));
  }

  /**
   * Data provider for editor format tests.
   */
  public function formatEditorProvider(): \Generator {
    yield 'Simple rich text' => [
      'Introduction',
      [
        'Bold',
        'Italic',
        'Link',
        'Unlink',
        'Source',
      ],
    ];

    yield 'Rich text' => [
      'Content',
      [
        'Bold',
        'Italic',
        'Link',
        'Unlink',
        'Insert/Remove Bulleted List',
        'Insert/Remove Numbered List',
        'Block Quote',
        'Embed media',
        'Source',
      ],
    ];
  }

}
