<?php

declare(strict_types=1);

namespace Drupal\oe_showcase_test;

use Drupal\Core\State\StateInterface;
use Drupal\symfony_mailer\EmailInterface;
use Drupal\symfony_mailer_test\MailerTestService as SymfonyMailerTestService;

/**
 * Extends the Symfony mailer test service.
 *
 * The differences are:
 * - mails are collected instantly, and not at service destruction time.
 * - no errors are thrown if emails are not collected. For tests that run on
 *   existing databases, this will cause the next instantiation of the service
 *   to throw an error, and often this happens in the next run.
 */
class MailerTestService extends SymfonyMailerTestService {

  /**
   * {@inheritdoc}
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
    $this->emails = $state->get(self::STATE_KEY, []);
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function postSend(EmailInterface $email) {
    $this->emails[] = $email;
    $this->state->set(self::STATE_KEY, $this->emails);
  }

}
