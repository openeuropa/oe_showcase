<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase\ExistingSiteJavascript;

use Drupal\Tests\oe_showcase\Traits\UserTrait;

/**
 * Test ai plugins.
 */
class AiPluginsTest extends ShowcaseExistingSiteJavascriptTestBase {

  use UserTrait;

  /**
   * Test AI content plugins.
   */
  public function testContentPlugins(): void {
    // Test for editor role.
    $user = $this->createUser([]);
    $user->addRole('editor');
    $user->save();
    $this->drupalLogin($user);
    // Test editoria11y is enabled for editor role.
    $this->drupalGet('home');
    $this->assertSession()->waitForElement('css', 'ed11y-element-panel');
    $this->assertSession()->elementExists('css', 'ed11y-element-panel');

    // Test content suggestion plugins are available for editor role.
    $this->drupalGet('node/add/oe_sc_event');
    $this->assertSession()->pageTextContains('Alter tone');
    $this->assertSession()->pageTextContains('Evaluate Readability');
    $this->assertSession()->pageTextContains('Suggest title');
    $this->assertSession()->pageTextContains('Summarise text');
    $this->assertSession()->pageTextContains('Suggest taxonomy tags');
    $this->assertSession()->pageTextContains('AI Assistant');

    // Test editor rol has access to the reports.
    $this->drupalGet('/admin/reports/editoria11y');
    $page_title = $this->getSession()->getPage()->find('css', 'h1.page-title')->getText();
    $this->assertEquals('Content Accessibility Issues', $page_title);

    // Test for non editor roles.
    $user->addRole('configure_page_feedback_form');
    $user->addRole('manage_contact_forms');
    $user->addRole('manage_menu_items');
    $user->addRole('manage_site_specific_footer');
    $user->removeRole('editor');
    $user->save();
    $this->drupalLogin($user);

    // Test editoria11y is disabled for non editor roles.
    $this->drupalGet('home');
    $this->assertSession()->elementNotExists('css', 'ed11y-element-panel');

    // Test editor rol has access to the reports.
    $this->drupalGet('/admin/reports/editoria11y');
    $this->assertSession()->pageTextContains('You are not authorized to access this page.');
  }

}
