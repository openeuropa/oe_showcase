<?php

declare(strict_types = 1);


namespace Drupal\oe_showcase_list_pages\EventSubscriber;

use Drupal\oe_list_pages\ListPageEvents;
use Drupal\oe_list_pages\ListPageSourceAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to the list page allowed entity types event.
 */
class ListPageAllowedTypesSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ListPageEvents::ALTER_ENTITY_TYPES => 'alterEntityTypes',
      ListPageEvents::ALTER_BUNDLES => 'alterBundles',
    ];
  }

  /**
   * Alters the entity types allowed by the List Page nodes.
   *
   * @param \Drupal\oe_list_pages\ListPageSourceAlterEvent $event
   *   The event.
   */
  public function alterEntityTypes(ListPageSourceAlterEvent $event) {
    $event->setEntityTypes(['node', 'media']);
  }

  /**
   * Alters the bundles allowed by the List Page nodes.
   *
   * @param \Drupal\oe_list_pages\ListPageSourceAlterEvent $event
   *   The event.
   */
  public function alterBundles(ListPageSourceAlterEvent $event) {
    $allowed = [
      'node' => [
        'oe_showcase_search_demo',
      ],
    ];

    $entity_types = $event->getEntityTypes();
    $entity_type = reset($entity_types);
    if (isset($allowed[$entity_type])) {
      $event->setBundles($entity_type, $allowed[$entity_type]);
    }
  }

}
