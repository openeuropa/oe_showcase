<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

use Drupal\Tests\oe_showcase\Traits\AuthenticationTrait;
use Drupal\Tests\oe_showcase\Traits\ConfigurationBackupTrait;

/**
 * Tests the Page feedback form block.
 */
class ShowcasePageFeedbackFormTest extends ShowcaseExistingSiteTestBase {

  use AuthenticationTrait;
  use ConfigurationBackupTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->backupSimpleConfig('oe_webtools_page_feedback.settings');
  }

  /**
   * Tests the Page Feedback Form Block configuration and rendering.
   */
  public function testPageFeedbackFormBlock(): void {
    $user = $this->createUser();
    $this->drupalLogin($user);
    // Assert the configuration form is available only for users with the
    // 'Configure Page Feedback form' role.
    $this->drupalGet('admin/config/system/oe_webtools_page_feedback');
    $this->assertSession()->pageTextContains('Access denied');
    $user->addRole('configure_page_feedback_form');
    $user->save();
    $this->drupalLogout();
    $this->drupalLogin($user);
    $this->drupalGet('admin/config/system/oe_webtools_page_feedback');
    $this->assertSession()->pageTextContains('Webtools Page Feedback Form settings');
    // The block is disabled by default.
    $this->assertSession()->checkboxNotChecked('Enabled');
    $this->assertSession()->fieldValueEquals('Form ID', '');

    // Enable the block.
    $page = $this->getSession()->getPage();
    $page->checkField('Enabled');
    $page->fillField('Form ID', '1234');
    $page->pressButton('Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->drupalLogout();

    // Assert the block is rendered.
    $this->drupalGet('<front>');
    $this->assertSession()->elementExists('xpath', "//script[@type='application/json'][.='" . addcslashes('{"service":"dff","id":"1234","lang":"en"}', '\\\'') . "']");
    $page->clickLink('English');
    $page->clickLink('português');
    $this->assertSession()->elementExists('xpath', "//script[@type='application/json'][.='" . addcslashes('{"service":"dff","id":"1234","lang":"pt"}', '\\\'') . "']");
    // Assert that Page feedback form is placed before the footer
    // inside footer region.
    $this->assertSession()->elementExists('css', '#block-oe-showcase-theme-page-feedback-form + #block-oe-showcase-theme-neutral-footer');

    // Disable the block and assert the block is not rendered.
    $this->drupalLogin($user);
    $this->drupalGet('admin/config/system/oe_webtools_page_feedback');
    $page->uncheckField('Enabled');
    $page->pressButton('Save configuration');
    $this->drupalLogout();
    $this->drupalGet('/index');
    $this->assertSession()->elementNotExists('xpath', "//script[@type='application/json'][.='" . addcslashes('{"service":"dff","id":1234,"lang":"en"}', '\\\'') . "']");
  }

}
