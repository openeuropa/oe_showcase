<?php

/**
 * @file
 * OpenEuropa Showcase post updates.
 */

declare(strict_types=1);

use Drupal\block\Entity\Block;
use Drupal\field\Entity\FieldConfig;
use Drupal\oe_bootstrap_theme\ConfigImporter;
use Drupal\user\Entity\Role;

/**
 * Set the updated regions of the oe_whitelabel navigation blocks.
 */
function oe_showcase_post_update_00001(&$sandbox) {
  $blocks = [
    'oe_whitelabel_branding' => 'header_branding',
    'oe_whitelabel_eulogin' => 'header_top',
    'oe_whitelabel_language_switcher' => 'header_top',
    'oe_whitelabel_local_actions' => 'highlighted',
    'oe_whitelabel_local_tasks' => 'highlighted',
    'oe_whitelabel_messages' => 'highlighted',
    'oe_whitelabel_main_navigation' => 'navigation_left',
    'oe_whitelabel_search_form' => 'navigation_right',
  ];

  foreach ($blocks as $block_id => $region) {
    $block = Block::load($block_id);
    $block->setRegion($region);
    $block->enable();
    $block->save();
  }
}

/**
 * Enable OE Webtools Social share, Cookie consent and Page feedback components.
 */
function oe_showcase_post_update_00002(&$sandbox) {
  \Drupal::service('module_installer')->install([
    'oe_webtools_social_share',
    'oe_webtools_cookie_consent',
    'oe_webtools_page_feedback',
  ]);

  ConfigImporter::importSingle('module', 'oe_showcase', '/config/post_updates/00002_webtools_components', 'user.role.configure_page_feedback_form');
}

/**
 * Update project-related settings.
 */
function oe_showcase_post_update_00003(): void {
  // Install project-related modules.
  \Drupal::service('module_installer')->install([
    // All other relevant modules will be enabled as dependencies of this one.
    'oe_whitelabel_extra_project',
  ]);

  // Configure text formats in rich text fields in project ct.
  $field_names_by_text_format = [
    'rich_text' => [
      'body',
      'oe_cx_achievements_and_milestone',
      'oe_cx_impacts',
      'oe_cx_objective',
      'oe_project_results',
      'oe_summary',
    ],
    'simple_rich_text' => [
      'oe_teaser',
    ],
  ];
  foreach ($field_names_by_text_format as $text_format => $field_names) {
    foreach ($field_names as $field_name) {
      $field_id = "node.oe_project.$field_name";
      $field = FieldConfig::load($field_id);
      if ($field === NULL) {
        throw new \Exception("Field not found: '$field_id'.");
      }
      $field->setThirdPartySetting('allowed_formats', 'allowed_formats', [$text_format]);
      $field->save();
    }
  }

  // Configure permissions.
  $permissions_by_role = [
    'anonymous' => [
      'view published oe_organisation',
    ],
    'authenticated' => [
      'view published oe_organisation',
    ],
    'editor' => [
      'create oe_organisation oe_cx_project_stakeholder corporate entity',
      'create oe_organisation oe_stakeholder corporate entity',
      'create oe_project content',
      'delete any oe_project content',
      'delete oe_organisation oe_cx_project_stakeholder corporate entity',
      'delete oe_organisation oe_stakeholder corporate entity',
      'delete oe_project revisions',
      'edit any oe_project content',
      'edit oe_organisation oe_cx_project_stakeholder corporate entity',
      'edit oe_organisation oe_stakeholder corporate entity',
      'revert oe_project revisions',
      'view oe_project revisions',
      'view published oe_organisation',
      'view unpublished oe_organisation',
    ],
  ];
  foreach ($permissions_by_role as $role_name => $permissions) {
    $role = Role::load($role_name);
    if ($role === NULL) {
      throw new \Exception("Role not found: '$role_name'.");
    }
    foreach ($permissions as $permission) {
      $role->grantPermission($permission);
    }
    $role->save();
  }

  // Configure pathauto pattern.
  ConfigImporter::importSingle('profile', 'oe_showcase', '/config/post_updates/00003_project', 'pathauto.pattern.project_url_alias_pattern');
}
