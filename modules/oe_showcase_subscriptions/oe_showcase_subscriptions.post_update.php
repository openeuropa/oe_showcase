<?php

/**
 * @file
 * OE Showcase Subscriptions post updates.
 */

declare(strict_types=1);

use Drupal\oe_bootstrap_theme\ConfigImporter;

/**
 * Add alias pattern for list pages.
 */
function oe_showcase_subscriptions_post_update_00001(&$sandbox): void {
  ConfigImporter::importSingle(
    'module',
    'oe_showcase_subscriptions',
    '/config/post_updates/00001_aliased_url',
    'message.template.node_event_update'
  );
}
