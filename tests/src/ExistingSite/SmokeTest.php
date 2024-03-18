<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

/**
 * Smoke test to verify that tests are running. To be removed.
 */
class SmokeTest extends ShowcaseExistingSiteTestBase {

  /**
   * Checks that the website is installed and running.
   */
  public function testWebsiteIsUp(): void {
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
  }

}
