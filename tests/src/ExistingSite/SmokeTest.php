<?php

declare(strict_types = 1);

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
    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(200);
    // Assert only one Block social share and that is inside the content region.
    $assert_session->elementsCount('css', 'div#block-showcase-socialshare', 1);
    $assert_session->elementExists('xpath', '//div[@class="container mt-5"]/*//div[@id="block-showcase-socialshare"]');
  }

}
