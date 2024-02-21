<?php

/**
 * @file
 * OpenEuropa Showcase Fields post updates.
 */

declare(strict_types = 1);

use Drupal\oe_bootstrap_theme\ConfigImporter;

/**
 * Add the news type field to news node bundle.
 */
function oe_showcase_fields_post_update_00001(): void {
  ConfigImporter::importMultiple('module', 'oe_showcase_fields', '/config/post_updates/00001_news_type', [
    'taxonomy.vocabulary.news_type',
    'field.storage.node.field_news_type',
    'field.field.node.oe_sc_news.field_news_type',
  ]);
}
