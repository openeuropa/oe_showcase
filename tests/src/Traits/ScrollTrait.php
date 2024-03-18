<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase\Traits;

/**
 * Contains methods to scroll the current page.
 */
trait ScrollTrait {

  /**
   * Scroll an element into the viewport.
   *
   * @param string $selector
   *   The css query selector.
   */
  protected function scrollIntoView(string $selector): void {
    $offset = $this->getSession()->getPage()->find('css', '[aria-label="Site administration toolbar"]')
      ? 80
      : 0;

    $js = <<<JS
  const el = document.querySelector('$selector');
  const box = el.getBoundingClientRect();
  const docElem = document.documentElement;
  window.scrollBy({top: box.top + window.scrollX - docElem.clientTop - $offset, left: 0, behavior: 'instant'})
JS;

    $this->getSession()->executeScript($js);
  }

}
