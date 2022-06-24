<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_tests\Unit;

use Drupal\Tests\UnitTestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests code integrity.
 */
class IntegrityTest extends UnitTestCase {

  /**
   * Tests that regions are the same as in the base theme.
   *
   * The test forces an intentional decision when the regions are changed in the
   * base theme.
   */
  public function testRegions(): void {
    // Read whitelabel info.
    $whitelabel_info_file = dirname(__DIR__, 5) . '/build/themes/contrib/oe_whitelabel/oe_whitelabel.info.yml';
    $this->assertFileIsReadable($whitelabel_info_file);
    $whitelabel_info = Yaml::parseFile($whitelabel_info_file);

    // Read showcase theme info.
    $showcase_theme_info_file = \dirname(__DIR__, 3) . '/oe_showcase_theme.info.yml';
    $this->assertFileIsReadable($showcase_theme_info_file);
    $showcase_theme_info = Yaml::parseFile($showcase_theme_info_file);

    // Compare regions.
    $this->assertSame($whitelabel_info['regions'], $showcase_theme_info['regions']);
  }

}
