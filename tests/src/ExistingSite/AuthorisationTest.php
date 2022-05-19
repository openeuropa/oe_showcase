<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

use Drupal\Tests\oe_showcase\Traits\UserTrait;

/**
 * Tests that users can only access pages they are authorized to.
 */
class AuthorisationTest extends ShowcaseExistingSiteTestBase {

  use UserTrait;

  /**
   * Anonymous user cannot access restricted pages.
   */
  public function testAnonymousAccess(): void {
    $paths = [
      '/admin',
      '/admin/config',
      '/admin/content',
      '/admin/people',
      '/admin/structure',
      '/node/add',
    ];

    foreach ($paths as $path) {
      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals(403);
    }
  }

  /**
   * Authenticated user cannot access restricted pages.
   */
  public function testAuthenticatedAccess(): void {
    $paths = [
      '/admin',
      '/admin/config',
      '/admin/content',
      '/admin/people',
      '/admin/structure',
      '/node/add',
    ];

    foreach ($paths as $path) {
      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals(403);
    }
  }

  /**
   * Manage users user cannot access restricted pages.
   */
  public function testManageUsersAccess(): void {
    $user = $this->createUserWithRoles(['manage_users']);
    $this->drupalLogin($user);

    $paths = [
      '/admin/people/roles',
      '/admin/people/role-settings',
      '/admin/people/roleassign',
    ];

    foreach ($paths as $path) {
      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals(403);
    }
  }

  /**
   * Users with the "Manage users" role can assign a limited set of roles.
   */
  public function testManageUsersRoleAssign(): void {
    $user = $this->createUserWithRoles(['manage_users']);
    $this->drupalLogin($user);

    // Test roles availability in the user listing page.
    $this->drupalGet('/admin/people');
    $this->assertRoleAvailableInAction(['Editor']);
    $this->assertRoleNotAvailableInAction(['Manage users']);

    // Test roles availability in the user import page.
    $this->drupalGet('/admin/people/create/cas-bulk');
    $this->assertRoleOptionsAvailable(['Editor']);
    $this->assertRoleOptionsNotAvailable(['Manage users']);
  }

  /**
   * Users without the "Manage users" role cannot edit user accounts.
   */
  public function testOtherRolesCannotManageUsers(): void {
    $user = $this->createUserWithRoles(['editor']);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/people');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet("user/{$user->id()}/edit");
    $this->assertRoleOptionsNotAvailable([
      'Editor',
      'Manage users',
    ]);
  }

  /**
   * Users cannot manage permissions.
   */
  public function testPermissionsPageIsRestricted(): void {
    $assert = $this->assertSession();

    $this->drupalLogin($this->createUser(['administer permissions']));

    $this->drupalGet('/admin/people/permissions');

    $assert->pageTextContains('Role management is disabled in OE Showcase. Roles and associated permissions are only changeable by users with Manage users role.');
    $assert->buttonNotExists('edit-submit');
    // Assert all checkboxes are disabled on permissions page.
    $this->assertCheckboxesDisabled();
  }

  /**
   * Roles cannot be deleted, edited.
   */
  public function testRolesManagementAccess(): void {
    $assert = $this->assertSession();
    $this->drupalLogin($this->createUser(['administer permissions']));

    $this->drupalGet('/admin/people/roles');
    $assert->statusCodeEquals(200);

    $assert->linkNotExistsExact('Edit');
    $assert->linkNotExistsExact('Delete');
  }

  /**
   * Asserts that the options provided are available in the 'Action' field.
   *
   * @param array $options
   *   The list of options.
   */
  protected function assertRoleAvailableInAction(array $options): void {
    $assert = $this->assertSession();

    foreach ($options as $option) {
      $assert->optionExists('Action', sprintf('Add the %s role to the selected user(s)', $option));
    }
  }

  /**
   * Asserts that the options provided aren't available in the 'Action' field.
   *
   * @param array $options
   *   The list of options.
   */
  protected function assertRoleNotAvailableInAction(array $options): void {
    $assert = $this->assertSession();

    foreach ($options as $option) {
      $assert->optionNotExists('Action', sprintf('Add the %s role to the selected user(s)', $option));
    }
  }

  /**
   * Asserts which role options are available.
   *
   * @param array $options
   *   The list of options.
   */
  protected function assertRoleOptionsAvailable(array $options): void {
    $assert = $this->assertSession();

    foreach ($options as $option) {
      $field = $assert->fieldExists($option);

      $this->assertFalse($field->isChecked(), sprintf('%s role checkbox is disabled.', $option));
    }
  }

  /**
   * Asserts which role options are not available.
   *
   * @param array $options
   *   The list of options.
   */
  protected function assertRoleOptionsNotAvailable(array $options): void {
    $assert = $this->assertSession();

    foreach ($options as $option) {
      $assert->fieldNotExists($option);
    }
  }

  /**
   * Assert checkboxes are disabled.
   */
  protected function assertCheckboxesDisabled(): void {
    $checkboxes = $this->getSession()
      ->getPage()
      ->findAll('css', '.form-checkbox');

    foreach ($checkboxes as $checkbox) {
      $this->assertTrue($checkbox->hasAttribute('disabled'));
    }
  }

}
