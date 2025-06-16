<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase\Traits;

use Drupal\media\MediaInterface;
use Drupal\Tests\oe_whitelabel\Traits\MediaCreationTrait as WhitelabelMediaCreationTrait;
use weitzman\DrupalTestTraits\Entity\MediaCreationTrait as WeitzmanMediaCreationTrait;

/**
 * Contains methods to create media entities for testing.
 *
 * When adding a method to create a specific bundle, the method name MUST follow
 * the naming: "create" + bundle name in camel case + "Media".
 */
trait MediaCreationTrait {

  use WeitzmanMediaCreationTrait;
  use WhitelabelMediaCreationTrait {
    WeitzmanMediaCreationTrait::createMedia insteadof WhitelabelMediaCreationTrait;
    createDocumentMedia as whitelabelCreateDocumentMedia;
    createImageMedia as whitelabelCreateImageMedia;
  }

  /**
   * Creates one media for each existing media bundle.
   *
   * @return \Drupal\media\MediaInterface[]
   *   The created media, keyed by bundle.
   */
  protected function createTestMedia(): array {
    $bundles = \Drupal::entityTypeManager()->getStorage('media_type')->loadMultiple();
    $media = [];
    foreach (array_keys($bundles) as $bundle) {
      if ($bundle === 'webtools_social_feed') {
        // Deprecated media bundle, already removed in oe_webtools 1.36.0 and
        // will be removed in oe_media 2.0.
        continue;
      }
      $media[$bundle] = $this->createMediaByBundle($bundle);
    }

    return $media;
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
   * {@inheritdoc}
   */
  protected function createDocumentMedia(array $values = []): MediaInterface {
    $media = $this->whitelabelCreateDocumentMedia($values);
    $this->markEntityForCleanup($media->get('oe_media_file')->first()->entity);

    return $media;
  }

  /**
   * {@inheritdoc}
   */
  protected function createImageMedia(array $values = []): MediaInterface {
    $media = $this->whitelabelCreateImageMedia($values);
    $this->markEntityForCleanup($media->get('oe_media_image')->first()->entity);

    return $media;
  }

  /**
   * {@inheritdoc}
   */
  protected function createOeMediaPwbiMedia(array $values = []): MediaInterface {
    $values['bundle'] = 'oe_media_pwbi';

    $media = $this->createMedia($values + [
      'name' => 'My test report',
      'bundle' => 'power_bi_report',
      'status' => 1,
      'field_media_pwbi_embed_visual' => [
        0 => [
          'report_id' => 'report_id',
          'workspace_id' => 'report_id',
          'report_height' => '100',
          'report_layout' => '3',
          'report_width' => '100',
          'token_type' => 'Embed',
          'embed_type' => 'report',
          'report_width_units' => '%',
          'report_height_units' => 'px',
          'report_breakpoints_height' => 'a:3:{s:7:"pwbi.sm";a:1:{s:6:"height";s:3:"500";}s:7:"pwbi.md";a:1:{s:6:"height";s:3:"900";}s:7:"pwbi.lg";a:1:{s:6:"height";s:4:"1100";}}',
        ],
      ],
    ]);

    return $media;
  }

}
