<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_user_profile\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\oe_showcase\Traits\AuthenticationTrait;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the User Profile feature integration.
 *
 * @group oe_showcase_user_profile
 */
class IntegrationTest extends BrowserTestBase {

  use AuthenticationTrait;

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
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'config',
    'datetime',
    'image',
    'oe_showcase_user_profile',
    'path',
    'system',
    'user',
  ];
  
 /**
  * {@inheritdoc}
  */
  protected function setUp(): void {
    parent::setUp();
    // Disable EU Login.
    $this->disableForcedLogin();

    // Create user.
    $this->user = $this->drupalCreateUser([], NULL, TRUE);

    // Login.
    $this->drupalLogin($this->user);
  }

  /**
   * Tests that the Project page renders correctly.
   */
  public function testIntegration(): void {
    // Assert session.
    $assert_session = $this->assertSession();

    // Go to people management to create a new user.
    $this->drupalGet("admin/people/create");

    $page = $this->getSession()->getPage();

    // Fill user fields.
    $page->fillField('Email address', 'example@example.com');
    $page->fillField('Username', 'Example user');
    $page->fillField('Password', '123456');
    $page->fillField('Confirm password', '123456');
    $page->fillField('First Name', 'Exampleson');
    $page->fillField('Last Name', 'McModel');
    $page->fillField('Department', 'Cleaning Department');
    $page->fillField('Organisation', 'Cleaning Co.');
    $page->pressButton('Create new account');

    // Check field values.
    $page->clickLink('Example user');
    $this->assertSession()->statusCodeEquals(200);
  }

}
