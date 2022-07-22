<?php

/**
 * @file
 * OpenEuropa Showcase List pages post updates.
 */

declare(strict_types = 1);

use Drupal\oe_bootstrap_theme\ConfigImporter;

/**
 * Add project filters.
 */
function oe_showcase_list_pages_post_update_00001() {
  $configs = [
    'facets.facet.oelp_oe_sc_project__budget',
    'facets.facet.oelp_oe_sc_project__end_date',
    'facets.facet.oelp_oe_sc_project__start_date',
    'facets.facet.oelp_oe_sc_project__status',
    'facets.facet.oelp_oe_sc_project__type',
    'search_api.index.oe_list_pages_index',
  ];
  ConfigImporter::importMultiple('module', 'oe_showcase_list_pages', '/config/post_updates/00001_project_filters', $configs);
}
