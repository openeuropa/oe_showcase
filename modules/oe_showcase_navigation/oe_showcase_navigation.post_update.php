<?php

/**
 * @file
 * OE Showcase Contact form post updates.
 */

declare(strict_types=1);

use Drupal\oe_bootstrap_theme\ConfigImporter;

/**
 * Override changes from oe_corporate_blocks.
 */
function oe_showcase_navigation_post_update_00001(&$sandbox): void {

  $configs = [
    'oe_corporate_blocks.footer_link.general.about_us',
    'oe_corporate_blocks.footer_link.general.contact_us',
  ];

  ConfigImporter::importMultiple('module', 'oe_showcase_navigation', '/config/overrides/', $configs);
}

/**
 * Remove ec_core_column footer section.
 */
function oe_showcase_navigation_post_update_00002(&$sandbox): void {
  $config = [
    'oe_corporate_blocks.footer_link.general.events',
    'oe_corporate_blocks.footer_link.general.funding_and_tenders',
    'oe_corporate_blocks.footer_link.general.jobs',
    'oe_corporate_blocks.footer_link.general.press_corner',
    'oe_corporate_blocks.footer_link.general.priorities',
    'oe_corporate_blocks.footer_link.general.topics',
    'oe_corporate_blocks.footer_link.section.ec_core_column',
  ];
  foreach ($config as $item) {
    \Drupal::configFactory()->getEditable($item)?->delete();
  }
}
