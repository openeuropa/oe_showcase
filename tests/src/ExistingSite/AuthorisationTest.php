<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

use Drupal\Tests\oe_showcase\Traits\AssertPathAccessTrait;
use Drupal\Tests\oe_showcase\Traits\UserTrait;

/**
 * Tests that users can only access pages they are authorized to.
 */
class AuthorisationTest extends ShowcaseExistingSiteTestBase {

  use AssertPathAccessTrait;
  use UserTrait;

  /**
   * Tests access to node create/view/edit/delete pages.
   */
  public function testManageContentPages(): void {
    $content_types = [
      'oe_project',
      'oe_sc_event',
      'oe_sc_news',
      'oe_sc_person',
      'oe_showcase_page',
    ];

    $public_paths = [];
    $restricted_paths = [];
    foreach ($content_types as $content_type) {
      $node = $this->createNode([
        'type' => $content_type,
        'title' => 'Stub ' . $content_type . ' node',
        'oe_content_content_owner' => 'http://publications.europa.eu/resource/authority/corporate-body/AGRI',
      ]);
      $revision_id = $node->getRevisionId();
      // Update the node to create a new revision.
      $node->setNewRevision();
      $node->save();
      $this->assertNotEquals($revision_id, $node->getRevisionId());
      $public_paths[] = 'node/' . $node->id();
      $restricted_paths[] = 'node/add/' . $content_type;
      $restricted_paths[] = 'node/' . $node->id() . '/edit';
      $restricted_paths[] = 'node/' . $node->id() . '/delete';
      $restricted_paths[] = 'node/' . $node->id() . '/revisions';
      $restricted_paths[] = 'node/' . $node->id() . '/revisions/' . $revision_id . '/revert';
      $restricted_paths[] = 'node/' . $node->id() . '/revisions/' . $revision_id . '/delete';
    }

    $this->assertPathsArePublic($public_paths);
    $this->assertPathsRequireRole($restricted_paths, 'editor');
  }

  /**
   * Anonymous user cannot access restricted pages.
   */
  public function testAnonymousAccess(): void {
    $paths = [
      '/admin',
      '/admin/config',
      '/admin/content',
      '/admin/content/media',
      '/admin/people',
      '/admin/structure',
      '/node/add',
    ];

    foreach ($paths as $path) {
      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals(403);
    }

    $this->drupalGet('/patterns');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Authenticated user cannot access restricted pages.
   */
  public function testAuthenticatedAccess(): void {
    $this->drupalLogin($this->createUser());
    $paths = [
      '/admin',
      '/admin/config',
      '/admin/content',
      '/admin/content/media',
      '/admin/people',
      '/admin/structure',
      '/node/add',
    ];

    foreach ($paths as $path) {
      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals(403);
    }

    $this->drupalGet('/patterns');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests that the administrator can see restricted administration pages.
   */
  public function testAdministratorAccess(): void {
    $this->drupalLogin($this->createUserWithRoles(['administrator']));
    $paths = [
      '/admin',
      '/admin/appearance',
      '/admin/appearance/settings/oe_showcase_theme',
      '/admin/config',
      '/admin/config/development/configuration',
      '/admin/config/development/maintenance',
      '/admin/config/regional/language',
      '/admin/config/regional/settings',
      '/admin/config/system/site-information',
      '/admin/content',
      '/admin/content/media',
      '/admin/modules',
      '/admin/people',
      '/admin/people/permissions',
      '/admin/people/roles',
      '/admin/reports/status',
      '/admin/structure/block',
      '/admin/structure/block/manage/oe_showcase_theme_main_page_content',
      '/admin/structure/contact',
      '/admin/structure/contact/manage/example_contact_form',
      '/admin/structure/menu',
      '/admin/structure/menu/manage/main',
      '/admin/structure/oe_contact_type',
      '/admin/structure/paragraphs_type',
      '/admin/structure/paragraphs_type/oe_facts_figures',
      '/admin/structure/taxonomy',
      '/admin/structure/types',
      '/admin/structure/types/manage/oe_showcase_page',
      '/node/add',
      '/node/add/oe_showcase_page',
    ];

    foreach ($paths as $path) {
      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals(200);
    }
  }

  /**
   * Tests that manage_menu_items users can manage menu administration pages.
   */
  public function testManageMenuItemsAccess(): void {
    $this->drupalLogin($this->createUserWithRoles(['manage_menu_items']));
    $paths = [
      '/admin',
      '/admin/structure',
      '/admin/structure/menu',
      '/admin/structure/menu/add',
      '/admin/structure/menu/manage/main',
      'admin/structure/menu/manage/main/add',
    ];
    foreach ($paths as $path) {
      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals(200);
    }
  }

  /**
   * Tests that manage_menu_items users can manage menu administration pages.
   */
  public function testManageSiteSpecificFooterAccess(): void {
    $this->drupalLogin($this->createUserWithRoles(['manage_site_specific_footer']));
    $paths = [
      '/admin',
      '/admin/config',
      '/admin/config/footer_link_general',
      '/admin/config/footer_link_general/add',
      '/admin/config/footer_link_section',
      '/admin/config/footer_link_section/add',
      '/admin/config/footer_link_social',
      '/admin/config/footer_link_social/add',
    ];
    foreach ($paths as $path) {
      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals(200);
    }
  }

  /**
   * Manage users user cannot access restricted pages.
   */
  public function testManageUsersAccess(): void {
    $user = $this->createUserWithRoles(['manage_users']);
    $this->drupalLogin($user);

    $paths = [
      '/admin/people/role-settings',
      '/admin/people/roleassign',
      '/admin/people/roles',
      '/admin/people/roles/add',
      '/admin/people/permissions',
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
    $roles = [
      'Configure Page Feedback form',
      'Editor',
      'Manage contact forms',
      'Manage menu items',
      'Manage site specific footer',
    ];

    // Test roles availability in the user listing page.
    $this->drupalGet('/admin/people');
    $this->assertRoleAvailableInAction($roles);
    $this->assertRoleNotAvailableInAction(['Manage users']);

    // Test roles availability in the user import page.
    $this->drupalGet('/admin/people/create/cas-bulk');
    $this->assertRoleOptionsAvailable($roles);
    $this->assertRoleOptionsNotAvailable(['Manage users']);
  }

  /**
   * Users without the "Manage users" role cannot edit user accounts.
   */
  public function testOtherRolesCannotManageUsers(): void {
    $user = $this->createUserWithRoles([
      'configure_page_feedback_form',
      'editor',
      'manage_contact_forms',
      'manage_menu_items',
      'manage_site_specific_footer',
    ]);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/people');
    $this->assertSession()->statusCodeEquals(403);
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

    $assert->linkExists('Add role');
    $this->drupalGet('/admin/people/roles/add');
    $assert->statusCodeEquals(200);
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

    // Assert an arbitrary count to make sure checkboxes are there.
    $this->assertTrue(count($checkboxes) > 30);

    foreach ($checkboxes as $checkbox) {
      $this->assertTrue($checkbox->hasAttribute('disabled'));
    }
  }

}
