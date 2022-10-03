<?php

/**
 * @file
 * OpenEuropa Showcase post updates.
 */

declare(strict_types = 1);

use Drupal\block\Entity\Block;
use Drupal\Core\Config\FileStorage;
use Drupal\field\Entity\FieldConfig;
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
 * Update project-related settings.
 */
function oe_showcase_post_update_00007(): void {
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
  ConfigImporter::importSingle('profile', 'oe_showcase', '/config/post_updates/00007_project', 'pathauto.pattern.project_url_alias_pattern');
}

/**
 * Install OpenEuropa Showcase list pages module, and update configuration.
 */
function oe_showcase_post_update_00008(&$sandbox): void {
  \Drupal::service('module_installer')->install(['oe_showcase_list_pages']);

  // Allow editor role to manage list pages.
  $permissions = [
    'create oe_list_page content',
    'delete any oe_list_page content',
    'delete oe_list_page revisions',
    'edit any oe_list_page content',
    'revert oe_list_page revisions',
    'view oe_list_page revisions',
  ];
  $role = Role::load('editor');
  if ($role === NULL) {
    throw new \Exception("Role not found: 'editor'.");
  }
  foreach ($permissions as $permission) {
    $role->grantPermission($permission);
  }
  $role->save();
}

/**
 * Install 'dropsolid_purge', 'oe_dashboard_agent', 'redirect'.
 */
function oe_showcase_post_update_00009(): void {
  \Drupal::service('module_installer')->install([
    'dropsolid_purge',
    'oe_dashboard_agent',
    'redirect',
  ]);

  $storage = new FileStorage(\Drupal::service('extension.list.profile')->getPath('oe_showcase') . '/config/post_updates/00009_purge');
  $data = $storage->read('purge.plugins');

  $config_factory = \Drupal::configFactory();
  $config_factory->getEditable('purge.plugins')->setData($data)->save();
}

/**
 * Install 'oe_showcase_theme', and make it the default theme.
 */
function oe_showcase_post_update_00010(): void {
  // Use oe_showcase_theme instead of oe_whitelabel.
  \Drupal::service('theme_installer')->install(['oe_showcase_theme']);
  \Drupal::configFactory()
    ->getEditable('system.theme')
    ->set('default', 'oe_showcase_theme')
    ->save();
}

/**
 * Add an administrator admin role.
 */
function oe_showcase_post_update_00011(): void {
  $role = Role::create([
    'id' => 'administrator',
    'label' => 'Administrator',
    'is_admin' => TRUE,
  ]);
  $role->save();
}

/**
 * Create new role for manage menu items and assign permissions.
 */
function oe_showcase_post_update_00012(): void {
  $role = Role::create([
    'id' => 'manage_menu_items',
    'label' => 'Manage menu items',
    'is_admin' => FALSE,
    'permissions' => [
      'access administration pages',
      'access toolbar',
      'administer menu',
      'view the administration theme',
    ],
  ]);
  $role->save();

  // Add new role to roleassign.
  $roleassign_config = \Drupal::configFactory()->getEditable('roleassign.settings');
  $roleassign_roles = $roleassign_config->get('roleassign_roles');
  $roleassign_roles['manage_menu_items'] = 'manage_menu_items';
  $roleassign_config->set('roleassign_roles', $roleassign_roles)->save();
}

/**
 * Create new role for manage menu items and assign permissions.
 */
function oe_showcase_post_update_00013(): void {
  $role = Role::create([
    'id' => 'manage_site_specific_footer',
    'label' => 'Manage site specific footer',
    'is_admin' => FALSE,
    'permissions' => [
      'access administration pages',
      'access toolbar',
      'administer site specific footer link sections',
      'administer site specific footer links',
      'view the administration theme',
    ],
  ]);
  $role->save();

  // Add new role to roleassign.
  $roleassign_config = \Drupal::configFactory()->getEditable('roleassign.settings');
  $roleassign_roles = $roleassign_config->get('roleassign_roles');
  $roleassign_roles['manage_site_specific_footer'] = 'manage_site_specific_footer';
  $roleassign_config->set('roleassign_roles', $roleassign_roles)->save();
}

/**
 * Enable person content type. Grant permissions to editor.
 */
function oe_showcase_post_update_00014(): void {
  // Install starter content person module.
  \Drupal::service('module_installer')->install([
    'oe_whitelabel_starter_person',
  ]);

  // Allow editor role to manage person pages.
  $permissions = [
    'create oe_sc_person content',
    'delete any oe_sc_person content',
    'delete oe_sc_person revisions',
    'edit any oe_sc_person content',
    'revert oe_sc_person revisions',
    'view oe_sc_person revisions',
  ];
  $role = Role::load('editor');
  if ($role === NULL) {
    throw new \Exception("Role not found: 'editor'.");
  }
  foreach ($permissions as $permission) {
    $role->grantPermission($permission);
  }
  $role->save();

  // Configure text formats in rich text fields for person.
  $field = FieldConfig::load('node.oe_sc_person.oe_summary');
  if ($field === NULL) {
    throw new \Exception("Field not found: 'oe_summary'.");
  }
  $field->setThirdPartySetting('allowed_formats', 'allowed_formats', ['simple_rich_text']);
  $field->save();

  $field = FieldConfig::load('node.oe_sc_person.oe_sc_person_additional_info');
  if ($field === NULL) {
    throw new \Exception("Field not found: 'oe_sc_person_additional_info'.");
  }
  $field->setThirdPartySetting('allowed_formats', 'allowed_formats', ['rich_text']);
  $field->save();

  // Add person bundle to the social share block.
  $block = Block::load('oe_showcase_theme_social_share');
  $visibility = $block->getVisibility();
  if (isset($visibility['entity_bundle:node']['bundles'])) {
    $visibility['entity_bundle:node']['bundles']['oe_sc_person'] = 'oe_sc_person';
    $block->setVisibilityConfig('entity_bundle:node', $visibility['entity_bundle:node']);
    $block->save();
  }
}

