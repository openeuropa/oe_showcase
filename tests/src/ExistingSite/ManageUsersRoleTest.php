<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

/**
 * Tests the Manage users Role.
 */
class ManageUsersRoleTest extends ShowcaseExistingSiteTestBase {

  /**
   * Test the "Manage users" role.
   */
  public function testManageUsersRole(): void {
    $page = $this->getSession()->getPage();
    $assertions = $this->assertSession();

    // Create users.
    // Create user manager with administer permissions.
    $user_manager_admin_permissions = $this->createUser(['administer permissions']);
    $user_manager_admin_permissions->addRole('manage_users');
    $user_manager_admin_permissions->save();
    // Create user manager.
    $user_manager = $this->createUser();
    $user_manager->addRole('manage_users');
    $user_manager->save();

    $this->drupalLogin($user_manager);
    $this->drupalGet('admin/people');

    // Assert user manager cannot access to
    // permissions, roles or role assign page.
    $access_manager = $this->container->get('access_manager');
    $this->assertFalse($access_manager->checkNamedRoute('user.admin_permissions', [], $user_manager));
    $this->assertFalse($access_manager->checkNamedRoute('entity.user_role.collection', [], $user_manager));
    $this->assertFalse($access_manager->checkNamedRoute('roleassign.settings', [], $user_manager));

    // Assert user manager can assign the 'Editor' role.
    $page->selectFieldOption('Action', 'Add the Editor role to the selected user(s)');
    $page->checkField('user_bulk_form[2]');
    $page->pressButton('Apply to selected items');
    $assertions->pageTextContains('Editor role to the selected user(s) was applied to 1 item.');
    // Assert user manager cannot assign or remove the 'Manage users' role.
    $assertions->optionNotExists('Action', 'Add the Manage users role to the selected user(s)');
    $assertions->optionNotExists('Action', 'Remove the Manage users role from the selected user(s)');

    // Assert the roles available on bulk import page for user manager.
    $this->drupalGet('admin/people/create/cas-bulk');
    $assertions->elementTextContains('css', 'div#edit-roles', 'Editor');
    $assertions->elementTextNotContains('css', 'div#edit-roles', 'Manage users');

    // Assert lock permissions for user manager with administer permission.
    $this->drupalLogin($user_manager_admin_permissions);
    $this->drupalGet('admin/people/permissions');
    $assertions->pageTextContains('Role management is disabled in OE Showcase.');
    $assertions->buttonNotExists('edit-submit');

    // Assert all checkboxes are disabled on permissions' page for
    // user manager with administer permission.
    $this->assertCheckboxesDisabled();

    // Assert new users needs to be approved upon registration.
    $this->drupalLogout();
    $this->drupalGet('user/register');
    dump($page->getHtml());
    $config_factory = $this->container->get('config.factory');
    $config = $config_factory->getEditable('user.settings');
    $this->assertEquals('visitors_admin_approval', $config->get('register'));
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
