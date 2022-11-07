<?php

/**
 * @file
 * Profile file.
 */

declare(strict_types = 1);

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\views\Entity\View;

/**
 * Implements hook_install_tasks_alter().
 */
function oe_showcase_install_tasks_alter(&$tasks, $install_state): void {
  // Do not download UI and config translations at installation time.
  unset($tasks['install_config_download_translations']);
  unset($tasks['install_import_translations']);
  unset($tasks['install_finish_translations']);

  $tasks['oe_showcase_disable_taxonomy_term_view'] = [];
}

/**
 * Disables the taxonomy term canonical page override provided by Views.
 *
 * @param array $install_state
 *   An array of information about the current installation state.
 */
function oe_showcase_disable_taxonomy_term_view(array &$install_state): void {
  $view = View::load('taxonomy_term');
  if (!$view || $view->status() === FALSE) {
    return;
  }

  $view->disable();
  $view->save();
}

/**
 * Implements hook_form_FORM_ID_alter() for the permissions form.
 *
 * Alter the permissions form to disable all options to perform any change.
 * Form and route is still active to guarantee the current permissions can be
 * seen.
 */
function oe_showcase_form_user_admin_permissions_alter(&$form, FormStateInterface $form_state): void {
  // Don't disable for uid=1.
  $user = \Drupal::currentUser();
  if ($user->id() == 1) {
    return;
  }

  // Disable submit button.
  $form['actions']['submit']['#access'] = FALSE;

  // Add warning message.
  \Drupal::messenger()->addMessage(t('Role management is disabled in OE Showcase. Roles and associated permissions are only changeable by users with Manage users role.'), MessengerInterface::TYPE_WARNING);

  // Get actual roles and permissions and disable the checkbox.
  $role_names = $form['role_names']['#value'];

  $permissions_handler = Drupal::service('user.permissions');
  $permissions = $permissions_handler->getPermissions();

  $permissions_by_provider = [];
  foreach ($permissions as $permission_name => $permission) {
    $permissions_by_provider[$permission['provider']][$permission_name] = $permission;
  }

  foreach ($role_names as $rid => $name) {
    foreach ($permissions_by_provider as $provider => $permissions) {
      foreach ($permissions as $perm => $permisssion) {
        $form['permissions'][$perm][$rid]['#attributes']['disabled'] = TRUE;
      }
    }
  }
}

/**
 * Implements hook_form_FORM_ID_alter() for the roles form.
 *
 * Alter the roles form to prevent role editing and deletion.
 */
function oe_showcase_form_user_admin_roles_form_alter(&$form, FormStateInterface $form_state): void {
  // Don't disable for uid=1.
  $user = \Drupal::currentUser();
  if ($user->id() == 1) {
    return;
  }

  if (empty($form['entities'])) {
    return;
  }

  foreach ($form['entities'] as &$entity) {
    if (is_array($entity) && isset($entity['operations']) && is_array($entity['operations'])) {

      if (isset($entity['operations']['data']['#links']['edit'])) {
        unset($entity['operations']['data']['#links']['edit']);
      }

      if (isset($entity['operations']['data']['#links']['delete'])) {
        unset($entity['operations']['data']['#links']['delete']);
      }
    }
  }
}

/**
 * Implements hook_form_FORM_ID_alter() for the CAS bulk user form.
 *
 * Alter the assignable roles.
 */
function oe_showcase_form_bulk_add_cas_users_alter(&$form, FormStateInterface $form_state): void {
  if (roleassign_restrict_access()) {
    // Get all roles that are available.
    $roles = user_role_names(TRUE);

    // Get roles that are available for assignment.
    $assignable_roles = array_intersect_key($roles, array_filter(\Drupal::config('roleassign.settings')
      ->get('roleassign_roles')));

    // Change assignable roles.
    $form['roles']['#options'] = $assignable_roles;
  }
}

/**
 * Implements hook_field_widget_WIDGET_TYPE_form_alter().
 *
 * Attaches extra styles to the entity browser entity reference widget.
 */
function oe_showcase_field_widget_entity_browser_entity_reference_form_alter(&$element, FormStateInterface $form_state, $context) {
  $element['#attached']['library'][] = 'oe_showcase/entity_browser.widget';
}

/**
 * Implements hook_form_FORM_ID_alter() for "entity_browser_form" form.
 *
 * Attaches extra styles to the entity browser modal form.
 */
function oe_showcase_form_entity_browser_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $form['#attached']['library'][] = 'oe_showcase/entity_browser.form';
}
