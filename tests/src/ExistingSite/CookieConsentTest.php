<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

/**
 * Class to test Cookie Consent existing.
 */
class CookieConsentTest extends ShowcaseExistingSiteTestBase {

  /**
   * Asserts Cookie Consent enabled.
   */
  public function testCookieConsent(): void {
    $this->drupalGet('<front>');
    $this->assertCookieConsentEnabled();

    $this->drupalGet('node');
    $this->assertCookieConsentEnabled();

    $this->drupalGet('node/1');
    $this->assertCookieConsentEnabled();
  }

}
