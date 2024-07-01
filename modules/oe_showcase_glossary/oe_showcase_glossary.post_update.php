<?php

/**
 * @file
 * OpenEuropa Showcase Glossary post updates.
 */

declare(strict_types=1);

use Drupal\oe_bootstrap_theme\ConfigImporter;
use Drupal\taxonomy\Entity\Term;

/**
 * Add alias for Glossary term and enable automatic alias for existing terms.
 */
function oe_showcase_glossary_post_update_00001(array &$sandbox): void {
  // First interaction we import configuration and set max elements for batch.
  if (!isset($sandbox['total'])) {
    ConfigImporter::importSingle('module', 'oe_showcase_glossary', '/config/post_updates/00001_term_alias', 'pathauto.pattern.glossary_term_url_alias_pattern');

    $tids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'glossary')
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $sandbox['total'] = $tids;
    $sandbox['current'] = 0;
  }

  $tids = \Drupal::entityQuery('taxonomy_term')
    ->range($sandbox['current'], 50)
    ->condition('vid', 'glossary')
    ->accessCheck(FALSE)
    ->execute();

  foreach ($tids as $tid) {
    $term = Term::load($tid);
    // Enable automatic alias generation.
    $term->set('path', ['pathauto' => TRUE]);
    $term->save();

    $sandbox['current']++;
  }

  if ($sandbox['current'] >= $sandbox['total']) {
    $sandbox['#finished'] = 1;

    return;
  }

  $sandbox['#finished'] = ($sandbox['current'] / $sandbox['total']);
}
