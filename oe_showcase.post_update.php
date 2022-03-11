<?php

/**
 * @file
 * Post update functions for oe_showcase.
 */

declare(strict_types = 1);

use Drupal\oe_bootstrap_theme\ConfigImporter;

/**
 * Update fields for starter news and events.
 */
function oe_showcase_post_update_00001(array &$sandbox): void {
  $fields_delete = [
    'field.field.node.oe_event.body',
    'field.field.node.oe_event.oe_summary',
    'field.field.node.oe_news.body',
    'field.field.node.oe_news.oe_summary',
  ];
  // Remove fields.
  foreach ($fields_delete as $field) {
    \Drupal::configFactory()->getEditable($field)->delete();
  }

  $configs = [
    'block.block.openeuropa_content_language_switcher',
    'field.field.node.oe_sc_event.body',
    'field.field.node.oe_sc_event.oe_summary',
    'field.field.node.oe_sc_news.body',
    'field.field.node.oe_sc_news.oe_summary',
    'user.role.editor',
  ];
  // Import/create config.
  ConfigImporter::importMultiple('oe_showcase', '/config/post_updates/00001/', $configs, TRUE);
}
