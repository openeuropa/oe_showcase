<?php

/**
 * @file
 * OpenEuropa Showcase User profile post updates.
 */

declare(strict_types = 1);

use Drupal\field\Entity\FieldConfig;
use Drupal\oe_bootstrap_theme\ConfigImporter;

/**
 * Remove gender field.
 */
function oe_showcase_user_profile_post_update_00001(&$sandbox): void {
  FieldConfig::loadByName('user', 'user', 'field_gender')->delete();

  // Remove rdf skos graph.
  $graph = [
    'name' => 'human-sex',
    'uri' => 'http://publications.europa.eu/resource/dataset/human-sex',
  ];
  $config = \Drupal::configFactory()->getEditable('rdf_skos.graphs');
  $entity_types = $config->get('entity_types');
  $changed = FALSE;
  foreach (['skos_concept_scheme', 'skos_concept'] as $type) {
    // Make sure the key exists.
    if (!isset($entity_types[$type])) {
      continue;
    }
    // Find if the graph is configured.
    $key = array_search($graph, $entity_types[$type]);
    if ($key !== FALSE) {
      unset($entity_types[$type][$key]);
      $changed = TRUE;
    }
  }
  // Save the configuration only if graphs were added.
  if ($changed) {
    $config->set('entity_types', $entity_types)->save();
  }

  ConfigImporter::importMultiple('module', 'oe_showcase_user_profile', '/config/post_updates/00001_remove_gender', [
    'core.entity_form_display.user.user.default',
    'core.entity_view_display.user.user.default',
    'core.entity_view_display.user.user.full',
    'core.entity_view_display.user.user.teaser',
  ]);
}
