<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\oe_showcase\Traits\AssertPathAccessTrait;
use Drupal\Tests\oe_showcase\Traits\UserTrait;

/**
 * Tests access to taxonomy vocabularies CRUD and overview routes.
 */
class TaxonomyAccessTest extends ShowcaseExistingSiteTestBase {

  use AssertPathAccessTrait;
  use UserTrait;

  /**
   * Tests that editors can create taxonomy terms in designed vocabularies.
   */
  public function testManageTaxonomyPagesAccess(): void {
    $vocabularies = [
      'event_type',
      'publication_type',
    ];
    $terms = [];
    foreach ($vocabularies as $vid) {
      $terms[$vid] = $this->createTerm(Vocabulary::load($vid));
    }

    $restricted_paths = [];
    foreach ($terms as $bundle => $term) {
      $restricted_paths[] = "admin/structure/taxonomy/manage/{$bundle}/add/";
      $restricted_paths[] = $term->toUrl('edit-form')->setAbsolute()->toString();
      $restricted_paths[] = $term->toUrl('delete-form')->setAbsolute()->toString();
    }

    // Editors have access to the taxonomy overview.
    $restricted_paths[] = 'admin/structure/taxonomy';
    $this->assertPathsRequireRole($restricted_paths, 'editor');

    // Vocabularies cannot be created, edited or deleted.
    $forbidden_paths = [
      'admin/structure/taxonomy/add',
    ];
    foreach ($vocabularies as $bundle) {
      $forbidden_paths[] = 'admin/structure/taxonomy/manage/' . $bundle;
      $forbidden_paths[] = "admin/structure/taxonomy/manage/{$bundle}/delete";
    }

    $this->assertPathsAccessForbidden($forbidden_paths);
  }

}
