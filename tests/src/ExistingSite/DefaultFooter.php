<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

/**
 * Test default footer options.
 */
class DefaultFooter extends ShowcaseExistingSiteTestBase {

  /**
   * Test ec-core column is removed.
   */
  public function testNoEcCoreColumn(): void {

    $this->drupalGet('');
    $assert = $this->assertSession();
    $this->assertSession()->elementTextNotContains('css', 'footer', 'EC Core column');
    $assert->linkByHrefNotExists('https://commission.europa.eu/about_en');
    $assert->linkByHrefNotExists('https://commission.europa.eu/about/contact_en');
    $assert->linkByHrefNotExists('https://commission.europa.eu/priorities-2024-2029_en');
    $assert->linkByHrefNotExists('https://commission.europa.eu/topics_en');
    $assert->linkByHrefNotExists('https://commission.europa.eu/funding-tenders_en');
    $assert->linkByHrefNotExists('https://commission.europa.eu/get-involved/jobs-european-commission_en');
    $assert->linkByHrefNotExists('https://ec.europa.eu/commission/presscorner/home/en');
    $assert->linkByHrefNotExists('https://commission.europa.eu/get-involved/events_en');
  }

}
