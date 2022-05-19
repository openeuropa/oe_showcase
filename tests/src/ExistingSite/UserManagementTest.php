<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

use Drupal\Tests\oe_showcase\Traits\UserTrait;

/**
 * Tests that a privileged user can manage other users.
 */
class UserManagementTest extends ShowcaseExistingSiteTestBase {

  use UserTrait;

  /**
   * Manage users user can activate other users.
   */
  public function testUsersCanBeEnabled(): void {
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();

    $blocked_user = $this->createUser([], 'blocked_user');
    $blocked_user->block();
    $blocked_user->save();

    $user = $this->createUserWithRoles(['manage_users']);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/people');

    $this->checkUsersUpdateCheckbox('blocked_user');
    $page->selectFieldOption('Action', 'Unblock the selected user(s)');
    $page->pressButton('Apply to selected items');

    $assert->pageTextContains('Unblock the selected user(s) was applied to 1 item.');

    $blocked_user = $this->getUserByName('blocked_user');
    $this->assertTrue($blocked_user->isActive());
  }

  /**
   * Finds the update checkbox in the users row and checks it.
   *
   * @param string $username
   *   The users machine name.
   */
  protected function checkUsersUpdateCheckbox(string $username): void {
    $link = $this->getSession()->getPage()->findLink($username);
    // The link resides in a td.
    $row = $link->getParent()->getParent();
    $row->checkField('Update the user');
  }

}
