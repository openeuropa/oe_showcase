<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\oe_showcase\Traits\AuthenticationTrait;
use Drupal\Tests\oe_showcase\Traits\ConfigurationBackupTrait;
use Drupal\Tests\oe_showcase\Traits\EntityCleanupTrait;
use Drupal\Tests\oe_showcase\Traits\SocialShareBlockTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Base test class for all oe_showcase existing site tests.
 */
abstract class ShowcaseExistingSiteTestBase extends ExistingSiteBase {

  use AuthenticationTrait;
  use ConfigurationBackupTrait;
  use EntityCleanupTrait;
  use SocialShareBlockTrait;

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
   * Performs login with a retry mechanism to handle race conditions.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User object representing the user to log in.
   * @param int $max_attempts
   *   Maximum number of retry attempts.
   *
   * @throws \Exception
   */
  protected function drupalLogin(AccountInterface $account, int $max_attempts = 3) {
    for ($attempt = 1; $attempt <= $max_attempts; $attempt++) {
      try {
        parent::drupalLogin($account);
        return;
      }
      catch (\Exception $e) {
        if ($attempt === $max_attempts) {
          throw $e;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * Safe logout that doesn't fail if a user is already logged out.
   */
  protected function drupalLogout(): void {
    try {
      if ($this->loggedInUser) {
        parent::drupalLogout();
      }
    }
    catch (\Exception) {
      $this->drupalResetSession();
    }
  }

}
