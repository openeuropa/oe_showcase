<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase\Traits;

/**
 * Provides methods to check access to paths.
 */
trait AssertPathAccessTrait {

  /**
   * Asserts that only users with given role can visit given paths.
   *
   * Side effects:
   * - Creates user accounts.
   * - Changes login state.
   * - Changes currently visited page.
   *
   * @param string[] $paths
   *   Paths that are public to everybody.
   */
  protected function assertPathsArePublic(array $paths): void {
    // Test anonymous.
    if ($this->loggedInUser) {
      $this->drupalLogout();
    }
    $this->assertPathsResponseCode(200, $paths);

    // Test authenticated.
    $this->drupalLogin($this->createUser());
    $this->assertPathsResponseCode(200, $paths);
  }

  /**
   * Asserts that only users with given role can visit given paths.
   *
   * Side effects:
   * - Creates user accounts.
   * - Changes login state.
   * - Changes currently visited page.
   *
   * @param string[] $paths
   *   Paths that require a specific role.
   * @param string $role
   *   Exclusive role that grants access to the given paths.
   */
  protected function assertPathsRequireRole(array $paths, string $role): void {
    // Test anonymous.
    if ($this->loggedInUser) {
      $this->drupalLogout();
    }
    $this->assertPathsResponseCode(403, $paths);

    // Test users with other roles.
    $all_roles = array_keys(user_role_names(TRUE));
    $other_roles = array_diff(
      $all_roles,
      [$role, 'authenticated', 'administrator'],
    );
    $this->drupalLogin($this->createUserWithRoles($other_roles));
    $this->assertPathsResponseCode(403, $paths);

    // Test the specified role.
    $this->drupalLogin($this->createUserWithRoles([$role]));
    $this->assertPathsResponseCode(200, $paths);
  }

  /**
   * Asserts that neither anonymous nor logged-in users can visit given paths.
   *
   * Side effects:
   * - Creates user accounts.
   * - Changes login state.
   * - Changes currently visited page.
   *
   * @param string[] $paths
   *   The paths to test.
   */
  protected function assertPathsAccessForbidden(array $paths): void {
    // Test anonymous.
    if ($this->loggedInUser) {
      $this->drupalLogout();
    }
    $this->assertPathsResponseCode(403, $paths);

    // Test all existing roles.
    $all_roles = user_role_names(TRUE);
    unset($all_roles['authenticated'], $all_roles['administrator']);
    $this->drupalLogin($this->createUserWithRoles(array_keys($all_roles)));
    $this->assertPathsResponseCode(403, $paths);
  }

  /**
   * Visits a number of paths and asserts the response code.
   *
   * Side effect: Current path/page will change.
   *
   * @param int $expected_response_code
   *   Expected response code, e.g. 403 or 304.
   * @param string[] $paths
   *   Paths to check.
   */
  protected function assertPathsResponseCode(int $expected_response_code, array $paths): void {
    foreach ($paths as $path) {
      $this->drupalGet($path);
      $code = $this->getSession()->getStatusCode();
      $this->assertSame(
        $expected_response_code,
        $code,
        sprintf('Status code on %s.', $path),
      );
    }
  }

}
