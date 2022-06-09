<?php

/**
 * @file
 * OpenEuropa Showcase post updates.
 */

declare(strict_types=1);

use Drupal\block\Entity\Block;
use Drupal\Core\Config\FileStorage;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
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
    // Allow editor to manage page content.
    'user.role.editor',
    // Create new role 'Manage contact forms'.
    'user.role.manage_contact_forms',
  ];
  ConfigImporter::importMultiple('profile', 'oe_showcase', '/config/post_updates/00005_update_roles', $configs);

  // Add new role to roleassign.
  $roleassign_config = \Drupal::configFactory()->getEditable('roleassign.settings');
  $roleassign_roles = $roleassign_config->get('roleassign_roles');
  $roleassign_roles['manage_contact_forms'] = 'manage_contact_forms';
  $roleassign_config->set('roleassign_roles', $roleassign_roles)->save();
}

/**
 * Simplify the page node bundle label.
 */
function oe_showcase_post_update_00006(): void {
  $type = NodeType::load('oe_showcase_page');
  $type->set('name', 'Page');
  $type->save();
}

/**
 * Update several fields.
 */
function oe_showcase_post_update_00007(): void {
  $configs = [
    // 'label' should be 'Orientation', not 'Variant'.
    'field.field.paragraph.oe_social_media_follow.field_oe_social_media_variant',
    // 'content/field_category/settings/link' should be false, not true.
    'core.entity_view_display.node.oe_showcase_search_demo.search_result',
  ];

  $storage = new FileStorage(drupal_get_path('profile', 'oe_showcase') . '/config/post_updates/00007_oesa_updates/');

  $field_storage = $storage->read('field.storage.node.oe_publication_date');
  $field_config = $storage->read('field.field.node.oe_sc_news.oe_publication_date');

  oe_showcase_change_field_type('oe_publication_date', $field_storage, $field_config);

  ConfigImporter::importMultiple('profile', 'oe_showcase', '/config/post_updates/00007_oesa_updates', $configs);
}

/**
 * Change the field type of date field to .
 *
 * @param string $field
 *   The machine name of the field.
 * @param array $new_field_storage
 *   The new field storage config to create.
 * @param array $new_field_config
 *   The field config to create.
 */
function oe_showcase_change_field_type(string $field, array $new_field_storage, array $new_field_config): void {
  // Check if we have any records in the field data table.
  $database = \Drupal::database();
  $count = $database->select("node__$field", 'n')
    ->countQuery()
    ->execute()
    ->fetchField();
  if ($count > 0) {
    // Create a table based on the one we want to back up. We are using direct
    // SQL queries to rely on the database server performance instead of using
    // the API as well in the chain.
    $data_table = "{node__$field}";
    $backup_data_table = "{_node__$field}";
    $query_string = 'CREATE TABLE ' . $backup_data_table . ' LIKE ' . $data_table;
    $database->query($query_string);

    // Copy every record from the field table to the backup table.
    $query_string = 'INSERT ' . $backup_data_table . ' SELECT * FROM ' . $data_table;
    $database->query($query_string);

    // Now do the same for the field revisions table.
    $revision_table = "{node_revision__$field}";
    $backup_revision_table = "{_node_revision__$field}";
    $query_string = 'CREATE TABLE ' . $backup_revision_table . ' LIKE ' . $revision_table;
    $database->query($query_string);
    $query_string = 'INSERT ' . $backup_revision_table . ' SELECT * FROM ' . $revision_table;
    $database->query($query_string);
  }

  // Delete the field and purge field data if there is any left over.
  /** @var \Drupal\field\Entity\FieldConfig $field_config */
  $field_config = FieldConfig::load("node.oe_sc_news.$field");
  $field_config->delete();
  field_purge_batch(50);

  // Save the new field.
  $new_field_storage = FieldStorageConfig::create($new_field_storage);
  $new_field_storage->save();
  $new_field_config = FieldConfig::create($new_field_config);
  $new_field_config->save();

  if ($count > 0) {
    // Now we need to account for an additional column for timezone, so we
    // change the schema of the backup table accordingly.
    $query_string = 'ALTER TABLE ' . $backup_data_table . ' ADD COLUMN ' . $field . '_timezone VARCHAR(255)';
    $database->query($query_string);
    $query_string = 'ALTER TABLE ' . $backup_revision_table . ' ADD COLUMN ' . $field . '_timezone VARCHAR(255)';
    $database->query($query_string);

    // Use the default site timezone if one is already configured, or fall
    // back to the system timezone.
    $default_timezone = \Drupal::config('system.date')->get('timezone.default') ?: @date_default_timezone_get();
    $query_string = 'UPDATE ' . $backup_data_table . ' SET ' . $field . '_timezone = \'' . $default_timezone . '\'';
    $database->query($query_string);
    $query_string = 'UPDATE ' . $backup_revision_table . ' SET ' . $field . '_timezone = \'' . $default_timezone . '\'';
    $database->query($query_string);

    // Restore existing data in the same field table.
    $query_string = 'INSERT ' . $data_table . ' SELECT * FROM ' . $backup_data_table;
    $database->query($query_string);
    $query_string = 'INSERT ' . $revision_table . ' SELECT * FROM ' . $backup_revision_table;
    $database->query($query_string);

    // Delete the backup tables from the database.
    $database->query('DROP TABLE ' . $backup_data_table);
    $database->query('DROP TABLE ' . $backup_revision_table);
  }
}
