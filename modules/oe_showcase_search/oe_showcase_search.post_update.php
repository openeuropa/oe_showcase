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
 * Set the updated values to the search form block.
 */
function oe_showcase_search_post_update_00002(&$sandbox) {
  $configs = [
    'block.block.oe_whitelabel_search_form',
    'block.block.showcase_header_search_form',
  ];
  ConfigImporter::importMultiple('module', 'oe_showcase_search', '/config/post_updates/00001', $configs);
}
