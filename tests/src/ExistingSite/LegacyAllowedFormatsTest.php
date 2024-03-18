<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\Tests\oe_showcase\Traits\TraversingTrait;
use Drupal\Tests\oe_showcase\Traits\UserTrait;

/**
 * Tests legacy allowed formats.
 */
class LegacyAllowedFormatsTest extends ShowcaseExistingSiteTestBase {

  use TraversingTrait;
  use UserTrait;

  /**
   * Tests that the correct formats are forced when re-editing a timeline item.
   */
  public function testTimelineItemTextFormat(): void {
    $paragraph = Paragraph::create([
      'type' => 'oe_timeline',
      'field_oe_timeline' => [
        [
          'label' => 'Label 1',
          'title' => 'Title 1',
          'body' => 'Description 1',
          'format' => 'rich_text',
        ],
      ],
    ]);
    $paragraph->save();
    $this->markEntityForCleanup($paragraph);
    $node = $this->createPageWithParagraph($paragraph);

    $this->drupalLogin($this->createUserWithRoles(['editor']));
    $this->drupalGet($node->toUrl('edit-form'));
    $assert_session = $this->assertSession();
    $subform = $assert_session->elementExists('css', '[data-drupal-selector="edit-field-body-0-subform-field-oe-timeline"]');
    $text_format = $assert_session->selectExists('Text format', $subform);
    // The "rich text" format is selected and available.
    $this->assertEquals([
      'simple_rich_text' => 'Simple rich text',
      'rich_text' => 'Rich text',
    ], $this->getSelectOptions($text_format));
    $this->assertEquals('rich_text', $text_format->getValue());
    $assert_session->buttonExists('Save')->press();
    $assert_session->statusMessageContains('The field Content supports only the Simple rich text format.', 'error');
    $text_format->setValue('simple_rich_text');
    $assert_session->buttonExists('Save')->press();
    $assert_session->pageTextContains(sprintf('Page %s has been updated.', $node->label()));

    // Test that only one format is allowed for new paragraphs.
    $paragraph = Paragraph::create([
      'type' => 'oe_timeline',
      'field_oe_timeline' => [],
    ]);
    $paragraph->save();
    $this->markEntityForCleanup($paragraph);
    $node = $this->createPageWithParagraph($paragraph);
    $this->drupalGet($node->toUrl('edit-form'));
    $assert_session->fieldNotExists('Text format', $subform);
    $this->assertTrue($subform->hasLink('About the Simple rich text format'));
  }

  /**
   * Tests the rich text paragraph body format.
   */
  public function testRichTextParagraph(): void {
    $paragraph = Paragraph::create([
      'type' => 'oe_rich_text',
      'field_oe_text_long' => [
        [
          'value' => 'Paragraph content',
          'format' => 'simple_rich_text',
        ],
      ],
    ]);
    $paragraph->save();
    $this->markEntityForCleanup($paragraph);
    $node = $this->createPageWithParagraph($paragraph);

    $this->drupalLogin($this->createUserWithRoles(['editor']));
    $this->drupalGet($node->toUrl('edit-form'));
    $assert_session = $this->assertSession();
    $subform = $assert_session->elementExists('css', '[data-drupal-selector="edit-field-body-0-subform-field-oe-text-long-wrapper"]');
    $text_format = $assert_session->selectExists('Text format', $subform);
    // The "rich text" format is selected and available.
    $this->assertEquals([
      'simple_rich_text' => 'Simple rich text',
      'rich_text' => 'Rich text',
    ], $this->getSelectOptions($text_format));
    $this->assertEquals('simple_rich_text', $text_format->getValue());
    $assert_session->buttonExists('Save')->press();
    $assert_session->statusMessageContains('The field Text supports only the Rich text format.', 'error');
    $text_format->setValue('rich_text');
    $assert_session->buttonExists('Save')->press();
    $assert_session->pageTextContains(sprintf('Page %s has been updated.', $node->label()));
  }

  /**
   * Creates a page that references the given paragraph entity.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity.
   *
   * @return \Drupal\node\NodeInterface
   *   The created page.
   */
  protected function createPageWithParagraph(ParagraphInterface $paragraph): NodeInterface {
    return $this->createNode([
      'type' => 'oe_showcase_page',
      'field_body' => [
        [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ],
      ],
    ]);
  }

}
