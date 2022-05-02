<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

use Drupal\Tests\oe_showcase\Traits\AuthenticationTrait;
use Drupal\Tests\oe_showcase\Traits\ConfigurationBackupTrait;
use Drupal\user\Entity\User;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests the Manage users Role.
 */
class ManageUsersRoleTest extends ExistingSiteBase {

  use AuthenticationTrait;
  use ConfigurationBackupTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->disableForcedLogin();
    $this->backupSimpleConfig('user.role.manage_users');
    $this->backupSimpleConfig('user.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    $this->enableForcedLogin();
    $this->restoreConfiguration();

    parent::tearDown();
  }

  /**
   * Test the "Manage users" role.
   */
  public function testManageUsersRole(): void {
    $page = $this->getSession()->getPage();
    $assertions = $this->assertSession();
    // Assert users without the "Manage users" role cannot edit user accounts.
    // Create test user.
    $user = $this->createUser();
    $this->drupalGet('admin/people');
    $assertions->pageTextContains('Access denied');

    // Assert the People administration page is available only for users with
    // the 'Manage users' role.
    $user_manager = $this->createUser();
    $user_manager->addRole('manage_users');
    $user_manager->save();
    $this->drupalLogin($user_manager);
    $this->drupalGet('admin/people');
    $assertions->pageTextContains('People');

    // Assure users with the "Manage users" role can assign
    // a limited set of roles.
    $roles = user_role_names(FALSE);
    $config = \Drupal::config('roleassign.settings');
    $assignable_roles = array_intersect_key(
      $roles, array_filter($config->get('roleassign_roles')));
    $this->assertCount(1, $assignable_roles);

    // Assure the manager can assign the Editor role.
    $this->drupalGet('admin/people');
    $page->selectFieldOption('edit-action', 'user_add_role_action.editor');
    $page->checkField('edit-user-bulk-form-0');
    $page->pressButton('Apply to selected items');
    $assertions->pageTextContains('Add the Editor role to the selected user(s) was applied to 1 item.');
    $user = User::load($user->id());
    $this->assertTrue(in_array('editor', $user->getRoles()));

    // Assure users with Manage users role cannot manage permissions or roles.
    $assertions->linkNotExists('Roles');
    $this->drupalGet('admin/people/roles');
    $assertions->pageTextContains('Access denied');
    $assertions->linkNotExists('Permissions');
    $this->drupalGet('admin/people/permissions');
    $assertions->pageTextContains('Access denied');

    // Assure users with Manage users role cannot change Role assign settings.
    $assertions->linkNotExists('Role assign');
    $this->drupalGet('admin/people/roleassign');
    $assertions->pageTextContains('Access denied');
  }

}
