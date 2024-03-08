<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase_subscriptions\ExistingSite;

use Drupal\symfony_mailer\Email;
use Drupal\symfony_mailer_test\MailerTestServiceInterface;
use Drupal\symfony_mailer_test\MailerTestTrait;
use Drupal\Tests\oe_showcase\ExistingSite\ShowcaseExistingSiteTestBase;
use Drupal\Tests\oe_showcase\Traits\NodeCreationTrait;
use Drupal\Tests\oe_showcase\Traits\UserTrait;
use Drupal\Tests\oe_subscriptions_anonymous\Trait\StatusMessageTrait as AnonymousStatusMessageTrait;
use Drupal\Tests\Traits\Core\CronRunTrait;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the event subscription functionality.
 */
class EventSubscriptionTest extends ShowcaseExistingSiteTestBase {

  use AnonymousStatusMessageTrait;
  use CronRunTrait;
  use MailerTestTrait;
  use NodeCreationTrait;
  use UserTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Decoupled users are created in the test.
    $this->markEntityTypeForCleanup('user');
    // Run cron so that any pending emails are sent out.
    $this->cronRun();
    // Empty previously collected mails.
    \Drupal::state()->delete(MailerTestServiceInterface::STATE_KEY);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    parent::tearDown();

    // Empty uncollected mails at the end of the test, e.g. after a failure.
    $state = \Drupal::state();
    $state->delete(MailerTestServiceInterface::STATE_KEY);
    // Clear the last execution time for digests.
    $state->delete('message_digest:daily_last_run');
    $state->delete('message_digest:weekly_last_run');
  }

  /**
   * Tests the event subscription and notifications.
   */
  public function testEventNotifications(): void {
    $event = $this->createEventNode();
    $this->markEntityForCleanup($event);

    // Subscribe with a registered user.
    $authenticated_user = $this->createUser();
    $this->drupalLogin($authenticated_user);
    $this->drupalGet($event->toUrl());
    $assert_session = $this->assertSession();
    $action_bar = $assert_session->elementExists('css', '.bcl-content-banner .action-bar');
    $action_bar->clickLink('Subscribe');
    $assert_session->statusMessageContains('You are now subscribed to this item.');

    // Subscribe also with an anonymous user.
    $this->drupalLogout();
    $this->drupalGet($event->toUrl());
    $action_bar->clickLink('Subscribe');
    $assert_session->fieldExists('Your e-mail')->setValue('test_anon@example.com');
    $assert_session->fieldExists('I have read and agree with the data protection terms.')->check();
    $assert_session->buttonExists('Subscribe me')->press();
    // Read the mail just after sending it, or the collector service will throw
    // errors when destructed.
    $this->assertSubscriptionCreateMailStatusMessage();
    // Confirm the subscription.
    $mail = $this->readMail();
    $this->assertTo('test_anon@example.com');
    $this->drupalGet($this->getMailLinks($mail)[1]);
    $assert_session->statusMessageContains('Your subscription request has been confirmed.');

    // Update the event.
    $editor = $this->createUserWithRoles(['editor']);
    $this->drupalLogin($editor);
    $this->drupalGet($event->toUrl('edit-form'));
    $assert_session->fieldExists('Title')->setValue('Event update 1');
    $assert_session->buttonExists('Save')->press();
    $assert_session->pageTextContains('Event Event update 1 has been updated.');

    $this->assertMail($authenticated_user->getEmail(), ['The event Event update 1 has been updated.'], [
      [
        'url' => $event->toUrl()->setAbsolute()->toString(),
        'text' => 'Event update 1',
      ],
    ], FALSE);
    $this->assertMail('test_anon@example.com', ['The event Event update 1 has been updated.'], [
      [
        'url' => $event->toUrl()->setAbsolute()->toString(),
        'text' => 'Event update 1',
      ],
    ]);

    // Opt-in for receiving digest e-mails every day.
    $this->drupalLogin($authenticated_user);
    $this->drupalGet($authenticated_user->toUrl());
    $this->getSession()->getPage()->clickLink('Subscriptions');
    $assert_session->selectExists('Notifications frequency')->selectOption('Daily');
    $assert_session->buttonExists('Save')->press();
    $assert_session->statusMessageContains('Your preferences have been saved.');

    // Make a new change to the event.
    $this->drupalLogin($editor);
    $this->drupalGet($event->toUrl('edit-form'));
    $assert_session->fieldExists('Title')->setValue('Event update 2');
    $assert_session->buttonExists('Save')->press();
    $assert_session->pageTextContains('Event Event update 2 has been updated.');
    $this->drupalLogout();

    // Only one email is expected, to the anonymous user who didn't opt for a
    // digest.
    $this->assertMail('test_anon@example.com', ['The event Event update 2 has been updated.'], [
      [
        'url' => $event->toUrl()->setAbsolute()->toString(),
        'text' => 'Event update 2',
      ],
    ]);

    // Move the time forward one day.
    \Drupal::time()->setTime(strtotime('+1 day +1 hour', \Drupal::time()->getCurrentTime()));
    $this->cronRun();
    $this->waitUntilMailsAreCollected(1);
    $this->assertMail($authenticated_user->getEmail(), ['The event Event update 2 has been updated.'], [
      [
        'url' => $event->toUrl()->setAbsolute()->toString(),
        'text' => 'Event update 2',
      ],
    ]);

    // Set the anonymous user to receive weekly digests.
    $this->drupalGet('/user/subscriptions');
    $assert_session = $this->assertSession();
    $assert_session->fieldExists('Your e-mail')->setValue('test_anon@example.com');
    $assert_session->buttonExists('Submit')->press();
    $this->assertSubscriptionsPageMailStatusMessage();
    $mail = $this->readMail();
    $this->assertTo('test_anon@example.com');
    $this->drupalGet($this->getMailLinks($mail)[1]);
    $assert_session->selectExists('Notifications frequency')->selectOption('Weekly');
    $assert_session->buttonExists('Save')->press();
    $assert_session->statusMessageContains('Your preferences have been saved.');

    // Do another change to the event.
    $this->drupalLogin($editor);
    $this->drupalGet($event->toUrl('edit-form'));
    $assert_session->fieldExists('Title')->setValue('Event update 3');
    $assert_session->buttonExists('Save')->press();
    $assert_session->pageTextContains('Event Event update 3 has been updated.');
    $this->drupalLogout();

    // Move the time forward one day.
    \Drupal::time()->setTime(strtotime('+1 day +1 hour', \Drupal::time()->getCurrentTime()));
    $this->cronRun();
    $this->waitUntilMailsAreCollected(1);
    $this->assertMail($authenticated_user->getEmail(), ['The event Event update 3 has been updated.'], [
      [
        'url' => $event->toUrl()->setAbsolute()->toString(),
        'text' => 'Event update 3',
      ],
    ]);

    // Move the time forward a week.
    \Drupal::time()->setTime(strtotime('+1 week +1 hour', \Drupal::time()->getCurrentTime()));
    $this->cronRun();
    $this->waitUntilMailsAreCollected(1);
    // The anonymous user weekly digest mail should have been sent.
    $this->assertMail('test_anon@example.com', ['The event Event update 3 has been updated.'], [
      [
        'url' => $event->toUrl()->setAbsolute()->toString(),
        'text' => 'Event update 3',
      ],
    ]);

    // Create a second event and subscribe a user to it.
    $event_two = $this->createEventNode([
      'title' => 'Second event',
    ]);
    $this->drupalLogin($authenticated_user);
    $this->drupalGet($event_two->toUrl());
    $assert_session = $this->assertSession();
    $action_bar = $assert_session->elementExists('css', '.bcl-content-banner .action-bar');
    $action_bar->clickLink('Subscribe');
    $assert_session->statusMessageContains('You are now subscribed to this item.');

    $this->drupalLogin($editor);
    // Update the second event.
    $this->drupalGet($event_two->toUrl('edit-form'));
    $assert_session->fieldExists('Title')->setValue('Second event - update 1');
    $assert_session->buttonExists('Save')->press();
    $assert_session->pageTextContains('Event Second event - update 1 has been updated.');
    // Update also the first event.
    $this->drupalGet($event->toUrl('edit-form'));
    $assert_session->fieldExists('Title')->setValue('Event update 4');
    $assert_session->buttonExists('Save')->press();
    $assert_session->pageTextContains('Event Event update 4 has been updated.');
    $this->drupalLogout();

    // No mail should have been sent, as both the users have enabled the digest.
    $this->noMail();

    // Move the time forward another day.
    \Drupal::time()->setTime(strtotime('+1 day +1 hour', \Drupal::time()->getCurrentTime()));
    $this->cronRun();
    $this->waitUntilMailsAreCollected(1);
    // The mail should contain information about the updates of both nodes.
    $this->assertMail(
      $authenticated_user->getEmail(),
      [
        'The event Event update 4 has been updated.',
        'The event Second event - update 1 has been updated.',
      ],
      [
        [
          'url' => $event_two->toUrl()->setAbsolute()->toString(),
          'text' => 'Second event - update 1',
        ],
        [
          'url' => $event->toUrl()->setAbsolute()->toString(),
          'text' => 'Event update 4',
        ],
      ]);

    // Move the time forward another week.
    \Drupal::time()->setTime(strtotime('+1 week +1 hour', \Drupal::time()->getCurrentTime()));
    $this->cronRun();
    $this->waitUntilMailsAreCollected(1);
    // The anonymous user weekly digest mail should have been sent.
    $this->assertMail('test_anon@example.com', ['The event Event update 4 has been updated.'], [
      [
        'url' => $event->toUrl()->setAbsolute()->toString(),
        'text' => 'Event update 4',
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   *
   * Overrides the message selectors.
   */
  protected function assertHtmlStatusMessage(array $html, string $type): void {
    if (empty($html) || array_is_list($html)) {
      throw new \InvalidArgumentException('An associative array of tags and expected their content is expected.');
    }

    $allowed_types = [
      'status' => 'success',
      'error' => 'danger',
      'warning' => 'warning',
    ];
    if (!isset($allowed_types[$type])) {
      throw new \InvalidArgumentException(sprintf("Provide an message type, the allowed values are 'status', 'error', 'warning'. The value provided was '%s'.", $type));
    }

    $assert_session = $this->assertSession();
    $class = ' alert-' . $allowed_types[$type] . ' ';
    foreach ($html as $tag => $content) {
      $xpath = $assert_session->buildXPathQuery('//div[@data-drupal-messages]//div[@class and contains(concat(:space, normalize-space(@class), :space), :class)]//' . $tag . '[contains(., :content)]', [
        ':space' => ' ',
        ':class' => $class,
        ':content' => $content,
      ]);
      $assert_session->elementExists('xpath', $xpath);
    }
  }

  /**
   * Retrieves the URIs of links existing in a mail.
   *
   * @param \Drupal\symfony_mailer\Email $mail
   *   The mail.
   *
   * @return string[]
   *   An array of URIs.
   */
  protected function getMailLinks(Email $mail): array {
    $crawler = new Crawler($mail->getHtmlBody());

    return array_map(static fn($link) => $link->getUri(), $crawler->filter('a')->links());
  }

  /**
   * Reads a mail from the queue and asserts its content.
   *
   * @param string $expected_to
   *   The expected "to" address.
   * @param string[] $expected_texts
   *   A list of expected text content to search in the mail body.
   * @param array[] $expected_links
   *   A list of links that should be present in the mail. Each entry is
   *   composed of a "url" and a "text" key.
   * @param bool $last_mail
   *   If the current mail is the last one expected to be found in the queue.
   */
  protected function assertMail(string $expected_to, array $expected_texts, array $expected_links, bool $last_mail = TRUE): void {
    $mail = $this->readMail($last_mail);
    $this->assertTo($expected_to);
    $crawler = new Crawler($mail->getHtmlBody());
    foreach ($expected_texts as $expected) {
      $this->assertStringContainsString($expected, $crawler->text());
    }
    $links = $crawler->filter('a');
    $this->assertSameSize($expected_links, $links);
    foreach ($expected_links as $delta => $data) {
      $this->assertEquals($data['url'], $links->eq($delta)->attr('href'), sprintf('Url at delta %s did not match.', $delta));
      $this->assertEquals($data['text'], $links->eq($delta)->text(), sprintf('Text at delta %s did not match.', $delta));
    }
  }

  /**
   * Waits for mails to be collected.
   *
   * @param int $count
   *   The number of mails to collect.
   */
  protected function waitUntilMailsAreCollected(int $count): void {
    $state = \Drupal::state();
    // We don't need the page at all, but we reuse the wait code.
    $result = $this->getSession()->getPage()->waitFor(10, function () use ($count, $state) {
      $state->resetCache();
      $mail_count = count($state->get(MailerTestServiceInterface::STATE_KEY, []) ?? []);
      return $mail_count === $count ? $mail_count : FALSE;
    });
    $this->assertEquals($count, $result, sprintf('%s mails were expected, but %s found.', $count, (int) $result));
  }

}
