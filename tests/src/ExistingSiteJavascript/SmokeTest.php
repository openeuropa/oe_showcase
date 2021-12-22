<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSiteJavascript;

/**
 * Smoke test to verify that JavaScript tests are running. To be removed.
 */
class SmokeTest extends ShowcaseExistingSiteJavascriptTestBase {

  /**
   * Checks that the website has JavaScript executed.
   */
  public function testJavascriptIsInitialised(): void {
    $this->drupalGet('<front>');

    // Verify that the "js" class is added to the html element. This is done
    // through JavaScript.
    $this->assertSession()->elementExists('css', 'html.js');
  }

}
