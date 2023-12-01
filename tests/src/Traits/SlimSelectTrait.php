<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\Traits;

use Behat\Mink\Element\ElementInterface;
use Behat\Mink\Element\NodeElement;
use WebDriver\Key;

/**
 * Provides methods to work with slim_select form elements.
 */
trait SlimSelectTrait {

  /**
   * Selects a slim select option.
   *
   * @param \Behat\Mink\Element\NodeElement $field
   *   The form field.
   * @param string $option
   *   The option to select.
   * @param bool $multiple
   *   If old values should be kept.
   */
  protected function selectSlimOption(NodeElement $field, string $option, bool $multiple = FALSE): void {
    $slim_select = $field->getParent()->find('css', 'div.ss-main');
    if (!$multiple) {
      $items = $slim_select->findAll('css', '.ss-values .ss-value-delete');
      foreach ($items as $item) {
        $item->click();
      }
    }

    // Helper function that checks if an element is visible. It differs from
    // \Drupal\FunctionalJavascriptTests\JSWebAssert::waitForElementVisible()
    // as it allows to pass a different container than page.
    $page = $this->getSession()->getPage();
    $fn_is_visible = static function (string $selector, $locator, ElementInterface $container = NULL) use ($page) {
      $container ??= $page;
      return $page->waitFor(10, static function () use ($selector, $locator, $container) {
        $element = $container->find($selector, $locator);
        if (!empty($element) && $element->isVisible()) {
          return $element;
        }
        return NULL;
      });
    };

    $slim_select->click();
    // Wait for the dropdown to open.
    $this->assertNotEmpty($fn_is_visible('css', '.ss-content.ss-open', $slim_select));
    // Wait for the search input to be visible.
    $slim_select_search = $fn_is_visible('css', 'div.ss-search > input', $slim_select);
    $this->assertNotEmpty($slim_select_search);
    $slim_select_search->setValue($option);
    // Wait for the option to be visible.
    $option_element = $fn_is_visible('xpath', '//div[contains(@class, "ss-option") and text()="' . $option . '"]', $slim_select);
    $this->assertNotEmpty($option_element);
    $option_element->click();
    // Wait for the option to be added to the list.
    $this->assertNotEmpty($fn_is_visible('xpath', '//span[@class = "ss-value-text" and text()="' . $option . '"]', $slim_select));
    // Close the element.
    $slim_select->keyDown(Key::ESCAPE);
    $slim_select->keyUp(Key::ESCAPE);
  }

}
