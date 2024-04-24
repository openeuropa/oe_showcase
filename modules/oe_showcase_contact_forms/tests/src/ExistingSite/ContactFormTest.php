<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase_contact_forms\ExistingSite;

use Drupal\symfony_mailer_test\MailerTestServiceInterface;
use Drupal\symfony_mailer_test\MailerTestTrait;
use Drupal\Tests\oe_showcase\ExistingSite\ShowcaseExistingSiteTestBase;
use Drupal\Tests\oe_showcase\Traits\AssertPathAccessTrait;
use Drupal\Tests\oe_showcase\Traits\UserTrait;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Contact form tests.
 */
class ContactFormTest extends ShowcaseExistingSiteTestBase {

  use AssertPathAccessTrait;
  use UserTrait;
  use MailerTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->backupSimpleConfig('contact.settings');
    $this->backupSimpleConfig('honeypot.settings');
    // Empty previously collected mails.
    \Drupal::state()->delete(MailerTestServiceInterface::STATE_KEY);
  }

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
    $this->markEntityTypeForCleanup('contact_message');

    // Disable the flood limits to avoid submit failure when run multiple times.
    $contact_settings = $this->container->get('config.factory')->getEditable('contact.settings');
    $flood_values = [
      'limit' => '100',
      'interval' => '1',
    ];
    $contact_settings->set('flood', $flood_values)->save();

    // Disable the time limit to avoid honeypot error due to
    // the quick submission form.
    \Drupal::configFactory()->getEditable('honeypot.settings')->set('time_limit', 0)->save();

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->createUserWithRoles(['editor']));
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

    // Test contact form.
    $user = $this->createUser();
    $this->drupalLogin($user);

    $this->drupalGet('/pages/example-contact-form-page');
    $page->fillField('Subject', 'Example subject');
    $page->fillField('Message', 'Example Message text');
    $page->selectFieldOption('Topic', 'Alpaca');
    $page->checkField('privacy_policy');
    $page->fillField('Phone', '345345345');
    $page->selectFieldOption('Country of residence', 'http://publications.europa.eu/resource/authority/country/BEL');
    $page->pressButton('Send message');

    $confirm_message = $assert_session->elementExists('css', 'div.alert.alert-success')->getText();
    $this->assertStringContainsString('Alpaca', $confirm_message);
    $this->assertStringContainsString('345345345', $confirm_message);
    $this->assertStringContainsString('Belgium', $confirm_message);
    $this->assertStringContainsString('Example subject', $confirm_message);
    $this->assertStringContainsString('Example Message text', $confirm_message);

    // Check sent mail.
    $email = $this->readMail();
    $page_url = $this->buildUrl('/pages/example-contact-form-page', ['absolute' => TRUE]);
    $user_url = $user->toUrl('canonical', ['absolute' => TRUE])->toString();
    // Configured recipients + Topic address 'Alpaca'.
    $this->assertAddress('to', ['webmaster@example.com', 'webmaster2@example.com']);
    $this->assertContactMailHtml(
      "{$user->label()} ($user_url) sent a message using the contact form at $page_url.",
      [
        'name' => [
          'label' => "The sender's name",
          'text' => $user->label(),
        ],
        'mail' => [
          'label' => "The sender's email",
          'text' => $user->getEmail(),
        ],
        'subject' => [
          'label' => 'Subject',
          'text' => 'Example subject',
        ],
        'message' => [
          'label' => 'Message',
          'text' => 'Example Message text',
        ],
        'oe-country-residence' => [
          'label' => 'Country of residence',
          'text' => 'Belgium',
        ],
        'oe-telephone' => [
          'label' => 'Phone',
          'text' => '345345345',
        ],
        'oe-topic' => [
          'label' => 'Topic',
          'text' => 'Alpaca',
        ],
      ],
      $email->getHtmlBody()
    );

    // Test contact form with copy to sender.
    $this->drupalGet('/pages/example-contact-form-page');
    $page->fillField('Subject', 'Example subject 2');
    $page->fillField('Message', 'Example Message text 2');
    $page->selectFieldOption('Topic', 'Llama');
    $page->checkField('privacy_policy');
    $page->fillField('Phone', '123123123');
    $page->selectFieldOption('Country of residence', 'http://publications.europa.eu/resource/authority/country/ESP');
    $page->checkField('Send yourself a copy');
    $page->pressButton('Send message');
    // Copy option sends two mails, first we check the website mail.
    $email = $this->readMail(FALSE);
    $this->assertAddress('to', ['webmaster@example.com', 'webmaster@example.com']);
    $this->assertContactMailHtml(
      "{$user->label()} ($user_url) sent a message using the contact form at $page_url.",
      [
        'name' => [
          'label' => "The sender's name",
          'text' => $user->label(),
        ],
        'mail' => [
          'label' => "The sender's email",
          'text' => $user->getEmail(),
        ],
        'subject' => [
          'label' => 'Subject',
          'text' => 'Example subject 2',
        ],
        'message' => [
          'label' => 'Message',
          'text' => 'Example Message text 2',
        ],
        'oe-country-residence' => [
          'label' => 'Country of residence',
          'text' => 'Spain',
        ],
        'oe-telephone' => [
          'label' => 'Phone',
          'text' => '123123123',
        ],
        'oe-topic' => [
          'label' => 'Topic',
          'text' => 'Llama',
        ],
      ],
      $email->getHtmlBody()
    );

    // Then check the copy mail.
    $email = $this->readMail();
    $this->assertTo($user->getEmail());
    $this->assertContactMailHtml(
      "{$user->label()} ($user_url) sent a message using the contact form at $page_url.",
      [
        'name' => [
          'label' => "The sender's name",
          'text' => $user->label(),
        ],
        'mail' => [
          'label' => "The sender's email",
          'text' => $user->getEmail(),
        ],
        'subject' => [
          'label' => 'Subject',
          'text' => 'Example subject 2',
        ],
        'message' => [
          'label' => 'Message',
          'text' => 'Example Message text 2',
        ],
        'oe-country-residence' => [
          'label' => 'Country of residence',
          'text' => 'Spain',
        ],
        'oe-telephone' => [
          'label' => 'Phone',
          'text' => '123123123',
        ],
        'oe-topic' => [
          'label' => 'Topic',
          'text' => 'Llama',
        ],
      ],
      $email->getHtmlBody(),
      TRUE
    );
  }

  /**
   * Asserts the expected HTML in a contact mail.
   *
   * @param string $expected_intro
   *   Text with the mail introduction.
   * @param array[] $expected_fields
   *   Associative array with the expected fields.
   * @param string $mail_body
   *   The mail HTML body.
   * @param bool $copy
   *   If the mail is a copy to the sender.
   */
  protected function assertContactMailHtml(string $expected_intro, array $expected_fields, string $mail_body, bool $copy = FALSE): void {
    $copy = $copy ? 'copy' : 'mail';
    $crawler = new Crawler($mail_body);
    $content_wrapper = $crawler->filter(".email-sub-type-page-$copy div.clearfix");

    // Assert introduction.
    $this->assertEquals($expected_intro, $content_wrapper->innerText());

    // Assert each field content and order.
    $previous = '';
    foreach ($expected_fields as $field => $content) {
      $field_wrapper = $content_wrapper->filter(".example-contact-form__$field");
      $this->assertEquals($content['label'], $field_wrapper->filter(".field__label")->text());
      $this->assertEquals($content['text'], $field_wrapper->filter(".field__item")->text());
      if ($previous) {
        $this->assertEquals(
          $content_wrapper->filter(".example-contact-form__$previous")->attr('class'),
          $field_wrapper->previousAll()->attr('class')
        );
      }
      $previous = $field;
    }
  }

}
