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
  $entityTypeManager = \Drupal::service('entity_type.manager');
  $types = [];
  $contentTypes = $entityTypeManager->getStorage('node_type')->loadMultiple();
  foreach ($contentTypes as $contentType) {
    $types[$contentType->id()] = $contentType->label();
    dump($types);
  }
  $configs = [
    'block.block.openeuropa_content_language_switcher.yml',
    'field.field.node.oe_sc_event.body.yml',
    'field.field.node.oe_sc_event.oe_summary.yml',
    'field.field.node.oe_sc_news.body.yml',
    'field.field.node.oe_sc_news.oe_summary.yml',
    'user.role.editor.yml',
  ];

  ConfigImporter::importMultiple('oe_showcase', '/config/post_updates/00001/', $configs, TRUE);
}
