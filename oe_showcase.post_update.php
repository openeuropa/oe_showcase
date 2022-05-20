<?php

/**
 * @file
 * OpenEuropa Showcase post updates.
 */

declare(strict_types=1);

use Drupal\block\Entity\Block;
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
 * Enable roleassign, Manage users role and update settings.
 */
function oe_showcase_post_update_00003(&$sandbox) {
  \Drupal::service('module_installer')->install(['roleassign']);
  \Drupal::configFactory()->getEditable('user.settings')
    ->set('register', 'visitors_admin_approval')
    ->save();

  $roleassign_config = \Drupal::configFactory()->getEditable('roleassign.settings');
  $roleassign_roles = $roleassign_config->get('roleassign_roles');
  $roleassign_roles['configure_page_feedback_form'] = 'configure_page_feedback_form';
  $roleassign_roles['editor'] = 'editor';
  $roleassign_roles['manage_users'] = '0';
  $roleassign_config->set('roleassign_roles', $roleassign_roles)->save();

  $configs = [
    'user.role.manage_users',
  ];
  ConfigImporter::importMultiple('profile', 'oe_showcase', '/config/post_updates/00003_manage_users', $configs);
}

/**
 * Revoke editor role's administer nodes permission.
 */
function oe_showcase_post_update_00004(&$sandbox) {
  $role = Role::load('editor');
  $role->revokePermission('administer nodes');
  $role->save();
}

/**
 * Manage permissions for content editing and for contact forms.
 */
function oe_showcase_post_update_00005(): void {
  // Update and create roles.
  // Also change role weights to make them alphabetic.
  $configs = [
    // Allow anonymous and authenticated to use corporate contact forms.
    'user.role.anonymous',
    'user.role.authenticated',
    // Allow editor to manage page content, and change role weight.
    'user.role.editor',
    // Create new role 'Manage contact forms'.
    'user.role.manage_contact_forms',
    // Change weight of 'Manage users' role.
    'user.role.manage_users',
  ];
  ConfigImporter::importMultiple('profile', 'oe_showcase', '/config/post_updates/00005_update_roles', $configs);

  // Add new role to roleassign.
  $roleassign_config = \Drupal::configFactory()->getEditable('roleassign.settings');
  $roleassign_roles = $roleassign_config->get('roleassign_roles');
  $roleassign_roles['manage_contact_forms'] = 'manage_contact_forms';
  $roleassign_config->set('roleassign_roles', $roleassign_roles)->save();
}
