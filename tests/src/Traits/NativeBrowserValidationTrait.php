<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\Traits;

/**
 * Provides methods to handle the native browser validation.
 *
 * This trait is meant to be used in tests that extend
 * \Drupal\Tests\BrowserTestBase.
 */
trait NativeBrowserValidationTrait {

  /**
   * Disables the native browser validation for required fields.
   */
  protected function disableNativeBrowserRequiredFieldValidation() {
    $this->getSession()->executeScript("jQuery(':input[required]').prop('required', false);");
  }

}
