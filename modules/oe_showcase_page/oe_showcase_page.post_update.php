<?php

/**
 * @file
 * OpenEuropa Showcase Page post updates.
 */

declare(strict_types = 1);

use Drupal\field\Entity\FieldConfig;

/**
 * Enable the social_media paragraph.
 */
function oe_showcase_page_post_update_00001(&$sandbox): void {
  $field = FieldConfig::load('node.oe_showcase_page.field_body');
  $settings = $field->getSetting('handler_settings');
  $settings['target_bundles']['oe_social_media_follow'] = 'oe_social_media_follow';
  $settings['target_bundles_drag_drop']['oe_social_media_follow']['enabled'] = TRUE;
  $field->setSetting('handler_settings', $settings);
  $field->save();
}
