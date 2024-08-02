<?php

/**
 * @file
 * OpenEuropa Showcase post updates.
 */

declare(strict_types=1);

use Drupal\block\Entity\Block;
use Drupal\Core\Config\FileStorage;
use Drupal\facets\Entity\Facet;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\oe_bootstrap_theme\ConfigImporter;
use Drupal\search_api\Entity\Index;
use Drupal\user\Entity\Role;
use Drupal\views\Entity\View;

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

  $view = View::load('taxonomy_term');
  $view->disable();
  $view->save();
}

/**
 * Enable the gallery paragraph.
 */
function oe_showcase_post_update_00021(): void {
  \Drupal::service('module_installer')->install(['oe_paragraphs_gallery']);

  $field_config = FieldConfig::load('paragraph.oe_gallery.field_oe_title');
  $field_config->setRequired(TRUE)->save();

  $field_storage = FieldStorageConfig::load('paragraph.field_oe_gallery_items');
  $field_storage->setCardinality(50)->save();

  Drupal::service('config.factory')->getEditable('entity_browser_enhanced.widgets.images_and_videos')
    ->setData([
      'ccf4f22e-62bf-4051-b43c-d43c707be09f' => 'multiselect',
      '482cc0f3-5652-4dbb-8158-2df53fc935a7' => 'multiselect',
    ])
    ->save();

  $configs = [
    'core.entity_view_mode.media.oe_sc_entity_browser_selection',
    'core.entity_view_display.media.av_portal_photo.oe_sc_entity_browser_selection',
    'core.entity_view_display.media.av_portal_video.oe_sc_entity_browser_selection',
    'core.entity_view_display.media.image.oe_sc_entity_browser_selection',
    'core.entity_view_display.media.remote_video.oe_sc_entity_browser_selection',
    'core.entity_form_display.paragraph.oe_gallery.default',
    'entity_browser.browser.images_and_videos',
    'field.field.node.oe_showcase_page.field_body',
    'field.field.paragraph.oe_content_row.field_oe_paragraphs',
    'views.view.media_entity_browsers',
  ];

  ConfigImporter::importMultiple('profile', 'oe_showcase', '/config/post_updates/00022_gallery', $configs);

  $editor = Role::load('editor');
  $editor->grantPermission('access images_and_videos entity browser pages');
  $editor->save();
}

/**
 * Update the media library image style.
 */
function oe_showcase_post_update_00022(): void {
  ConfigImporter::importSingle('profile', 'oe_showcase', '/config/post_updates/00023_media_library', 'image.style.media_entity_browser_thumbnail');
}

/**
 * Fix allowed formats in formatted text fields.
 */
function oe_showcase_post_update_00023(): void {
  $field_storage = \Drupal::entityTypeManager()->getStorage('field_config');
  // To prevent field lock during editing, set multiple formats to all the
  // fields that are already used in production. The first format is the real
  // allowed one, while the others are the current value (note that order is
  // just for sake of understanding easier which format is what).
  // @see https://github.com/ec-europa/ewcms/blob/develop/post_updates/ewcms_post_update_200.inc#L2096
  $fields = [
    'paragraph.oe_accordion_item.field_oe_text_long' => [
      'rich_text',
      'full_html',
    ],
    'paragraph.oe_list_item.field_oe_text_long' => [
      'simple_rich_text',
      'plain_text',
    ],
    'paragraph.oe_rich_text.field_oe_text_long' => [
      'rich_text',
      'full_html',
    ],
    'paragraph.oe_text_feature_media.field_oe_text_long' => [
      'rich_text',
      'full_html',
    ],
    'paragraph.oe_timeline.field_oe_text_long' => [
      'simple_rich_text',
    ],
  ];

  foreach ($fields as $field_name => $text_formats) {
    /** @var \Drupal\Core\Field\FieldConfigInterface $field_instance */
    $field_instance = $field_storage->load($field_name);
    if (!$field_instance) {
      continue;
    }
    $field_instance->setThirdPartySetting('allowed_formats', 'allowed_formats', $text_formats);
    $field_instance->save();
  }

  // Enable the format help text for listing item description.
  $configs = [
    'core.entity_form_display.paragraph.oe_list_item.default',
    'core.entity_form_display.paragraph.oe_list_item.highlight',
  ];
  ConfigImporter::importMultiple('profile', 'oe_showcase', '/config/post_updates/00022_text_formats', $configs);

  // Full html format is risky, so we disable it.
  $role = Role::load('editor');
  $role->revokePermission('use text format full_html');
  $role->save();

  $format = FilterFormat::load('full_html');
  $format->disable();
  $format->save();
}

/**
 * Enable the publication content type.
 */
