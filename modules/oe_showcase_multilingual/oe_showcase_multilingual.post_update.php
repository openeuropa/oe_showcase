<?php

/**
 * @file
 * OpenEuropa Showcase Multilingual post updates.
 */

declare(strict_types=1);

/**
 * Enable OE Webtools eTrans and add etrans block.
 */
function oe_showcase_multilingual_post_update_00001(&$sandbox) {
  \Drupal::service('module_installer')->install(['oe_webtools_etrans']);
}

/**
 * Place the Unified eTrans block in the content region.
 */
function oe_showcase_multilingual_post_update_00002(array &$sandbox) {
  $block_storage = \Drupal::entityTypeManager()->getStorage('block');

  // Remove the existing two blocks.
  $block_storage->load('oe_showcase_theme_content_language_switcher')?->delete();
  $block_storage->load('oe_showcase_theme_etrans')?->delete();

  // Only create it if it doesn't already exist.
  $block_id = 'oe_showcase_theme_etrans_unified';
  if ($block_storage->load($block_id)) {
    return;
  }

  /** @var \Drupal\block\Entity\Block $block */
  $block_storage->create([
    'id' => $block_id,
    'theme' => 'oe_showcase_theme',
    'plugin' => 'oe_webtools_etrans_unified',
    'region' => 'content',
    'weight' => -3,
    'status' => TRUE,
    'settings' => [
      'label_display' => 0,
    ],
  ])->save();
}
