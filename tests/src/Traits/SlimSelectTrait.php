<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\Traits;

use Behat\Mink\Element\NodeElement;

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
    $slim_select_search = $slim_select->find('css', 'div.ss-search > input');

    if (!$multiple) {
      $items = $slim_select->findAll('css', '.ss-value-delete');
      foreach ($items as $item) {
        $item->click();
      }
    }
    $slim_select->click();
    $this->assertSession()->waitForElementVisible('css', 'div.ss-search');
    $slim_select_search->setValue($option);
    $option_selector = '//div[contains(@class, "ss-option") and text()="' . $option . '"]';
    $this->assertSession()->waitForElementVisible('xpath', $option_selector);
    $slim_select->find('xpath', $option_selector)->click();
    $this->assertSession()->waitForElementVisible('xpath', '//div[contains(@class, "ss-value-text") and text()="' . $option . '"]');
  }

}
