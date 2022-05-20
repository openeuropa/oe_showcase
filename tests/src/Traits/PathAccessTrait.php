<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\Traits;

/**
 * Provides methods to check access to paths.
 */
trait PathAccessTrait {

  /**
   * Visits a number of paths and asserts the response code.
   *
   * Side effect: Current path/page will change.
   *
   * @param int $expected_response_code
   *   Expected response code, e.g. 403 or 304.
   * @param string[] $paths
   *   Paths to check.
   */
  protected function assertPathsResponseCode(int $expected_response_code, array $paths): void {
    foreach ($paths as $path) {
      $this->drupalGet($path);
      $code = $this->getSession()->getStatusCode();
      $this->assertSame(
        $expected_response_code,
        $code,
        sprintf('Status code on %s.', $path),
      );
    }
  }

}
