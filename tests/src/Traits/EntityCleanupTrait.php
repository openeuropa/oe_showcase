<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase\Traits;

/**
 * Tracks entities of a given type to clean them up once the test ends.
 */
trait EntityCleanupTrait {

  /**
   * The IDs of the existing entities in the system, keyed by entity type.
   *
   * @var array
   */
  protected $existing = [];

  /**
   * Deletes all the entities that have been created during test execution.
   */
  protected function cleanupTestEntities(): void {
    foreach ($this->existing as $entity_type => $ids) {
      $current_ids = $this->getAllEntityIds($entity_type);
      $test_entity_ids = array_diff($current_ids, $ids);

      if ($test_entity_ids) {
        $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
        $storage->delete($storage->loadMultiple($test_entity_ids));
      }
    }
  }

  /**
   * Marks entities of a specific type for cleanup.
   *
   * All the entities created after the method call will be deleted, while the
   * existing ones will be preserved.
   *
   * @param string $entity_type
   *   The entity type ID.
   */
  protected function markEntityTypeForCleanup(string $entity_type): void {
    $this->existing[$entity_type] = $this->getAllEntityIds($entity_type);
  }

  /**
   * Returns all the IDs of a given entity type.
   *
   * @param string $entity_type
   *   The entity type.
   *
   * @return array
   *   The entity IDs.
   */
  protected function getAllEntityIds(string $entity_type): array {
    return \Drupal::entityTypeManager()->getStorage($entity_type)->getQuery()->accessCheck(FALSE)->execute();
  }

}