function oe_showcase_post_update_00024(): void {
  \Drupal::service('module_installer')->install(['oe_whitelabel_starter_publication']);

  $configs = [
    'views.view.media_entity_browsers',
    'entity_browser.browser.documents',
    'entity_browser.browser.images',
    'taxonomy.vocabulary.publication_type',
    'field.storage.node.field_publication_authors',
    'field.storage.node.field_publication_type',
    'field.field.node.oe_sc_publication.field_publication_authors',
    'field.field.node.oe_sc_publication.field_publication_type',
    'core.entity_form_display.node.oe_sc_publication.default',
    'core.entity_view_display.node.oe_sc_publication.full',
    'core.entity_view_display.node.oe_sc_publication.oe_w_content_banner',
    'core.entity_view_display.node.oe_sc_publication.teaser',
    'facets.facet.oelp_oe_sc_publication__keyword',
    'facets.facet.oelp_oe_sc_publication__publication_date',
    'facets.facet.oelp_oe_sc_publication__type',
    'search_api.index.oe_list_pages_index',
  ];
  ConfigImporter::importMultiple('profile', 'oe_showcase', '/config/post_updates/00022_publication_content_type', $configs);

  Drupal::service('config.factory')->getEditable('entity_browser_enhanced.widgets.documents')
    ->setData([
      '5b99b16c-0710-4271-9c45-deed0c6bcfaa' => 'multiselect',
    ])
    ->save();

  Drupal::service('config.factory')->getEditable('entity_browser_enhanced.widgets.images')
    ->setData([
      '309d157b-59e7-4bf6-8c9c-fb7f6db9bf6d' => 'multiselect',
      'a1713f16-c893-4baf-ac6d-949d4df818e3' => 'multiselect',
    ])
    ->save();

  $text_fields = [
    'body' => 'rich_text',
    'oe_summary' => 'simple_rich_text',
  ];
  foreach ($text_fields as $field_name => $text_format) {
    $field_id = "node.oe_sc_publication.$field_name";
    $field = FieldConfig::load($field_id);
    $field->setThirdPartySetting('allowed_formats', 'allowed_formats', [$text_format]);
    $field->save();
  }

  $permissions = [
    'create oe_sc_publication content',
    'delete any oe_sc_publication content',
    'delete oe_sc_publication revisions',
    'edit any oe_sc_publication content',
    'revert oe_sc_publication revisions',
    'view oe_sc_publication revisions',
    'create terms in publication_type',
    'delete terms in publication_type',
    'edit terms in publication_type',
    'access documents entity browser pages',
    'access images entity browser pages',
  ];
  $role = Role::load('editor');
  foreach ($permissions as $permission) {
    $role->grantPermission($permission);
  }
  $role->save();
}

/**
 * Add location to Event teaser.
 */
function oe_showcase_post_update_00025(): void {
  ConfigImporter::importSingle('profile', 'oe_showcase', '/config/post_updates/00025_event_location', 'core.entity_view_display.node.oe_sc_event.teaser');
}

/**
 * Project updates.
 */
function oe_showcase_post_update_00026(): void {
  $facet = Facet::load('oelp_oe_sc_project__budget');
  $facet->delete();

  // Add Project bundle to the social share block.
  $block = Block::load('oe_showcase_theme_social_share');
  $visibility = $block->getVisibility();
  if (isset($visibility['entity_bundle:node']['bundles'])) {
    $visibility['entity_bundle:node']['bundles']['oe_project'] = 'oe_project';
    $block->setVisibilityConfig('entity_bundle:node', $visibility['entity_bundle:node']);
    $block->save();
  }
}

/**
 * Enable Slim Select.
 */
function oe_showcase_post_update_00027(): void {
  \Drupal::service('module_installer')->install(['slim_select']);
}

/**
 * Add users access to Patterns overview page.
 */
function oe_showcase_post_update_00028(): void {
  $roles = [
    'anonymous',
    'authenticated',
  ];

  foreach ($roles as $role) {
    $role = Role::load($role);
    $role->grantPermission('access patterns page');
    $role->save();
  }
}

/**
 * Update pathauto entities to remove deprecated and duplicated node_type entry.
 */
function oe_showcase_post_update_00029(): void {
  ConfigImporter::importMultiple('profile', 'oe_showcase', '/config/post_updates/00029_pathauto', [
    'pathauto.pattern.page_url_alias_pattern',
    'pathauto.pattern.project_url_alias_pattern',
    'pathauto.pattern.search_demo_url_alias_pattern',
  ]);
}

/**
 * Set the correct default user cancel method.
 */
function oe_showcase_post_update_00030(): void {
  \Drupal::configFactory()
    ->getEditable('user.settings')
    ->set('cancel_method', 'user_cancel_block')
    ->save();
}

