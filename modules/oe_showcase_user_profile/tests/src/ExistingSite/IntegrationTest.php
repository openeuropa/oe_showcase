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

    // Emulate a EU login created user.
    // @todo Use cas mock.
    $user = $this->createUser([], NULL, FALSE, [
      'mail' => 'example@example.com',
      'field_first_name' => 'John',
      'field_last_name' => 'Doe',
      'field_organization' => 'DIGIT',
    ]);
    $user->get('allowed_options')->setValue([
      'authenticated' => 'authenticated',
    ]);
    $user->get('visible_fields')->setValue([
      'field_bio' => 'field_bio',
      'field_city_country' => 'field_city_country',
      'field_current_position' => 'field_current_position',
      'field_date_of_birth' => 'field_date_of_birth',
      'field_first_name' => 'field_first_name',
      'field_gender' => 'field_gender',
      'field_last_name' => 'field_last_name',
      'field_nationality' => 'field_nationality',
      'field_organization' => 'field_organization',
      'field_profile_image' => 'field_profile_image',
      'field_working_languages' => 'field_working_languages',
    ]);
    $user->save();
    $this->markEntityForCleanup($user);

    $this->drupalGet("user/{$user->id()}/edit");
    $page->fillField('Bio', 'User description bio.');
    $page->fillField('Date', '1990-01-01');
    $page->fillField('Current position', 'Web Developer');
    $page->selectFieldOption('Gender', 'male');
    $page->fillField('Country', 'BE');
    $page->fillField('Nationality', 'France');
    $page->fillField('Working Languages', 'http://publications.europa.eu/resource/authority/language/FRA');
    $assert_session->buttonExists('Cancel account');
    $page->pressButton('Save');

    $this->drupalGet("user/{$user->id()}/edit");
    $assert_session->fieldValueEquals('First name', 'John');
    $assert_session->fieldValueEquals('Last name', 'Doe');
    $assert_session->fieldValueEquals('Organization', 'DIGIT');
    $assert_session->fieldValueEquals('Bio', 'User description bio.');
    $assert_session->fieldValueEquals('Gender', 'http://publications.europa.eu/resource/authority/human-sex/MALE');
    $assert_session->fieldValueEquals('Country', 'BE');
    $assert_session->fieldValueEquals('Nationality', 'France (http://publications.europa.eu/resource/authority/country/FRA)');
    $working_language = $assert_session->optionExists('Working Languages', 'French');
    $this->assertSame('selected', $working_language->getAttribute('selected'));
    $page->pressButton('Save');

    $this->drupalGet("user/{$user->id()}");
    $assert_session->pageTextContains('John Doe');
    $assert_session->pageTextContains('User description bio.');
    $assert_session->pageTextContains('male');
    $assert_session->pageTextContains('1990-01-01');
    $assert_session->pageTextContains('Web Developer');
    $assert_session->pageTextContains('Belgium');
    $assert_session->pageTextContains('France');
    $assert_session->pageTextContains('French');

    $this->drupalLogin($user);
    $this->drupalGet("user/{$user->id()}/edit");
    $page->pressButton('Cancel account');
    $page->pressButton('Confirm');
    $assert_session->pageTextContains('A confirmation request to cancel your account has been sent to your email address.');
  }

}
