<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_user_profile\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure the user fields are created and visible.
 *
 * @group oe_showcase_user_profile
 */
class IntegrationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'bartik';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'oe_showcase';
  
  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'block',
    'config',
    'oe_showcase_user_profile',
    'datetime',
    'image',
    'path',
    'file',
  ];

  /**
   * Tests that the Project page renders correctly.
   */
  public function testIntegration(): void {
    $assert_session = $this->assertSession();
    // Log in as admin.
    $user = $this->drupalCreateUser([], '', TRUE);
    $this->drupalLogin($user);
    $this->drupalGet("admin/people/create");
    $page = $this->getSession()->getPage();
    // Fill user fields.
    $page->fillField('Email address', 'example@example.com');
    $page->fillField('Username', 'Example user');
    $page->fillField('Password', '123456');
    $page->fillField('Confirm password', '123456');
    $page->fillField('edit-field-date-of-birth-0-value-date', '2000-10-10');
    $page->fillField('Bio', 'Biography');
    $page->selectFieldOption('Country', 'Spain');
    $page->fillField('Current position', 'Example position');
    $page->pressButton('Create new account');
    // Check field values.
    $page->clickLink('Example user');
    $assert_session->pageTextContains('2000-10-10');
    $assert_session->pageTextContains('Biography');
    $assert_session->pageTextContains('Spain');
    $assert_session->pageTextContains('Example position');
  }

}
