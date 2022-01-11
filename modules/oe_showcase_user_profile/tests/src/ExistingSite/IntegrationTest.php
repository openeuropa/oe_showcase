<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_user_profile\Functional;

use Drupal\Tests\oe_showcase\ExistingSite\ShowcaseExistingSiteTestBase;

/**
 * Tests the User Profile feature integration.
 *
 * @group oe_showcase_user_profile
 */
class IntegrationTest extends ShowcaseExistingSiteTestBase {

  /**
   * Tests the user profile feature.
   */
  public function testIntegration(): void {
    $admin = $this->createUser([], NULL, TRUE);
    $this->markEntityForCleanup($admin);
    $this->drupalLogin($admin);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('admin/people/create');

    $page->fillField('Email address', 'example@example.com');
    $page->fillField('Username', 'example_user');
    $page->fillField('Password', '123456');
    $page->fillField('Confirm password', '123456');
    $page->fillField('First Name', 'Exampleson');
    $page->fillField('Last Name', 'McModel');
    $page->fillField('Department', 'Development');
    $page->fillField('Organisation', 'DIGIT');
    $page->fillField('Bio', 'User description bio.');
    $page->fillField('Date', '1990-01-01');
    $page->fillField('Current position', 'Web Developer');
    $page->selectFieldOption('Gender', 'male');
    $page->selectFieldOption('Country', 'Belgium');
    $page->selectFieldOption('Nationality', 'France');
    $page->selectFieldOption('Working Languages', 'French');
    $page->pressButton('Create new account');

    /** @var \Drupal\user\Entity\User $user */
    $user = user_load_by_name('example_user');
    $this->markEntityForCleanup($user);
    $this->drupalGet("user/{$user->id()}/edit");

    $assert_session->fieldValueEquals('First Name', 'Exampleson');
    $assert_session->fieldValueEquals('Last Name', 'McModel');
    $assert_session->fieldValueEquals('Organisation', 'DIGIT');
    $assert_session->fieldValueEquals('Department', 'Development');
    $assert_session->fieldValueEquals('Bio', 'User description bio.');
    $assert_session->fieldValueEquals('Gender', 'http://publications.europa.eu/resource/authority/human-sex/MALE');
    $assert_session->fieldValueEquals('Country', 'BE');
    $assert_session->fieldValueEquals('Nationality', 'http://publications.europa.eu/resource/authority/country/FRA');
    $working_languages = $assert_session->fieldExists('Working Languages');
    $this->assertSame(['http://publications.europa.eu/resource/authority/language/FRA'], $working_languages->getValue());

    $this->drupalGet('users');
    $assert_session->pageTextContains('User description bio.');
    $assert_session->pageTextContains('male');
    $assert_session->pageTextContains('1990-01-01');
    $assert_session->pageTextContains('Web Developer');
    $assert_session->pageTextContains('Belgium');
    $assert_session->pageTextContains('France');
    $assert_session->pageTextContains('French');
  }

}
