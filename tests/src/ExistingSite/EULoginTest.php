<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

use Drupal\Tests\oe_showcase\Traits\UserTrait;

/**
 * Tests the EU Login functionality.
 */
class EULoginTest extends ShowcaseExistingSiteTestBase {

  use UserTrait;

  /**
   * {@inheritdoc}
   */
  protected $casForcedLogin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->get('cas_mock_server.server_manager')
      ->start();

    $this->container->get('cas_mock_server.user_manager')
      ->addUser([
        'username' => 'chucknorris',
        'email' => 'texasranger@chucknorris.com.eu',
        'password' => 'Qwerty098',
        'firstName' => 'Chuck',
        'lastName' => 'Norris',
        'domain' => 'eu.europa.ec',
      ]);

    $this->markEntityTypeForCleanup('user');
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    $this->container->get('cas_mock_server.user_manager')
      ->deleteUsers(['chucknorris']);

    $this->container->get('cas_mock_server.server_manager')
      ->stop();

    parent::tearDown();
  }

  /**
   * Users should end up on EU login when trying to log in.
   */
  public function testEuLoginIsEnforced(): void {
    $paths = [
      '/user/login',
      '/eulogin',
    ];

    foreach ($paths as $path) {
      $this->drupalGet($path);
      $this->assertSession()->buttonExists('Login!');
    }
  }

  /**
   * Login/Logout with the EU Login mock server for internal users.
   */
  public function testLoginLogoutBehaviour(): void {
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();

    $this->drupalGet('/user/login');
    $page->fillField('Username or e-mail address', 'texasranger@chucknorris.com.eu');
    $page->fillField('Password', 'Qwerty098');
    $page->pressButton('Login!');

    // Redirected back to Drupal and user is blocked.
    $assert->pageTextContains('Your account is blocked or has not been activated. Please contact a site administrator.');
    $assert->pageTextNotContains('You have been logged in.');
    $this->assertUserAccountNotActive('chucknorris');
    $this->assertIsHomepage();

    // After activating the user the login proceeds normally.
    $this->activateUser('chucknorris');

    $this->drupalGet('/user/login');
    $page->fillField('Username or e-mail address', 'texasranger@chucknorris.com.eu');
    $page->fillField('Password', 'Qwerty098');
    $page->pressButton('Login!');
    // Redirected back to Drupal and user is logged in.
    $assert->pageTextContains('You have been logged in.');

    // Profile contains extra fields.
    $this->drupalGet('/user');
    $page->clickLink('Edit');

    $assert->fieldValueEquals('First name', 'Chuck');
    $assert->fieldValueEquals('Last name', 'NORRIS');
    $assert->fieldValueEquals('Organization', 'eu.europa.ec');

    // Logout.
    $this->drupalGet('/user/logout');
    $page->pressButton('Log me out');

    // Redirected back to Drupal.
    $this->assertIsHomepage();
  }

  /**
   * Asserts the current address is the homepage.
   */
  protected function assertIsHomepage(): void {
    $this->assertSession()->addressEquals('/en/home');
  }

  /**
   * Activates a user given its username.
   *
   * @var string $username
   *   The name of the user to be blocked.
   */
  protected function activateUser(string $username): void {
    $user = $this->getUserByName($username);
    $this->assertNotNull($user);
    $user->activate();
    $user->save();
  }

  /**
   * Asserts that a given user is not active.
   *
   * @param string $username
   *   The username.
   */
  protected function assertUserAccountNotActive(string $username): void {
    $user = $this->getUserByName($username);
    $this->assertTrue($user->isBlocked());
  }

}
