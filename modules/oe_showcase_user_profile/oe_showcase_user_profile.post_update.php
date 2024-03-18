<?php

/**
 * @file
 * OpenEuropa Showcase User profile post updates.
 */

declare(strict_types=1);

use Drupal\field\Entity\FieldConfig;
use Drupal\oe_bootstrap_theme\ConfigImporter;

/**
 * Remove gender field.
 */
function oe_showcase_user_profile_post_update_00001(&$sandbox): void {
  FieldConfig::loadByName('user', 'user', 'field_gender')->delete();

  ConfigImporter::importMultiple('module', 'oe_showcase_user_profile', '/config/post_updates/00001_remove_gender', [
    'core.entity_form_display.user.user.default',
    'core.entity_view_display.user.user.default',
    'core.entity_view_display.user.user.full',
    'core.entity_view_display.user.user.teaser',
  ]);
}
