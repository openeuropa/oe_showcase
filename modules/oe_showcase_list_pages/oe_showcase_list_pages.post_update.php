<?php

/**
 * @file
 * OE Showcase List pages post updates.
 */

declare(strict_types = 1);

use Drupal\oe_bootstrap_theme\ConfigImporter;

/**
 * Add alias pattern for list pages.
 */
function oe_showcase_list_pages_post_update_00001(&$sandbox): void {
  ConfigImporter::importSingle(
    'module',
    'oe_showcase_list_pages',
    '/config/post_updates/00001_url_alias',
    'pathauto.pattern.list_page_url_alias_pattern'
  );
}
