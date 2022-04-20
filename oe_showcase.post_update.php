<?php

/**
 * @file
 * OpenEuropa showcase post updates.
 */

declare(strict_types = 1);

/**
 * Implements hook_post_update_NAME().
 *
 * Install "OpenEuropa showcase listing pages" module.
 */
function oe_showcase_post_update_10001(&$sandbox): void {
  \Drupal::service('module_installer')->install(['oe_showcase_list_pages']);
  // Invalidate kernel container to make site info service available.
  \Drupal::service('kernel')->invalidateContainer();
}
