<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

use Drupal\Tests\oe_showcase\Traits\UserTrait;

/**
 * Tests that a manage_menu_items user can manage menus.
 */
class ManageMenuItemsTest extends ShowcaseExistingSiteTestBase {

  use UserTrait;

  /**
   * Test users with manage_menu_items role can manage menus.
   */
  public function testUsersWithManageSiteSpecificFooterRole(): void {
    $this->drupalLogin($this->createUserWithRoles(['manage_menu_items']));

    // Mark test content for deletion after the test has finished.
    $this->markEntityTypeForCleanup('menu');

    // Assert a basic menu creation.
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('admin/structure/menu/add');
    $page->fillField('Title', 'Example menu');
    $page->fillField('Menu name', 'example-menu');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Menu Example menu has been added.');

    $page->clickLink('Add link');
    $page->fillField('Menu link title', 'Link 1');
    $page->fillField('Link', 'https://europa.eu');
    $page->pressButton('Save');
    $assert_session->pageTextContains('The menu link has been saved.');
    $assert_session->pageTextContains('Link 1');
  }

}
