<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

use Drupal\Tests\oe_showcase\Traits\AssertPathAccessTrait;
use Drupal\Tests\oe_showcase\Traits\MediaCreationTrait;
use Drupal\Tests\oe_showcase\Traits\UserTrait;

/**
 * Tests access to media CRUD and overview routes.
 */
class MediaAccessTest extends ShowcaseExistingSiteTestBase {

  use MediaCreationTrait;
  use AssertPathAccessTrait;
  use UserTrait;

  /**
   * A list of media entities to test access with.
   *
   * @var array
   */
  protected array $medias = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $bundles = \Drupal::entityTypeManager()->getStorage('media_type')->loadMultiple();
    foreach (array_keys($bundles) as $bundle) {
      $this->medias[$bundle] = $this->createMediaByBundle($bundle);
    }
  }

  /**
   * Tests access to media create/edit/delete and overview pages.
   */
  public function testManageMediaPagesAccess(): void {
    // Some media types are present in the system but they are not usable yet,
    // so the related permissions are not assigned.
    $disallowed_media_bundles = [
      'webtools_op_publication_list',
      'webtools_generic',
      'webtools_countdown',
    ];

    $restricted_paths = [];
    $allowed_medias = array_diff_key($this->medias, array_flip($disallowed_media_bundles));
    foreach ($allowed_medias as $bundle => $media) {
      $restricted_paths[] = 'media/add/' . $bundle;
      $restricted_paths[] = 'media/' . $media->id() . '/edit';
      $restricted_paths[] = 'media/' . $media->id() . '/delete';
    }

    // Editors have access to the media overview.
    $restricted_paths[] = 'admin/content/media';

    $this->assertPathsRequireRole($restricted_paths, 'editor');

    $forbidden_paths = [];
    foreach ($disallowed_media_bundles as $bundle) {
      $forbidden_paths[] = 'media/add/' . $bundle;
      $forbidden_paths[] = 'media/' . $this->medias[$bundle]->id() . '/edit';
      $forbidden_paths[] = 'media/' . $this->medias[$bundle]->id() . '/delete';
    }

    $this->assertPathsAccessForbidden($forbidden_paths);
  }

}
