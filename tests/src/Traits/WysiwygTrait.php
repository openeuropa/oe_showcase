<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase\Traits;

use Behat\Mink\Element\NodeElement;

/**
 * Helper methods for interacting with WYSIWYG editors.
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
  protected function getWysiwigTextFormat(NodeElement $field): ?string {
    $parent = $field->find('xpath', '/ancestor::div[@class and contains(concat(" ", normalize-space(@class), " "), " text-format-wrapper ")]');
    $this->assertNotNull($parent);
    $link = $parent->find('css', 'a.filter-help-item');
    $this->assertNotNull($link);

    return $link->getAttribute('data-filter-format');
  }

  /**
   * Presses the given WYSIWYG button.
   *
   * @param string $field
   *   The field label of the field to which the WYSIWYG editor is attached. For
   *   example 'Body'.
   * @param string $button
   *   The title of the button to click.
   */
  protected function pressWysiwygButton($field, $button): void {
    $wysiwyg = $this->getWysiwyg($field);
    $button_elements = $this->getSession()->getDriver()->find($wysiwyg->getXpath() . '//a[@title="' . $button . '"]');
    if (empty($button_elements)) {
      throw new \Exception("Could not find the '$button' button.");
    }
    if (count($button_elements) > 1) {
      throw new \Exception("Multiple '$button' buttons found in the editor.");
    }
    $button = reset($button_elements);
    $button->click();
  }

  /**
   * Enters the given text in the textarea of the specified WYSIWYG editor.
   *
   * If there is any text existing it will be replaced.
   *
   * @param string $field
   *   The field label of the field to which the WYSIWYG editor is attached. For
   *   example 'Body'.
   * @param string $text
   *   The text to enter in the textarea.
   */
  protected function setWysiwygText($field, $text): void {
    $wysiwyg = $this->getWysiwyg($field);
    $textarea_elements = $this->getSession()->getDriver()->find($wysiwyg->getXpath() . '//textarea');
    if (empty($textarea_elements)) {
      throw new \Exception("Could not find the textarea for the '$field' field.");
    }
    if (count($textarea_elements) > 1) {
      throw new \Exception("Multiple textareas found for '$field'.");
    }
    $textarea = reset($textarea_elements);
    $textarea->setValue($text);
  }

  /**
   * Returns the WYSIWYG editor that is associated with the given field label.
   *
   * This is hardcoded on the CKE editor which is included with Drupal core.
   *
   * @param string $field
   *   The label of the field to which the WYSIWYG editor is attached.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The WYSIWYG editor.
   */
  protected function getWysiwyg($field): NodeElement {
    $driver = $this->getSession()->getDriver();
    $label_elements = $driver->find('//label[text()="' . $field . '"]');
    if (empty($label_elements)) {
      throw new \Exception("Could not find the '$field' field label.");
    }
    if (count($label_elements) > 1) {
      throw new \Exception("Multiple '$field' labels found in the page.");
    }
    $wysiwyg_id = 'cke_' . $label_elements[0]->getAttribute('for');
    $wysiwyg_elements = $driver->find('//div[@id="' . $wysiwyg_id . '"]');
    if (empty($wysiwyg_elements)) {
      throw new \Exception("Could not find the '$field' wysiwyg editor.");
    }
    if (count($wysiwyg_elements) > 1) {
      throw new \Exception("Multiple '$field' wysiwyg editors found in the page.");
    }
    return reset($wysiwyg_elements);
  }

  /**
   * Enters the given text in the given WYSIWYG editor.
   *
   * @param string $label
   *   The label of the field containing the WYSIWYG editor.
   * @param string $text
   *   The text to enter in the WYSIWYG editor.
   */
  protected function enterTextInWysiwyg(string $label, string $text): void {
    $this->pressWysiwygButton($label, 'Source');
    $this->setWysiwygText($label, $text);
    // Make sure we switch back to normal view and let javascript to
    // execute filters on the text and validate the html.
    $this->pressWysiwygButton($label, 'Source');
  }

}