/**
 * Set only the correct index as source for list pages.
 */
function oe_showcase_post_update_00031(): void {
  /** @var \Drupal\search_api\Entity\SearchApiConfigEntityStorage $storage */
  $storage = \Drupal::entityTypeManager()->getStorage('search_api_index');

  foreach ($storage->loadMultiple() as $index) {
    $use_for_list_pages = $index->id() === 'oe_list_pages_index';
    $index->setThirdPartySetting('oe_list_pages', 'lists_pages_index', $use_for_list_pages);
    $index->save();
  }
}

/**
 * Enable Showcase base fields module, add default sorting for list pages.
 */
function oe_showcase_post_update_00032(): void {
  \Drupal::service('module_installer')->install(['oe_showcase_fields']);

  ConfigImporter::importMultiple('profile', 'oe_showcase', '/config/post_updates/00032_search', [
    'search_api.index.oe_list_pages_index',
    'node.type.oe_project',
    'node.type.oe_sc_event',
    'node.type.oe_sc_news',
    'node.type.oe_sc_person',
    'node.type.oe_sc_publication',
  ]);

  Index::load('oe_list_pages_index')->indexItems();
}

/**
 * Update banner paragraphs to use the new fields.
 */
function oe_showcase_post_update_00033(&$sandbox) {
  $storage = \Drupal::entityTypeManager()->getStorage('paragraph');

  if (!isset($sandbox['total'])) {
    $sandbox['ids'] = $storage->getQuery()
      ->condition('type', 'oe_banner')
      ->exists('field_oe_banner_type')
      ->accessCheck(FALSE)
      ->execute();

    $sandbox['current'] = 0;
    $sandbox['total'] = count($sandbox['ids']);
    if ($sandbox['total'] === 0) {
      $sandbox['#finished'] = 1;
      return 'No items to update.';
    }
  }

  $id = array_pop($sandbox['ids']);
  /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
  $paragraph = $storage->load($id);
  /** @var \Drupal\oe_paragraphs_banner\BannerParagraphUpdater $updater */
  $updater = \Drupal::service('oe_paragraphs_banner.paragraph_updater');
  $updater->updateParagraph($paragraph);

  $sandbox['current']++;
  $sandbox['#finished'] = $sandbox['current'] / $sandbox['total'];
  if ($sandbox['#finished'] >= 1) {
    return sprintf('Processed %s banner paragraphs.', $sandbox['current']);
  }
}

/**
 * Disable frontpage view.
 */
function oe_showcase_post_update_00034(): void {
  View::load('frontpage')->disable()->save();
}

/**
 * Add News type to News.
 */
function oe_showcase_post_update_00035(): void {
  ConfigImporter::importMultiple('profile', 'oe_showcase', '/config/post_updates/00035_news_type', [
    'core.entity_view_display.node.oe_sc_news.teaser',
    'core.entity_view_display.node.oe_sc_news.oe_w_content_banner',
    'core.entity_form_display.node.oe_sc_news.default',
    'search_api.index.oe_list_pages_index',
    'user.role.editor',
  ]);

  Index::load('oe_list_pages_index')->indexItems();
}

/**
 * Change label of person facet from Title to Name.
 */
function oe_showcase_post_update_00036(): void {
  ConfigImporter::importSingle('profile', 'oe_showcase', '/config/post_updates/00036_person_facet', 'facets.facet.oelp_oe_sc_person__title');
}

/**
 * Enable the subscriptions module.
 */
function oe_showcase_post_update_00037(): void {
  \Drupal::service('module_installer')->install([
    'oe_showcase_subscriptions',
    'symfony_mailer',
  ]);

  ConfigImporter::importMultiple('profile', 'oe_showcase', '/config/post_updates/00037_event_subscriptions', [
    'core.entity_view_display.node.oe_sc_event.default',
    'core.entity_view_display.node.oe_sc_event.full',
    'core.entity_view_display.node.oe_sc_event.oe_w_content_banner',
    'core.entity_view_display.node.oe_sc_event.teaser',
    'core.entity_view_display.node.oe_sc_publication.oe_w_content_banner',
    'symfony_mailer.mailer_policy._',
    'user.role.anonymous_subscriber',
  ]);

  $role = Role::load(Role::AUTHENTICATED_ID);
  $role->grantPermission('flag subscribe_node')
    ->grantPermission('unflag subscribe_node')
    ->save();
}

/**
 * Update the Media AV Portal URIs to include HTTPS.
 */
function oe_showcase_post_update_00038(): void {
  $config = \Drupal::configFactory()->getEditable('media_avportal.settings');
  $config->set('iframe_base_uri', 'https://ec.europa.eu/avservices/play.cfm');
  $config->set('photos_base_uri', 'https://ec.europa.eu/avservices/avs/files/video6/repository/prod/photo/store/');
  $config->save();
}

