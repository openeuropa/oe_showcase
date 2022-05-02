<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

/**
 * Class to test Cookie Consent.
 */
class CookieConsentTest extends ShowcaseExistingSiteTestBase {

  /**
   * Asserts Cookie Consent.
   */
  public function testCookieConsent(): void {
    $this->drupalGet('<front>');
    $this->assertCookieConsent();

    $this->drupalGet('node');
    $this->assertCookieConsent();

    $this->drupalGet('node/1');
    $this->assertCookieConsent();
  }

  /**
   * Asserts Cookie Consent block.
   */
  private function assertCookieConsent(): void {
    $this->assertSession()->elementExists('css', 'script[type="application/json"]');
    $this->assertStringContainsString('<script type="application/json">{"utility":"cck"}</script>', $this->getSession()->getPage()->getHtml());
  }

}
