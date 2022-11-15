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
   * Gets a WYSIWYG fields filter format.
   *
   * @param \Behat\Mink\Element\NodeElement $field
   *   The field to check.
   *
   * @return string|null
   *   The filter format if found.
   */
  protected function getWysiwigTextFormat(NodeElement $field) {
    $parent = $field->find('xpath', '/ancestor::div[@class and contains(concat(" ", normalize-space(@class), " "), " text-format-wrapper ")]');
    $this->assertNotNull($parent);
    $link = $parent->find('css', 'a.filter-help-item');
    $this->assertNotNull($link);

    return $link->getAttribute('data-filter-format');
  }

}
