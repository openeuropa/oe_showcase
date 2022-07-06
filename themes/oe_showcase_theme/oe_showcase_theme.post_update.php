<?php

/**
 * @file
 * OpenEuropa Showcase Theme post updates.
 */

declare(strict_types = 1);

use Drupal\block\Entity\Block;

/**
 * Add person bundle to the social share block.
 */
function oe_showcase_theme_post_update_00001(&$sandbox) {
  $block = Block::load('oe_showcase_theme_social_share');
  $block->setVisibilityConfig('entity_bundle:node', [
    'id' => 'entity_bundle:node',
    'bundles' => [
      'oe_sc_event' => 'oe_sc_event',
      'oe_sc_news' => 'oe_sc_news',
      'oe_sc_person' => 'oe_sc_person',
      'oe_showcase_page' => 'oe_showcase_page',
    ],
  ]);

  $block->save();
}
