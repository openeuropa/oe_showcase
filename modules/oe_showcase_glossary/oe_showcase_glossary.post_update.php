<?php

/**
 * @file
 * OpenEuropa Showcase Glossary post updates.
 */

declare(strict_types=1);

use Drupal\oe_bootstrap_theme\ConfigImporter;

/**
 * Add alias for Glossary term.
 */
function oe_showcase_glossary_post_update_00001(): void {
  ConfigImporter::importSingle('module', 'oe_showcase_glossary', '/config/post_updates/00001_term_alias', 'pathauto.pattern.glossary_term_url_alias_pattern');
}
