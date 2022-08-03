<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\Traits;

use Drupal\Component\Utility\NestedArray;
use Drupal\file\Entity\File;
use Drupal\media\MediaInterface;
use weitzman\DrupalTestTraits\Entity\MediaCreationTrait as WeitzmanMediaCreationTrait;

/**
 * Contains methods to create media entities for testing.
 *
 * When adding a method to create a specific bundle, the method name MUST follow
 * the naming: "create" + bundle name in camel case + "Media".
 */
trait MediaCreationTrait {

  use WeitzmanMediaCreationTrait;

  /**
   * Creates a media of a specific bundle ready to use in tests.
   *
   * @param string $bundle
   *   The bundle of the media.
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\media\MediaInterface
   *   The media entity.
   */
  protected function createMediaByBundle(string $bundle, array $values = []): MediaInterface {
    $callable = [
      static::class,
      'create' . strtr(ucwords($bundle, '_'), ['_' => '']) . 'Media',
    ];

    if (!is_callable($callable)) {
      throw new \Exception(sprintf('No methods found to create medias of bundle "%s".', $bundle));
    }

    /** @var \Drupal\media\MediaInterface $media */
    $media = call_user_func($callable, $values);

    return $media;
  }

  /**
   * Create a remote video media with default values, ready to use in tests.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\media\MediaInterface
   *   The media entity.
   */
  protected function createRemoteVideoMedia(array $values = []): MediaInterface {
    $values['bundle'] = 'remote_video';
    // Title is fetched automatically from remote, so it must stay empty.
    $values['name'] = NULL;

    return $this->createMedia($values + [
      'oe_media_oembed_video' => 'https://www.youtube.com/watch?v=1-g73ty9v04',
    ]);
  }

  /**
   * Create an AV Portal photo media with default values, ready to use in tests.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\media\MediaInterface
   *   The media entity.
   */
  protected function createAvPortalPhotoMedia(array $values = []): MediaInterface {
    $values['bundle'] = 'av_portal_photo';
    // Title is fetched automatically from remote, so it must stay empty.
    $values['name'] = NULL;

    return $this->createMedia($values + [
      'oe_media_avportal_photo' => 'P-038924/00-15',
    ]);
  }

  /**
   * Create an AV Portal video media with default values, ready to use in tests.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\media\MediaInterface
   *   The media entity.
   */
  protected function createAvPortalVideoMedia(array $values = []): MediaInterface {
    $values['bundle'] = 'av_portal_video';
    // Title is fetched automatically from remote, so it must stay empty.
    $values['name'] = NULL;

    return $this->createMedia($values + [
      'oe_media_avportal_video' => 'I-163162',
    ]);
  }

  /**
   * Create a Webtools chart media with default values, ready to use in tests.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\media\MediaInterface
   *   The media entity.
   */
  protected function createWebtoolsChartMedia(array $values = []): MediaInterface {
    $values['bundle'] = 'webtools_chart';

    return $this->createMedia($values + [
      'name' => 'Webtools chart title',
      'oe_media_webtools' => '{"service":"chart"}',
    ]);
  }

  /**
   * Create a Webtools map media with default values, ready to use in tests.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\media\MediaInterface
   *   The media entity.
   */
  protected function createWebtoolsMapMedia(array $values = []): MediaInterface {
    $values['bundle'] = 'webtools_map';

    return $this->createMedia($values + [
      'name' => 'Webtools map title',
      'oe_media_webtools' => '{"service":"map"}',
    ]);
  }

  /**
   * Create a Webtools social feed media with default values.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\media\MediaInterface
   *   The media entity.
   */
  protected function createWebtoolsSocialFeedMedia(array $values = []): MediaInterface {
    $values['bundle'] = 'webtools_social_feed';

    return $this->createMedia($values + [
      'name' => 'Webtools social feed title',
      'oe_media_webtools' => '{"service":"social_feed"}',
    ]);
  }

  /**
   * Create a OP publication list media with default values.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\media\MediaInterface
   *   The media entity.
   */
  protected function createWebtoolsOpPublicationListMedia(array $values = []): MediaInterface {
    $values['bundle'] = 'webtools_op_publication_list';

    return $this->createMedia($values + [
      'name' => 'Webtools OP publication list title',
      'oe_media_webtools' => '{"service":"opwidget"}',
    ]);
  }

  /**
   * Create a Webtools countdown media with default values.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\media\MediaInterface
   *   The media entity.
   */
  protected function createWebtoolsCountdownMedia(array $values = []): MediaInterface {
    $values['bundle'] = 'webtools_countdown';

    return $this->createMedia($values + [
      'name' => 'Webtools countdown title',
      'oe_media_webtools' => '{"service": "cdown", "date": "26/04/2022"}',
    ]);
  }

  /**
   * Create a Webtools generic media with default values, ready to use in tests.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\media\MediaInterface
   *   The media entity.
   */
  protected function createWebtoolsGenericMedia(array $values = []): MediaInterface {
    $values['bundle'] = 'webtools_generic';

    return $this->createMedia($values + [
      'name' => 'Webtools generic title',
      'oe_media_webtools' => '{"service":"share"}',
    ]);
  }

  /**
   * Create a document media with default values, ready to use in tests.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\media\MediaInterface
   *   The media entity.
   */
  protected function createDocumentMedia(array $values = []): MediaInterface {
    $values['bundle'] = 'document';

    // If no file has been passed and the document type is local or not defined,
    // we create a sample pdf file and create a local document.
    if (!isset($values['oe_media_file']['target_id']) &&
      (!isset($values['oe_media_file_type']) || $values['oe_media_file_type'] === 'local')
    ) {
      $pdf = File::create([
        'uri' => \Drupal::service('file_system')->copy(
          \Drupal::service('extension.list.module')->getPath('oe_media') . '/tests/fixtures/sample.pdf',
          'public://sample.pdf'
        ),
      ]);
      $pdf->save();
      $this->markEntityForCleanup($pdf);

      $values['oe_media_file_type'] = 'local';
      $values['oe_media_file']['target_id'] = $pdf->id();
    }

    return $this->createMedia($values + [
      'name' => 'Document title',
    ]);
  }

  /**
   * Create an image media with default values, ready to use in tests.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\media\MediaInterface
   *   The media entity.
   */
  protected function createImageMedia(array $values = []): MediaInterface {
    $values['bundle'] = 'image';

    if (!isset($values['oe_media_image']['target_id'])) {
      $image = File::create([
        'uri' => \Drupal::service('file_system')->copy(
          \Drupal::root() . '/core/misc/druplicon.png',
          'public://image.png'
        ),
      ]);
      $image->save();
      $this->markEntityForCleanup($image);

      $values = NestedArray::mergeDeep([
        'oe_media_image' => [
          'target_id' => $image->id(),
          'alt' => 'Alt text',
        ],
      ], $values);
    }

    return $this->createMedia($values + [
      'name' => 'Image title',
    ]);
  }

}
