<?php

/**
 * @file
 * OpenEuropa Showcase Multilingual post updates.
 */

declare(strict_types=1);

/**
 * Enable OE Webtools eTrans and add etrans block.
 */
function oe_showcase_multilingual_post_update_00001(&$sandbox) {
  \Drupal::service('module_installer')->install(['oe_webtools_etrans']);
}
