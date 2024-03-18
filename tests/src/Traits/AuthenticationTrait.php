<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase\Traits;

/**
 * Provides methods for dealing with authentication in tests.
 *
 * @package Drupal\Tests\oe_showcase\Traits
 */
trait AuthenticationTrait {

  /**
   * Disables the forced login.
   */
  protected function disableForcedLogin(): void {
    \Drupal::configFactory()->getEditable('cas.settings')->set('forced_login.enabled', FALSE)->save();
  }

  /**
   * Enables the forced login.
   */
  protected function enableForcedLogin(): void {
    \Drupal::configFactory()
      ->getEditable('cas.settings')
      ->set('forced_login.enabled', TRUE)
      ->save();
  }

}
