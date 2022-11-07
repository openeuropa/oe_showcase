<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\Traits;

use Behat\Mink\Element\NodeElement;

/**
 * Provides methods to deal with entity browsers.
 */
trait EntityBrowserTrait {

  /**
   * Fetches a media browser tile for a specific media.
   *
   * @param string $label
   *   The media label.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The media browser view tile.
   */
  protected function getMediaBrowserTileByMediaName(string $label): NodeElement {
    $xpath = $this->cssSelectToXpath('div.views-col div.views-field-name') . '//span[normalize-space(text())=:text]';
    $assert_session = $this->assertSession();

    return $assert_session->elementExists('xpath', $assert_session->buildXPathQuery($xpath, [':text' => $label]));
  }

}
