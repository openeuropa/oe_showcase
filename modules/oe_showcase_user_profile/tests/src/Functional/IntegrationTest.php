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
    // Add an admin user.
    $user = $this->drupalCreateUser([], '', TRUE);
    $this->drupalLogin($user);
    $this->drupalGet("admin/people/create");
    $page = $this->getSession()->getPage();
    $page->fillField('Email address', 'example@example.com');
    $page->fillField('Username', 'Example user');
    $page->fillField('Password', '123456');
    $page->fillField('Confirm password', '123456');
    $page->pressButton('Create new account');
    $page->hasContent('Created a new user account for Example user.');
    $page->clickLink('Example user');
  }

}
