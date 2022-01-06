<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_user_profile\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\oe_showcase\Traits\AuthenticationTrait;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;

/**
 * Tests the User Profile feature integration.
 *
 * @group oe_showcase_user_profile
 */
class IntegrationTest extends BrowserTestBase {

  use AuthenticationTrait;
  use SparqlConnectionTrait;

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
    $this->setUpSparql();
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
    $page->fillField('Username', 'example_user');
    $page->fillField('Password', '123456');
    $page->fillField('Confirm password', '123456');
    $page->fillField('First Name', 'Exampleson');
    $page->fillField('Last Name', 'McModel');
    $page->fillField('Department', 'Development');
    $page->fillField('Organisation', 'DIGIT');
    $page->fillField('Bio', 'User description bio.');
    $page->fillField('Date', '2021-12-18');
    $page->fillField('Current position', 'Web Developer');

    $page->pressButton('Create new account');

    // Check field values.
    $user = user_load_by_name('example_user');
    $this->drupalGet("user/" . $user->id() . "/edit");
    $page = $this->getSession()->getPage();
    $this->assertSession()->statusCodeEquals(200);

    // Assert user information.
    $assert_session->fieldValueEquals('First Name', 'Exampleson');
    $assert_session->fieldValueEquals('Last Name', 'McModel');
    $assert_session->fieldValueEquals('Organisation', 'DIGIT');
    $assert_session->fieldValueEquals('Department', 'Development');
    $assert_session->fieldValueEquals('Bio', 'User description bio.');

    // Assert user is listed.
    $this->drupalGet("users");
    $this->assertSession()->statusCodeEquals(200);
    $assert_session->pageTextContains('Bio');
    $assert_session->pageTextContains('User description bio.');
    $assert_session->pageTextContains('2021-12-18');
    $assert_session->pageTextContains('Web Developer');
  }

}
