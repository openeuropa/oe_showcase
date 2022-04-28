<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

/**
 * Class to test Cookie Consent existing.
 */
class ShowcaseExistingSiteCookieConsentTest extends ShowcaseExistingSiteTestBase {

  /**
   * Asserts Cookie Consent block.
   *
   * @param string $page
   *   A list of pages to check the cookie block exists.
   *
   * @dataProvider cookieConsentBlockPagesPathProvider
   */
  public function testCookieConsentBlock(string $page): void {
    $this->drupalGet($page);

    $this->assertCookieConsentBlock();
  }

  /**
   * Returns pages path to check cookie consent exists.
   */
  public function cookieConsentBlockPagesPathProvider(): array {
    return [
      [
        'front',
      ],
      [
        'node',
      ],
      [
        'node/1',
      ],
    ];
  }

}
