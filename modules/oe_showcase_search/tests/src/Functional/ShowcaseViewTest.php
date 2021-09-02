<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_search\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Ensure Search API server and indexes are available once module is enabled.
 *
 * @group oe_showcase_search
 */
class ShowcaseViewTest extends BrowserTestBase {
  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'oe_showcase_search',
    'oe_showcase_default_content',
    'search_api',
    'search_api_db',
    'system',
    'user',
    'views',
    'views_ui',
  ];

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'oe_showcase';
  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;
  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * @var bool
   *
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Tests the view exists and has a block display.
   */
  public function testShowCaseView() {
    $admin_user = $this->drupalCreateUser(['administer views']);

    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/structure/views/view/showcase_search');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('admin/structure/views/view/showcase_search/edit/block_1');
    $this->assertSession()->statusCodeEquals(200);
  }

}
