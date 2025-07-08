<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase\ExistingSiteJavascript;

/**
 * Functional JS test for Slim Select accessibility attributes.
 */
class MultiSelectAccessibleTest extends ShowcaseExistingSiteJavascriptTestBase {

  /**
   * Tests that Slim Select's ARIA attributes are present and valid.
   */
  public function testSlimSelectAriaAttributes(): void {
    $this->drupalGet('search', ['query' => ['search_api_fulltext' => 'autem']]);

    $page = $this->getSession()->getPage();

    $this->getSession()->wait(3000, "document.querySelector('.ss-main') !== null");

    $multiselect = $page->find('css', '.ss-main.multi-select');
    $this->assertNotNull($multiselect);

    $this->assertEquals('listbox', $multiselect->getAttribute('aria-haspopup'));
    $this->assertNotEmpty($multiselect->getAttribute('aria-controls'));

    $controls_id = $multiselect->getAttribute('aria-controls');
    $this->assertNotNull($page->find('css', '#' . $controls_id));

    $this->assertEquals('false', $multiselect->getAttribute('aria-expanded'));

    $active_id = $multiselect->getAttribute('aria-activedescendant');
    if (!empty($active_id)) {
      $active_option = $page->find('css', '#' . $active_id);
      $this->assertNotNull($active_option);
      $this->assertEquals('option', $active_option->getAttribute('role'));
    }
  }

}
