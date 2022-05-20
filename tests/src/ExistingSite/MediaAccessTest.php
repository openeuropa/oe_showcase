<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

use Drupal\file\Entity\File;
use Drupal\Tests\oe_showcase\Traits\AssertPathAccessTrait;
use Drupal\Tests\oe_showcase\Traits\UserTrait;
use weitzman\DrupalTestTraits\Entity\MediaCreationTrait;

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

    $pdf = File::create([
      'uri' => \Drupal::service('file_system')->copy(
        \Drupal::service('extension.list.module')->getPath('oe_media') . '/tests/fixtures/sample.pdf',
        'public://sample.pdf'
      ),
    ]);
    $pdf->save();
    $this->markEntityForCleanup($pdf);

    $image = File::create([
      'uri' => \Drupal::service('file_system')->copy(
        \Drupal::root() . '/core/misc/druplicon.png',
        'public://image.png'
      ),
    ]);
    $image->save();
    $this->markEntityForCleanup($image);

    // Prepare a list of minimum required fields for each media type.
    $bundles = [
      'remote_video' => [
        'name' => NULL,
        'oe_media_oembed_video' => 'https://www.youtube.com/watch?v=1-g73ty9v04',
      ],
      'av_portal_photo' => [
        'name' => NULL,
        'oe_media_avportal_photo' => 'P-038924/00-15',
      ],
      'av_portal_video' => [
        'name' => NULL,
        'oe_media_avportal_video' => 'I-163162',
      ],
      'webtools_chart' => [
        'oe_media_webtools' => '{"service":"chart"}',
      ],
      'webtools_map' => [
        'oe_media_webtools' => '{"service":"map"}',
      ],
      'webtools_social_feed' => [
        'oe_media_webtools' => '{"service":"social_feed"}',
      ],
      'webtools_op_publication_list' => [
        'oe_media_webtools' => '{"service":"opwidget"}',
      ],
      'webtools_generic' => [
        'oe_media_webtools' => '{"service":"share"}',
      ],
      'document' => [
        'oe_media_file_type' => 'local',
        'oe_media_file' => [
          'target_id' => $pdf->id(),
        ],
      ],
      'image' => [
        'oe_media_image' => [
          'target_id' => $image->id(),
          'alt' => 'Alt text',
        ],
      ],
    ];

    foreach ($bundles as $bundle => $data) {
      $this->medias[$bundle] = $this->createMedia($data + [
        'bundle' => $bundle,
        'name' => $bundle . ' title',
      ]);
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

    $this->assertPathsResponseCode(403, $forbidden_paths);
  }

}
