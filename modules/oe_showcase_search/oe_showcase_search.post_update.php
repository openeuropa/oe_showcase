<?php

/**
 * @file
 * OE Showcase Search post updates.
 */

declare(strict_types = 1);

use Drupal\block\Entity\Block;
use Drupal\oe_bootstrap_theme\ConfigImporter;

/**
 * Ensure search blocks are in the correct order.
 */
function oe_showcase_search_post_update_00001(): void {
  $blocks = [
    'showcase_exposed_sort_form' => 1,
    'showcase_facets_summary' => 0,
  ];

  foreach ($blocks as $id => $weight) {
    $block = Block::load($id);
    $block->set('weight', $weight);
    $block->save();
  }
}

/**
 * Update search form block and add second instance.
 */
function oe_showcase_search_post_update_00002(&$sandbox) {
  $block = Block::load('oe_whitelabel_search_form');
  $block->setVisibilityConfig('request_path', [
    'id' => 'request_path',
    'negate' => TRUE,
    'pages' => "/search\r\n/search/*",
  ]);
  $block->save();

  ConfigImporter::importSingle(
    'module',
    'oe_showcase_search',
    '/config/post_updates/00002',
    'block.block.showcase_header_search_form'
  );
}

/**
 * Update search field preserve.
 */
function oe_showcase_search_post_update_00003(): void {
  ConfigImporter::importSingle(
    'module',
    'oe_showcase_search',
    '/config/post_updates/00003',
    'views.view.showcase_search'
  );
}
