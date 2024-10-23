<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

/**
 * Class to test the presence of H1 on the front page.
 */
class FrontPageTest extends ShowcaseExistingSiteTestBase {

  /**
   * Asserts H1 on the front page.
   */
  public function testFrontPageHeading(): void {
    $this->drupalGet('<front>');
    $this->assertSession()->elementExists('css', 'h1.visually-hidden');
  }

}