/**
 * Add editor to simple_rich_text.
 */
function oe_showcase_post_update_00039(): void {
  ConfigImporter::importMultiple('profile', 'oe_showcase', '/config/post_updates/00039_editor_simple_rich_text', [
    'editor.editor.simple_rich_text',
    'filter.format.simple_rich_text',
  ]);
}

/**
 * Add banner in list pages.
 */
function oe_showcase_post_update_00040(): void {
  ConfigImporter::importMultiple('profile', 'oe_showcase', '/config/post_updates/00040_list_pages_banner', [
    'field.field.node.oe_list_page.oe_featured_media',
    'field.field.node.oe_list_page.oe_summary',
    'core.entity_form_display.node.oe_list_page.default',
    'core.entity_view_display.node.oe_list_page.full',
    'core.entity_view_display.node.oe_list_page.oe_w_content_banner',
  ]);
}

/**
 * Enable Color Scheme module.
 */
function oe_showcase_post_update_00041(): void {
  \Drupal::service('module_installer')->install([
    'color_scheme_field',
  ]);

  ConfigImporter::importMultiple('profile', 'oe_showcase', '/config/post_updates/00041_color_scheme', [
    'field.storage.paragraph.field_color_scheme',
    'core.entity_form_display.paragraph.oe_accordion.default',
    'core.entity_form_display.paragraph.oe_banner.default',
    'core.entity_form_display.paragraph.oe_banner.oe_banner_image',
    'core.entity_form_display.paragraph.oe_banner.oe_banner_image_shade',
    'core.entity_form_display.paragraph.oe_banner.oe_banner_primary',
    'core.entity_form_display.paragraph.oe_description_list.default',
    'core.entity_form_display.paragraph.oe_document.default',
    'core.entity_form_display.paragraph.oe_facts_figures.default',
    'core.entity_form_display.paragraph.oe_links_block.default',
    'core.entity_form_display.paragraph.oe_list_item_block.default',
    'core.entity_form_display.paragraph.oe_quote.default',
    'core.entity_form_display.paragraph.oe_timeline.default',
    'field.field.paragraph.oe_accordion.field_color_scheme',
    'field.field.paragraph.oe_banner.field_color_scheme',
    'field.field.paragraph.oe_description_list.field_color_scheme',
    'field.field.paragraph.oe_document.field_color_scheme',
    'field.field.paragraph.oe_facts_figures.field_color_scheme',
    'field.field.paragraph.oe_links_block.field_color_scheme',
    'field.field.paragraph.oe_list_item_block.field_color_scheme',
    'field.field.paragraph.oe_quote.field_color_scheme',
    'field.field.paragraph.oe_timeline.field_color_scheme',
  ]);
}

/**
 * Update aliases for content types.
 */
function oe_showcase_post_update_00042(array &$sandbox): void {
  if (!isset($sandbox['total'])) {
    ConfigImporter::importMultiple('profile', 'oe_showcase', '/config/post_updates/00042_pathauto_patterns', [
      'message.template.node_event_update',
      'pathauto.pattern.event_url_alias_pattern',
      'pathauto.pattern.news_url_alias_pattern',
      'pathauto.pattern.page_url_alias_pattern',
      'pathauto.pattern.person_url_alias_pattern',
      'pathauto.pattern.project_url_alias_pattern',
      'pathauto.pattern.publication_url_alias_pattern',
    ]);

    $node_count = \Drupal::entityQuery('node')
      ->condition('type', [
        'oe_showcase_page',
        'oe_list_page',
        'oe_sc_event',
        'oe_sc_news',
        'oe_project',
        'oe_sc_publication',
        'oe_sc_person',
      ], 'IN')
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $sandbox['total'] = $node_count;
    $sandbox['current'] = 0;
  }

  $nodes = \Drupal::entityQuery('node')
    ->range($sandbox['current'], 50)
    ->condition('type', [
      'oe_showcase_page',
      'oe_list_page',
      'oe_sc_event',
      'oe_sc_news',
      'oe_project',
      'oe_sc_publication',
      'oe_sc_person',
    ], 'IN')
    ->accessCheck(FALSE)
    ->execute();

  foreach ($nodes as $node) {
    $node = Node::load($node);
    // Enable automatic alias generation.
    $node->set('path', ['pathauto' => TRUE]);
    $node->save();

    $sandbox['current']++;
  }

  if ($sandbox['current'] >= $sandbox['total']) {
    $sandbox['#finished'] = 1;

    return;
  }

  $sandbox['#finished'] = ($sandbox['current'] / $sandbox['total']);
}
