<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

use Drupal\Tests\oe_showcase\Traits\UserTrait;

/**
 * Tests that a manage_site_specific_footer user can manage footer links.
 */
class ManageSiteSpecificFooterTest extends ShowcaseExistingSiteTestBase {

  use UserTrait;

  /**
   * Test users with manage_site_specific_footer role can manage footer links.
   */
  public function testUsersWithManageSiteSpecificFooterRole(): void {
    $this->drupalLogin($this->createUserWithRoles(['manage_site_specific_footer']));

    // Mark test content for deletion after the test has finished.
    $this->markEntityTypeForCleanup('footer_link_section');
    $this->markEntityTypeForCleanup('footer_link_general');
    $this->markEntityTypeForCleanup('footer_link_social');

    // Assert a footer link section creation.
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('admin/config/footer_link_section/add');
    $page->fillField('Label', 'Example section');
    $page->fillField('Machine-readable name', 'example_section');
    $page->pressButton('Save');
    $assert_session->pageTextContains('The "Example section" footer link section has been created.');

    // Assert a footer link creation.
    $this->drupalGet('admin/config/footer_link_general/add');
    $page->fillField('Section', 'example_section');
    $page->fillField('Label', 'Footer Link 1');
    $page->fillField('Machine-readable name', 'footer_link_1');
    $page->fillField('URL', 'https://europa.eu');
    $page->pressButton('Save');
    $assert_session->pageTextContains('The "Footer Link 1" general footer link has been created.');
    $assert_session->pageTextContains('Footer Link 1');
    $assert_session->pageTextContains('https://europa.eu');

    // Assert a footer link social creation.
    $this->drupalGet('admin/config/footer_link_social/add');
    $page->fillField('Social network', 'twitter');
    $page->fillField('Label', 'Twitter Link 1');
    $page->fillField('Machine-readable name', 'twitter_link_1');
    $page->fillField('URL', 'https://twitter.com');
    $page->pressButton('Save');
    $assert_session->pageTextContains('The "Twitter link 1" social footer link has been created.');
    $assert_session->pageTextContains('Twitter Link 1');
    $assert_session->pageTextContains('https://twitter.com');
  }

}
