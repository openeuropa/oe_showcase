<?php

declare(strict_types = 1);

namespace Drupal\oe_showcase;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Wrapper class for methods revolving around text format fields.
 */
class AllowedFormats implements ContainerInjectionInterface {

  use DependencySerializationTrait;

  /**
   * Creates a new instance of this class.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Alters a text format element in a field widget.
   *
   * @param array $element
   *   The element.
   * @param array $context
   *   The context.
   * @param string $expected_format
   *   The expected format.
   */
  public function textFormatAlter(array &$element, array $context, string $expected_format): void {
    // Bail out if it's a default value widget.
    if ($context['default']) {
      return;
    }

    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    $items = $context['items'];

    $entity = $items->getEntity();
    $current_format = $items[$context['delta']]->format;

    // Enforce the new format when:
    if (
      // The entity is new.
      $entity->isNew() ||
      // The current format matches the expected format.
      $current_format === $expected_format ||
      // The field item is empty.
      $current_format === NULL
    ) {
      // We need to set also the #format key because the timeline field widget,
      // instead of leaving an empty value for new entries, sets the fallback
      // text format already.
      $element['#format'] = $expected_format;
      $element['#allowed_formats'] = [$expected_format];
      return;
    }

    // To allow editors to change the value, we allow the current format and
    // the expected one.
    $element['#allowed_formats'] = [
      $current_format,
      $expected_format,
    ];

    $element['#oe_showcase_expected_format'] = $expected_format;
    $element['#element_validate'][] = [$this, 'validateTextFormat'];
  }

  /**
   * Validates the text format selected, preventing old formats to be kept.
   *
   * @param array $element
   *    The element being validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *    The current state of the form.
   * @param array $complete_form
   *    The complete form structure.
   * /
   */
  public function validateTextFormat(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    // The text format element creates a child element for the value, moving
    // there all the properties of the original element. To get to the text format
    // value, we need to move one element up.
    $path = array_slice($element['#parents'], 0, -1);
    $values = NestedArray::getValue($form_state->getValues(), $path);

    $expected_format = $element['#oe_showcase_expected_format'];
    if (!isset($values['format']) || $values['format'] === $expected_format) {
      return;
    }

    $format = $this->entityTypeManager->getStorage('filter_format')->load($expected_format);
    $path[] = 'format';
    $form_state->setErrorByName(implode('][', $path), t('The field %field_name supports only the %format format.', [
      '%field_name' => $element['#title'],
      '%format' => $format->label(),
    ]));
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
  public static function alterTextFormatHelp(array &$element, FormStateInterface $form_state, array &$complete_form) {
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

}
