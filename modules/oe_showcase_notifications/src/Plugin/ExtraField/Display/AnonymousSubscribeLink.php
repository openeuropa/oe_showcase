<?php

declare(strict_types = 1);

namespace Drupal\oe_showcase_notifications\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\extra_field\Plugin\ExtraFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "anonymous_subscribe",
 *   label = @Translation("Anonymous subscribe"),
 *   description = @Translation("Link to subscribe to notifications about this node."),
 *   bundles = {
 *     "node.*"
 *   },
 *   weight = -30,
 *   visible = true
 * )
 */
class AnonymousSubscribeLink extends ExtraFieldDisplayBase {

  use StringTranslationTrait;

  public function view(ContentEntityInterface $entity) {
    $url = Url::fromRoute('oe_showcase_notifications.anonymous_subscribe', [
      'subscription_id' => implode(':', [
        'subscribe_node',
        $entity->id(),
      ]),
    ]);

    if (!$url->access()) {
      // @todo Handle caching.
      return [];
    }

    return [
      '#type' => 'link',
      '#title' => $this->t('Subscribe'),
      '#url' => $url,
      '#attributes' => [
        'class' => ['use-ajax', 'button', 'button--small'],
        'data-dialog-type' => 'modal',
      ],
    ];
  }

}
