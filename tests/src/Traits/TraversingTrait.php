<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\Traits;

use Behat\Mink\Element\NodeElement;

/**
 * Helper methods to deal with traversing of page elements.
 */
trait TraversingTrait {

  /**
   * Retrieves the options of a select field.
   *
   * @param \Behat\Mink\Element\NodeElement $select
   *   The select element.
   *
   * @return array
   *   The options text keyed by option value.
   */
  protected function getSelectOptions(NodeElement $select): array {
    $options = [];
    foreach ($select->findAll('xpath', '//option') as $element) {
      /** @var \Behat\Mink\Element\NodeElement $element */
      $options[$element->getValue()] = trim($element->getText());
    }

    return $options;
  }

  /**
   * Searches for the parent of an element by attribute, limited by depth.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The element to start from.
   * @param string $attribute
   *   The parent attribute for comparison.
   * @param string $value
   *   The parent attribute value to compare to.
   * @param int $depth
   *   The maximum depth to limit to.
   *
   * @return \Behat\Mink\Element\NodeElement|void
   *   The parent element if found.
   */
  protected function getParentByAttribute(NodeElement $element, string $attribute, string $value, int $depth = 5) {
    $parent = $element->getParent();

    // Limit the depth to prevent infinite loop.
    for ($i = 0; $i < $depth; $i++) {
      if (!$parent instanceof NodeElement) {
        break;
      }
      if (strpos($parent->getAttribute($attribute), $value) !== FALSE) {
        return $parent;
      }

      $parent = $parent->getParent();
    }
  }

}
