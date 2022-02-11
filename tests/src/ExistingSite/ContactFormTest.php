<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

/**
 * Contact form tests.
 */
class ContactFormTest extends ShowcaseExistingSiteTestBase {

  /**
   * Check creation contact form content through the UI.
   */
  public function testCreateContactForm() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // User page creator.
    $this->drupalLogin($this->createUser([
      'create oe_showcase_page content',
      'access corporate contact form',
    ]));
    $this->drupalGet('node/add/oe_showcase_page');

    // Create oe_showcase_page.
    $page->fillField('title[0][value]', 'Example contact form page');
    $page->fillField('field_description[0][value]', 'Example contact form page with corporate block.');
    $page->pressButton('Add Contact form');

    // Add contact form paragraph.
    $entity_storage = \Drupal::entityTypeManager()->getStorage('contact_form');
    /** @var \Drupal\contact\ContactFormInterface $contact_form */
    $contact_form = $entity_storage->load('example_contact_form');
    $page->selectFieldOption(
      'field_body[0][subform][field_contact_block][0][plugin_id]',
      'oe_contact_forms_corporate_block:' . $contact_form->uuid(),
    );
    $page->pressButton('Save');

    // Assert contact form was created successfully.
    $assert_session->elementTextContains('css', '.alert-content', 'Example contact form page has been created.');

    // User submitting the contact form.
    $this->drupalLogin($this->createUser([
      'access corporate contact form',
    ]));

    // Submit the form.
    $this->drupalGet('/pages/example-contact-form-page');
    $page->fillField('subject[0][value]', 'Example subject');
    $page->fillField('message[0][value]', 'Example Message text');
    $page->selectFieldOption('oe_topic', 'Alpaca');
    $page->checkField('privacy_policy');
    $page->pressButton('Send message');

    // Assert values were sent.
    $assert_session->pageTextContains('Alpaca');
    $assert_session->pageTextContains('Example Message text');

    // Assert honeypot is enabled and working for the contact form.
    $assert_session->fieldExists('url');
  }

}
