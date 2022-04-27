<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

use Drupal\Tests\oe_showcase\Traits\AuthenticationTrait;
use Drupal\Tests\oe_showcase\Traits\ConfigurationBackupTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests the Page feedback form block.
 */
class PageFeedbackFormTest extends ExistingSiteBase {

  use AuthenticationTrait;
  use ConfigurationBackupTrait;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->disableForcedLogin();
    $this->backupSimpleConfig('oe_webtools_page_feedback.settings');
    $this->languageManager = $this->container->get('language_manager');
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
    $this->assertSession()->elementExists('xpath', "//script[@type='application/json'][.='" . addcslashes('{"service":"dff","id":1234,"lang":"en"}', '\\\'') . "']");
    $page->clickLink('English');
    $page->clickLink('português');
    $this->assertSession()->elementExists('xpath', "//script[@type='application/json'][.='" . addcslashes('{"service":"dff","id":1234,"lang":"pt"}', '\\\'') . "']");
    // Assert that Page feedback form is placed before the footer
    // inside footer region.
    $page_feedback_selector = '//footer[contains(concat(\' \', normalize-space(@class), \' \'), \' bcl-footer \')]/parent::div[1]/preceding-sibling::div[1]';
    $page_feedback = $this->getSession()->getPage()->find('xpath', $page_feedback_selector);
    $this->assertEquals('block-showcase-pagefeedbackform', $page_feedback->getAttribute('id'));

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
