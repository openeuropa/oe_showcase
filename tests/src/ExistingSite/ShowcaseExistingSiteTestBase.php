<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\oe_showcase\Traits\AuthenticationTrait;
use Drupal\Tests\oe_showcase\Traits\ConfigurationBackupTrait;
use Drupal\Tests\oe_showcase\Traits\EntityCleanupTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Base test class for all oe_showcase existing site tests.
 */
abstract class ShowcaseExistingSiteTestBase extends ExistingSiteBase {

  use AuthenticationTrait;
  use ConfigurationBackupTrait;
  use EntityCleanupTrait;

  /**
   * The status of the CAS forced login setting for the test.
   *
   * Set to TRUE to force user login through (mocked) EU Login.
   *
   * @var bool
   */
  protected $casForcedLogin = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    if (!$this->casForcedLogin) {
      $this->disableForcedLogin();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    // Restore the default status of forced login.
    $this->enableForcedLogin();
    $this->cleanupTestEntities();
    $this->restoreConfiguration();

    parent::tearDown();
  }

  /**
   * Asserts Social Share block.
   */
  protected function assertSocialShareBlock(): void {
    $main_content = $this->cssSelect('main > div.container')[0];
    $this->assertStringContainsString('Share this page', $main_content->getText());
    $social_share_config = [
      'service' => 'share',
      'version' => '2.0',
      'networks' => [
        'twitter',
        'facebook',
        'linkedin',
        'email',
        'more',
      ],
      'stats' => TRUE,
      'selection' => TRUE,
    ];
    $this->assertStringContainsString(Json::encode($social_share_config), $main_content->getHtml());
  }

}
