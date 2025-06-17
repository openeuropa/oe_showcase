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
