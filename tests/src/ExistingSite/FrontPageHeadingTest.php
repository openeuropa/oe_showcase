<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

/**
 * Class to test the presence of H1 on the front page.
 */
class FrontPageHeadingTest extends ShowcaseExistingSiteTestBase {

  /**
   * Asserts H1 on the front page.
   */
  public function testFrontPageHeading(): void {
    $this->drupalGet('<front>');
    $this->assertFrontPageH1();
  }

  /**
   * Asserts that an H1 with the class visually-hidden exists.
   */
  private function assertFrontPageH1(): void {
    $this->assertSession()->elementExists('css', 'h1.visually-hidden');

    $h1 = $this->getSession()->getPage()->find('css', 'h1.visually-hidden');
    $this->assertNotNull($h1);
  }

}
