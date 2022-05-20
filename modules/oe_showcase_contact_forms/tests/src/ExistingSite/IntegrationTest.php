<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_contact_forms\ExistingSite;

use Drupal\Tests\oe_showcase\ExistingSite\ShowcaseExistingSiteTestBase;
use Drupal\Tests\oe_showcase\Traits\PathAccessTrait;
use Drupal\Tests\oe_showcase\Traits\UserTrait;

/**
 * Contact form tests.
 */
class IntegrationTest extends ShowcaseExistingSiteTestBase {

  use PathAccessTrait;
  use UserTrait;

  /**
   * Tests access to contact form admin pages.
   */
  public function testAccess(): void {
    $manage_contact_forms_paths = [
      'admin/structure/contact/add',
      'admin/structure/contact/manage/export',
      'admin/structure/contact/messages',
      'contact/example_contact_form',
    ];
    $this->assertPathsRequireRole($manage_contact_forms_paths, 'manage_contact_forms');
  }

  /**
   * Check creation contact form content through the UI.
   */
  public function testCreateContactForm() {
    // Mark test content for deletion after the test has finished.
    $this->markEntityTypeForCleanup('user');
    $this->markEntityTypeForCleanup('node');
    $this->markEntityTypeForCleanup('paragraph');

    // Disable the time limit to avoid honeypot error due to
    // the quick submission form.
    \Drupal::configFactory()->getEditable('honeypot.settings')->set('time_limit', 0)->save();

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->createUser([
      'create oe_showcase_page content',
      'access corporate contact form',
      'view published skos concept entities',
    ]));
    $this->drupalGet('node/add/oe_showcase_page');

    // Create oe_showcase_page.
    $page->fillField('Title', 'Example contact form page');
    $page->fillField('Description', 'Example contact form page with corporate block.');
    $page->pressButton('Add Contact form');

    // Add contact form paragraph.
    $entity_storage = \Drupal::entityTypeManager()->getStorage('contact_form');
    /** @var \Drupal\contact\ContactFormInterface $contact_form */
    $contact_form = $entity_storage->load('example_contact_form');
    $page->selectFieldOption(
      'field_body[0][subform][contact_block][0][plugin_id]',
      'oe_contact_forms_corporate_block:' . $contact_form->uuid(),
    );
    $page->pressButton('Save');

    $this->drupalLogin($this->createUser());

    $this->drupalGet('/pages/example-contact-form-page');
    $page->fillField('Subject', 'Example subject');
    $page->fillField('Message', 'Example Message text');
    $page->selectFieldOption('Topic', 'Alpaca');
    $page->checkField('privacy_policy');
    $page->fillField('Phone', '345345345');
    $page->selectFieldOption('Country of residence', 'http://publications.europa.eu/resource/authority/country/BEL');
    $page->pressButton('Send message');

    $assert_session->pageTextContains('Alpaca');
    $assert_session->pageTextContains('345345345');
    $assert_session->pageTextContains('Belgium');
    $assert_session->pageTextContains('Example Message text');
  }

}
