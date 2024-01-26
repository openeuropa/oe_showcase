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
   * Determines if a test should fail on JavaScript console errors.
   *
   * @var bool
   */
  protected $failOnJavascriptConsoleErrors = TRUE;

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
   * Waits for the given time or until the given JS condition becomes TRUE.
   *
   * @param string $condition
   *   JS condition to wait until it becomes TRUE.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 10000.
   * @param string $message
   *   (optional) A message to display with the assertion. If left blank, a
   *   default message will be displayed.
   *
   * @see \Drupal\FunctionalJavascriptTests\WebDriverTestBase::assertJsCondition()
   */
  protected function assertJsCondition($condition, $timeout = 10000, $message = ''): void {
    $message = $message ?: "JavaScript condition met:\n" . $condition;
    $result = $this->getSession()->getDriver()->wait($timeout, $condition);
    $this->assertTrue($result, $message);
  }

  /**
   * Triggers a test failure if a JavaScript error was encountered.
   *
   * @throws \PHPUnit\Framework\AssertionFailedError
   *
   * @postCondition
   */
  protected function failOnJavaScriptErrors(): void {
    if ($this->failOnJavascriptConsoleErrors) {
      $errors = $this->getSession()->evaluateScript("JSON.parse(sessionStorage.getItem('js_testing_log_test.errors') || JSON.stringify([]))");
      if (!empty($errors)) {
        $this->fail(implode("\n", $errors));
      }
    }
  }

}
