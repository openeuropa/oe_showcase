<?php

/**
 * @file
 * OE Showcase Search post updates.
 */

declare(strict_types = 1);

use Drupal\block\Entity\Block;

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
