<?php

/**
 * @file
 * Profile file.
 */

declare(strict_types = 1);

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\filter\Entity\FilterFormat;
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

/**
 * Implements hook_field_widget_form_alter().
 *
 * Forces the correct text format for fields where more than one format is
 * allowed.
 */
function oe_showcase_field_widget_form_alter(&$element, FormStateInterface $form_state, $context) {
  // Bail out if it's a default value widget.
  if ($context['default']) {
    return;
  }

  /** @var \Drupal\Core\Field\FieldItemListInterface $items */
  $items = $context['items'];
  $field_definition = $items->getFieldDefinition();
  // Apply the code only on supported field types.
  // @see allowed_formats_field_widget_form_alter()
  if (!in_array($field_definition->getType(), _allowed_formats_field_types())) {
    return;
  }

  // If only one format is allowed, bail out.
  if (!isset($element['#allowed_formats']) || count($element['#allowed_formats']) < 2) {
    return;
  }

  // List of field "identifiers" and the expected text format.
  // An identifier is built with entity type ID, bundle ID and field name.
  $fields = [
    'paragraph.oe_accordion_item.field_oe_text_long' => 'rich_text',
    'paragraph.oe_list_item.field_oe_text_long' => 'rich_text',
    'paragraph.oe_rich_text.field_oe_text_long' => 'rich_text',
    'paragraph.oe_text_feature_media.field_oe_text_long' => 'rich_text',
    'paragraph.oe_timeline.field_oe_text_long' => 'rich_text',
  ];

  $identifier = implode('.', [
    $field_definition->getTargetEntityTypeId(),
    $field_definition->getTargetBundle(),
    $field_definition->getName(),
  ]);

  // Bail out if the current field is not in the list. This condition should
  // never be true as we have a test that checks which fields have more than
  // one allowed format.
  if (!isset($fields[$identifier])) {
    return;
  }

  $expected_format = $fields[$identifier];
  $entity = $items->getEntity();

  // If the entity is new, force the expected format.
  if ($entity->isNew()) {
    $element['#allowed_formats'] = [$expected_format];
    return;
  }

  // If the current format is the same as the expected, we can safely remove
  // the other formats. The format is NULL when no text has been entered, so
  // we force the format also in that case.
  $current_format = $items[$context['delta']]->format;
  if ($current_format === $expected_format || $current_format === NULL) {
    $element['#allowed_formats'] = [$expected_format];
    return;
  }

  $element['#element_validate'][] = '_oe_showcase_validate_text_format';
  $element['#ewcms_expected_format'] = $expected_format;
}

/**
 * Validates the chosen text format.
 *
 * @param array $element
 *   The element being validated.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 * @param array $complete_form
 *   The complete form structure.
 */
function _oe_showcase_validate_text_format(array &$element, FormStateInterface $form_state, array &$complete_form): void {
  // The text format element creates a child element for the value, moving
  // there all the properties of the original element. To get to the text format
  // value, we need to move one element up.
  $path = array_slice($element['#parents'], 0, -1);
  $values = NestedArray::getValue($form_state->getValues(), $path);

  $expected_format = $element['#ewcms_expected_format'];
  if (!isset($values['format']) || $values['format'] === $expected_format) {
    return;
  }

  $format = FilterFormat::load($expected_format);
  $path[] = 'format';
  $form_state->setErrorByName(implode('][', $path), t('The field %field_name supports only the %format format.', [
    '%field_name' => $element['#title'],
    '%format' => $format->label(),
  ]));
}

/**
 * Implements hook_element_info_alter().
 *
 * Add a custom process method to the TextFormat form element.
 */
function oe_showcase_element_info_alter(array &$types) {
  if (isset($types['text_format'])) {
    $types['text_format']['#process'][] = '_oe_showcase_alter_text_format_help';
  }
}

/**
 * After build callback to alter the Url of the help link on text areas.
 *
 * @param $element
 *   The element.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The form state.
 * @param $complete_form
 *   The complete form structure.
 *
 * @return array
 *   The altered element.
 *
 * @see https://github.com/openeuropa/oe_editorial/blob/master/oe_editorial.module#L27
 */
function _oe_showcase_alter_text_format_help(&$element, FormStateInterface $form_state, &$complete_form) {
  // Replace the format help links with custom ones that point to
  // the appropriate help page.
  if (isset($element['format']['format'])) {
    // Apply custom class to text format select field.
    $element['format']['format']['#attributes']['class'][] = 'text-format-filter-list';

    foreach ($element['format']['format']['#options'] as $allowed_format_id => $allowed_format_name) {
      // We add one link for each of the available format types.
      $element['format']['help'][$allowed_format_id] = [
        '#type' => 'link',
        '#title' => t('About the :format_name format', [':format_name' => $allowed_format_name]),
        '#url' => Url::fromRoute('filter.tips', ['filter_format' => $allowed_format_id]),
        '#attributes' => [
          'data-filter-format' => $allowed_format_id,
          'class' => [
            'filter-help-item',
            'filter-help-' . $allowed_format_id,
          ],
          'target' => '_blank',
        ],
      ];
    }
    // Remove the default static link.
    unset($element['format']['help']['about']);
  }

  // Remove the guidelines area.
  if (isset($element['format']['guidelines'])) {
    unset($element['format']['guidelines']);
  }
  return $element;
}
