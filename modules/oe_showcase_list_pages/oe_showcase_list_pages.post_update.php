<?php

/**
 * @file
 * OE Showcase List pages post updates.
 */

declare(strict_types=1);

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

/**
 * Add News type filter.
 */
function oe_showcase_list_pages_post_update_00002(): void {
  ConfigImporter::importSingle(
    'module',
    'oe_showcase_list_pages',
    '/config/post_updates/00002_news_type',
    'facets.facet.oelp_oe_sc_news__type'
  );
}

/**
 * Restore oe_list_pages_index search_api index.
 */
function oe_showcase_list_pages_post_update_00003(&$sandbox): void {
  ConfigImporter::importSingle(
    'module',
    'oe_showcase_list_pages',
    '/config/post_updates/00003_search_api',
    'search_api.index.oe_list_pages_index'
  );
}

/**
 * Add placeholders for facets.
 */
function oe_showcase_list_pages_post_update_00004(): void {
  ConfigImporter::importMultiple('module', 'oe_showcase_list_pages', '/config/post_updates/00004_facets', [
    'facets.facet.oelp_oe_sc_event__location',
    'facets.facet.oelp_oe_sc_event__title',
    'facets.facet.oelp_oe_sc_event__type',
    'facets.facet.oelp_oe_sc_news__title',
    'facets.facet.oelp_oe_sc_news__type',
    'facets.facet.oelp_oe_sc_person__title',
    'facets.facet.oelp_oe_sc_project__status',
    'facets.facet.oelp_oe_sc_project__type',
    'facets.facet.oelp_oe_sc_publication__keyword',
    'facets.facet.oelp_oe_sc_publication__type',
  ]);
}
