<?php

declare(strict_types=1);

namespace Drupal\oe_showcase_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Replaces the symfony_mailer test service with ours.
 */
class OeShowcaseTestServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->has('symfony_mailer.test')) {
      $container->getDefinition('symfony_mailer.test')
        ->setClass(MailerTestService::class);
    }
  }

}
