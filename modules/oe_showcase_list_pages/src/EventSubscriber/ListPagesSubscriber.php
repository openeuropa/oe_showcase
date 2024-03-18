<?php

declare(strict_types=1);

namespace Drupal\oe_showcase_list_pages\EventSubscriber;

use Drupal\oe_list_pages\ListPageEvents;
use Drupal\oe_list_pages\ListPageSortAlterEvent;
use Drupal\oe_list_pages\ListPageSortOptionsResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * OpenEuropa Showcase List Pages event subscriber.
 */
class ListPagesSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ListPageEvents::ALTER_SORT_OPTIONS => ['onSortOptionsAlter'],
    ];
  }

  /**
   * Event handler for altering the sort options.
   *
   * @param \Drupal\oe_list_pages\ListPageSortAlterEvent $event
   *   The event.
   */
  public function onSortOptionsAlter(ListPageSortAlterEvent $event): void {
    $alter = $this->optionsAlter($event->getScope(), $event->getEntityType(), $event->getBundle());
    $options = $event->getOptions();
    $options = array_merge($options, $alter);
    $event->setOptions($options);
  }

  /**
   * Returns a mapping of content types to sort options.
   *
   * @param string $scope
   *   The options resolver scope.
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   *
   * @return array|string[]
   *   The mapping.
   */
  protected function optionsAlter(string $scope, string $entity_type, string $bundle): array {
    $data = [
      ListPageSortOptionsResolver::SCOPE_USER => [
        'node' => [
          'oe_sc_news' => [
            'oe_publication_date__ASC' => 'Date ASC',
            'oe_publication_date__DESC' => 'Date DESC',
            'title__ASC' => 'A-Z',
            'title__DESC' => 'Z-A',
          ],
          'oe_sc_event' => [
            'oe_sc_event_dates__ASC' => 'Date ASC',
            'oe_sc_event_dates__DESC' => 'Date DESC',
            'title__ASC' => 'A-Z',
            'title__DESC' => 'Z-A',
          ],
          'oe_project' => [
            'title__ASC' => 'A-Z',
            'title__DESC' => 'Z-A',
            'oe_project_budget__ASC' => 'Total budget ASC',
            'oe_project_budget__DESC' => 'Total budget DESC',
          ],
          'oe_sc_person' => [
            'title__ASC' => 'A-Z',
            'title__DESC' => 'Z-A',
          ],
          'oe_sc_publication' => [
            'title__ASC' => 'A-Z',
            'title__DESC' => 'Z-A',
            'oe_publication_date__ASC' => 'Publication date ASC',
            'oe_publication_date__DESC' => 'Publication date DESC',
          ],
        ],
      ],
    ];

    return $data[$scope][$entity_type][$bundle] ?? [];
  }

}
