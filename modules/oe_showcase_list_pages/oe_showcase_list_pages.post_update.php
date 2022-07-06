<?php

/**
 * @file
 * OpenEuropa Showcase list pages post updates.
 */

declare(strict_types = 1);

use Drupal\oe_bootstrap_theme\ConfigImporter;

/**
 * Enable person facets.
 */
function oe_showcase_list_pages_post_update_00001(): void {
  $configs = [
    'facets.facet.oelp_oe_sc_person__title',
    'search_api.index.oe_list_pages_index',
  ];

  ConfigImporter::importMultiple('module', 'oe_showcase_list_pages', '/config/post_updates/00001_person_facets', $configs);
}
