<?php

declare(strict_types=1);

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
    $slim_select_main = $field->getParent()->find('css', 'div.ss-main');
    $element_id = $field->find('css', '.form-select')->getAttribute('data-id');
    $slim_select_content = $field->getParent()->getParent()->getParent()->find('css', '#' . $element_id . '-content');
    if (!$multiple) {
      // Elements with no size are not interactable and can't be clicked, so we
      // need to modify delete divs inside the select and make them use space.
      // By default this elements have an 'x' as content with no size.
      $script = <<<JS
        var closeElements = document.querySelectorAll(".ss-main .ss-values .ss-value-delete");
        for (var i = 0, max = closeElements.length; i < max; i++) {
          closeElements[i].style.height = '10px';
          closeElements[i].style.width = '10px';
        }
      JS;
      $this->getSession()->executeScript($script);
      $items = $slim_select_main->findAll('css', '.ss-values .ss-value-delete');
      foreach ($items as $item) {
        $item->click();
      }
    }

    $slim_select_main->click();
    // Slim Select has minor delay in opening and closing the dialog so add a
    // small delay to ensure that tests do not break due to fast automated
    // actions.
    $this->getSession()->wait(1000);
    // Asserts the dropdown to be open.
    $this->assertTrue($slim_select_content->find('css', '.ss-content[class*="ss-open-"]')->isVisible());
    // Asserts the search input to be visible.
    $slim_select_search = $slim_select_content->find('css', 'div.ss-search > input');
    $this->assertTrue($slim_select_search->isVisible());
    $slim_select_search->setValue($option);
    $this->getSession()->wait(1000);
    // Asserts the option to be in the search result.
    $option_element = $slim_select_content->find('xpath', '//div[contains(@class, "ss-option") and text()="' . $option . '"]');
    $this->assertTrue($option_element->isVisible());
    $option_element->click();
    $this->getSession()->wait(1000);
    // Asserts the option to be added to the list.
    $this->assertTrue($slim_select_main->find('xpath', '//div[@class = "ss-value-text" and text()="' . $option . '"]')->isVisible());
  }

}
