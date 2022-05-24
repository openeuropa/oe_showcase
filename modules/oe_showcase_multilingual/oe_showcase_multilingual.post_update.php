<?php

/**
 * @file
 * OpenEuropa Showcase Multilingual post updates.
 */

declare(strict_types=1);

use Drupal\oe_bootstrap_theme\ConfigImporter;

/**
 * Enable OE Webtools eTrans and add etrans block.
 */
function oe_showcase_multilingual_post_update_00001(&$sandbox) {
  \Drupal::service('module_installer')->install(['oe_webtools_etrans']);

  ConfigImporter::importSingle(
    'module',
    'oe_showcase_multilingual',
    '/config/post_updates/00001_webtools_etrans',
    'block.block.openeuropa_webtools_etrans'
  );
}