/**
 * Enable embedding of medias in rich text fields.
 */
function oe_showcase_post_update_00015(): void {
  \Drupal::service('module_installer')->install([
    'oe_oembed',
    'entity_browser_enhanced',
  ]);

  $configs = [
    'core.entity_view_mode.media.showcase_embed',
    'core.entity_view_display.media.av_portal_photo.showcase_embed',
    'core.entity_view_display.media.av_portal_video.showcase_embed',
    'core.entity_view_display.media.document.showcase_embed',
    'core.entity_view_display.media.image.showcase_embed',
    'core.entity_view_display.media.remote_video.showcase_embed',
    'views.view.media_entity_browsers',
    'entity_browser.browser.embed_media',
    'embed.button.embed_media',
    'filter.format.rich_text',
    'editor.editor.rich_text',
  ];

  ConfigImporter::importMultiple('profile', 'oe_showcase', '/config/post_updates/00015_media_embed', $configs);

  // The entity_browser_enhanced module uses very simple configs that cannot
  // be imported.
  \Drupal::service('config.factory')->getEditable('entity_browser_enhanced.widgets.embed_media')
    ->setData([
      '67b95a37-7fac-4b2a-8449-0095a1255a98' => 'multiselect',
      'a75ddb75-5238-4cd9-abd5-932b3cb95a22' => 'multiselect',
    ])
    ->save();

  $editor = Role::load('editor');
  $editor->grantPermission('access embed_media entity browser pages');
  $editor->save();
}

/**
 * Add Event type and update filters.
 */
function oe_showcase_post_update_00016(): void {
  $configs = [
    'taxonomy.vocabulary.event_type',
    'field.storage.node.field_event_type',
    'field.field.node.oe_sc_event.field_event_type',
    'core.entity_form_display.node.oe_sc_event.default',
    'core.entity_view_display.node.oe_sc_event.oe_w_content_banner',
    'core.entity_view_display.node.oe_sc_event.teaser',
    'facets.facet.oelp_oe_sc_event__location',
    'facets.facet.oelp_oe_sc_event__oe_sc_event_dates',
    'facets.facet.oelp_oe_sc_event__type',
    'facets.facet.oelp_oe_sc_person__title',
    'facets.facet.oelp_oe_sc_project__budget',
    'facets.facet.oelp_oe_sc_project__end_date',
    'facets.facet.oelp_oe_sc_project__start_date',
    'facets.facet.oelp_oe_sc_project__status',
    'facets.facet.oelp_oe_sc_project__type',
    'search_api.index.oe_list_pages_index',
  ];
  ConfigImporter::importMultiple('profile', 'oe_showcase', '/config/post_updates/00016_event_type_and_filters', $configs);

  $editor = Role::load('editor');
  $editor->grantPermission('access taxonomy overview');
  $editor->grantPermission('create terms in event_type');
  $editor->grantPermission('delete terms in event_type');
  $editor->grantPermission('edit terms in event_type');
  $editor->save();
}

/**
 * Update pathauto node type conditions from node_type to entity_bundle.
 *
 * @see https://www.drupal.org/project/ctools/issues/3300682
 * @see https://www.drupal.org/project/pathauto/issues/3222776
 * @see pathauto_update_8108()
 */
function oe_showcase_post_update_00017(): void {
  // Load all pattern configuration entities.
  foreach (\Drupal::configFactory()->listAll('pathauto.pattern.') as $pattern_config_name) {
    $pattern_config = \Drupal::configFactory()->getEditable($pattern_config_name);

    // Loop patterns and swap the node_type plugin by the entity_bundle:node
    // plugin.
    if ($pattern_config->get('type') === 'canonical_entities:node') {
      $selection_criteria = $pattern_config->get('selection_criteria');
      foreach ($selection_criteria as $uuid => $condition) {
        if ($condition['id'] === 'node_type') {
          $pattern_config->set("selection_criteria.$uuid.id", 'entity_bundle:node');
          $pattern_config->save();
          break;
        }
      }
    }
  }
}

/**
 * Remove field description in Facts and figures paragraph type.
 */
function oe_showcase_post_update_00018(): void {
  $field_config_id = 'paragraph.oe_facts_figures.field_oe_paragraphs';
  $field_config = FieldConfig::load($field_config_id);
  $field_config->setDescription('');
  $field_config->save();
}

/**
 * Enable carousel functionality.
 */
function oe_showcase_post_update_00019(): void {
  \Drupal::service('module_installer')->install(['oe_paragraphs_carousel']);

  $field_config = FieldConfig::load('paragraph.oe_carousel_item.field_oe_title');
  $field_config->setRequired(FALSE);
  $field_config->save();

  ConfigImporter::importSingle('profile', 'oe_showcase', '/config/post_updates/00019_carousel', 'field.field.node.oe_showcase_page.field_body');
}

/**
 * Enable glossary module.
 */
function oe_showcase_post_update_00020(): void {
  \Drupal::service('module_installer')->install(['oe_showcase_glossary']);

  $editor = Role::load('editor');
  $editor->grantPermission('create terms in glossary');
  $editor->grantPermission('delete terms in glossary');
  $editor->grantPermission('edit terms in glossary');
  $editor->save();
}
