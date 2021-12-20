<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSiteJavascript;

use Drupal\Tests\oe_showcase\Traits\AuthenticationTrait;
use Drupal\Tests\oe_showcase\Traits\ConfigurationBackupTrait;
use Drupal\Tests\oe_showcase\Traits\EntityCleanupTrait;
use weitzman\DrupalTestTraits\ExistingSiteSelenium2DriverTestBase;

/**
 * Base class for all oe_showcase existing site tests that need JavaScript.
 */
abstract class ShowcaseExistingSiteJavascriptTestBase extends ExistingSiteSelenium2DriverTestBase {

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
  protected function setUp() {
    parent::setUp();

    if (!$this->casForcedLogin) {
      $this->disableForcedLogin();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    // Restore the default status of forced login.
    $this->enableForcedLogin();
    $this->cleanupTestEntities();
    $this->restoreConfiguration();

    parent::tearDown();
  }

}
