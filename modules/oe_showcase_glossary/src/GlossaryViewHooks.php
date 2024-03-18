<?php

declare(strict_types=1);

namespace Drupal\oe_showcase_glossary;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contains all the hooks related to the glossary view.
 */
class GlossaryViewHooks implements ContainerInjectionInterface {

  /**
   * The glossary view ID.
   */
  protected const VIEW_ID = 'glossary_page';

  /**
   * The page display ID.
   */
  protected const PAGE_DISPLAY_ID = 'page_1';

  /**
   * The summary display ID.
   */
  protected const SUMMARY_DISPLAY_ID = 'attachment_1';

  /**
   * Construct a new instance of this class.
   *
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   */
  public function __construct(protected TransliterationInterface $transliteration) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('transliteration')
    );
  }

  /**
   * Checks if a view ID matches the glossary view ID.
   *
   * @param string $view_id
   *   A view ID.
   *
   * @return bool
   *   Whether the ID matches our glossary view ID.
   */
  protected function isGlossaryView(string $view_id): bool {
    return $view_id === self::VIEW_ID;
  }

  /**
   * Checks if a display ID matches the page display of our view.
   *
   * @param string $display_id
   *   A display ID.
   *
   * @return bool
   *   Whether the ID matches our page display ID.
   */
  protected function isPageDisplay(string $display_id): bool {
    return $display_id === self::PAGE_DISPLAY_ID;
  }

  /**
   * Checks if a display ID matches the summary display of our view.
   *
   * @param string $display_id
   *   A display ID.
   *
   * @return bool
   *   Whether the ID matches our summary display ID.
   */
  protected function isSummaryDisplay(string $display_id): bool {
    return $display_id === self::SUMMARY_DISPLAY_ID;
  }

  /**
   * Implements hook_views_pre_view().
   *
   * Selects the first available letter when no explicit selection is made in
   * the glossary page view.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view object about to be processed.
   * @param string $display_id
   *   The machine name of the active display.
   * @param array $args
   *   An array of arguments passed into the view.
   */
  public function preView(ViewExecutable $view, $display_id, array &$args): void {
    if (!$this->isGlossaryView($view->id()) || !$this->isPageDisplay($display_id)) {
      return;
    }

    // Always generate the total rows count. Useful when a mini-pager is used.
    $view->get_total_rows = TRUE;

    // No need to set a default arg when it's already present.
    if (!empty($args)) {
      return;
    }

    $summary_results = views_get_view_result('glossary_page', self::SUMMARY_DISPLAY_ID);
    if (empty($summary_results)) {
      return;
    }

    // Make sure the letter is lowercased, to match the configuration of our
    // contextual filter settings.
    $args[] = mb_strtolower($summary_results[0]->name_truncated);
  }

  /**
   * Implements hook_views_query_alter().
   *
   * Adds custom sorting for the summary entries and fallback sorting for the
   * results.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view object about to be processed.
   * @param \Drupal\views\Plugin\views\query\QueryPluginBase $query
   *   The query plugin object for the query.
   */
  public function queryAlter(ViewExecutable $view, QueryPluginBase $query): void {
    if (!$this->isGlossaryView($view->id())) {
      return;
    }

    // Alter the glossary summary display so that numbers are shown after
    // letters.
    if ($this->isSummaryDisplay($view->current_display)) {
      /** @var \Drupal\views\Plugin\views\query\Sql $query */
      $table_alias = $query->ensureTable('taxonomy_term_field_data');
      // Add an expression that returns 1 if a string starts with a number.
      $query->addField(NULL, "{$table_alias}.name REGEXP :regexp", 'is_number', [
        'placeholders' => [':regexp' => '^[0-9]'],
      ]);
      // Use the custom expression as first sort option, so that numbers are
      // moved last.
      array_unshift($query->orderby, [
        'field' => 'is_number',
        'direction' => 'ASC',
      ]);
    }

    // Add a fallback sort by name. This cannot be added in the View directly,
    // as it will override the exposed one. Without fallback, terms with the
    // same update date will not be sorted consistently.
    if ($this->isPageDisplay($view->current_display)) {
      $current_order_by = $query->orderby[0]['field'] ?? NULL;
      if ($current_order_by === 'taxonomy_term_field_data_name') {
        return;
      }
      $table_alias = $query->ensureTable('taxonomy_term_field_data');
      $query->addOrderBy($table_alias, 'name');
    }
  }

  /**
   * Implements hook_views_post_build().
   *
   * The main view argument has a default value. This value is not present in
   * the URL, so the glossary summary won't mark the correct letter as active.
   * The attachment view cannot access the parent view, so we need to save the
   * args for later usage.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view object about to be processed.
   */
  public function postBuild(ViewExecutable $view): void {
    if (!$this->isGlossaryView($view->id()) || !$this->isPageDisplay($view->current_display) || empty($view->args) || empty($view->attachment_before)) {
      return;
    }

    $view->attachment_before[0]['#main_display_args'] = $view->args;
  }

  /**
   * Implements hook_views_pre_render().
   *
   * Remove accents from glossary summary.
   * We use a post_execute hook so we can change the raw results before views
   * executes any processing on them, like lower/upper case, URL generation.
   * A pre_render hook is not good as we need this transliteration also to be
   * applied when the view is invoked using views_get_view_result() above.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view object about to be processed.
   */
  public function postExecute(ViewExecutable $view): void {
    if (!$this->isGlossaryView($view->id()) || !$this->isSummaryDisplay($view->current_display) || empty($view->result)) {
      return;
    }

    /** @var \Drupal\Component\Transliteration\TransliterationInterface $transliteration */
    $transliteration = \Drupal::service('transliteration');
    // The default database collation of Drupal for text fields is utf8mb4,
    // which ignores accents. But when the glossary is generated, letters with
    // accents could be retrieved, for example "ç" for all the words that start
    // with "c", "C", "ć".
    // We remove all the diacritics from the characters, so that we always have
    // consistent lettering.
    foreach ($view->result as $row) {
      $row->name_truncated = $transliteration->removeDiacritics($row->name_truncated);
    }
  }

  /**
   * Preprocess for the page display.
   *
   * Makes available to the template the character passed as argument to the
   * view and the total number of results.
   *
   * @param array $variables
   *   The variables array.
   */
  public function preprocessPageDisplay(array &$variables): void {
    /** @var \Drupal\views\ViewExecutable $view */
    $view = $variables['view'];
    // Views exposes values as tokens that can be replaced inside some text
    // areas.
    $tokens = $view->getDisplay()->getArgumentsTokens();
    $name_argument = $tokens['{{ arguments.name }}'] ?? NULL;
    $variables['character'] = $name_argument;
    $variables['total_results'] = $view->total_rows;
  }

  /**
   * Preprocess for the summary display.
   *
   * Marks the current character as active in the summary.
   *
   * @param array $variables
   *   The variables array.
   *
   * @see self::postBuild()
   */
  public function preprocessSummaryDisplay(array &$variables): void {
    $main_display_args = $variables['view']->element['#main_display_args'] ?? NULL;
    if (empty($main_display_args)) {
      return;
    }

    $initial = mb_strtolower($main_display_args[0]);
    // We need to loop all the items to check if there is any row marked as
    // active already. If none is active, we set as active the one that matches
    // the main view argument.
    $matching_index = NULL;
    foreach ($variables['rows'] as $index => $row) {
      if ($row->active) {
        return;
      }

      if (mb_strtolower($row->name_truncated) === $initial) {
        $matching_index = $index;
      }
    }

    if ($matching_index !== NULL) {
      $variables['rows'][$matching_index]->active = TRUE;
    }
  }

}
