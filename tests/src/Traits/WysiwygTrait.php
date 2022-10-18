<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\Traits;

use Behat\Mink\Element\NodeElement;

/**
 * Helper to assert WYSIWYG related elements.
 */
trait WysiwygTrait {

  use TraversingTrait;

  /**
   * Asserts a field has the expected WYSIWYG editor.
   *
   * @param \Behat\Mink\Element\NodeElement $field
   *   The field to check.
   * @param string $format
   *   The format.
   */
  protected function assertFieldHasWysiwyg(NodeElement $field, string $format): void {
    $parent = $this->getParentByAttribute($field, 'class', 'text-format-wrapper');
    $this->assertNotNull($parent);
    $field = $parent->find('xpath', "//input[@value=\"$format\"]");
    $this->assertNotNull($field);
  }

  /**
   * Asserts a field has the expected text format.
   *
   * @param \Behat\Mink\Element\NodeElement $field
   *   The field to check.
   * @param string $format
   *   The format.
   */
  protected function assertFieldHasFormat(NodeElement $field, string $format): void {
    $parent = $this->getParentByAttribute($field, 'class', 'text-format-wrapper');
    $this->assertNotNull($parent);
    $element = $parent->find('xpath', "//div[@data-drupal-format-id=\"$format\"]");
    $this->assertNotNull($element);
  }

}
