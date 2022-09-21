<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_glossary\ExistingSite;

use Behat\Mink\Element\NodeElement;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\PaginationPatternAssert;
use Drupal\Tests\oe_showcase\ExistingSite\ShowcaseExistingSiteTestBase;
use Drupal\Tests\oe_showcase\Traits\TraversingTrait;
use Drupal\Tests\oe_whitelabel\PatternAssertions\ContentBannerAssert;
use Symfony\Component\CssSelector\CssSelectorConverter;

/**
 * Tests the glossary functionality.
 */
class GlossaryTest extends ShowcaseExistingSiteTestBase {

  use TraversingTrait;

  /**
   * Tests the glossary view.
   */
  public function testGlossaryView(): void {
    $all_terms = $this->generateTerms();

    $this->drupalGet('/glossary');
    $assert_session = $this->assertSession();
    $view_wrapper = $assert_session->elementExists('css', '.glossary-view');

    // The CSS selector uses the converter with a default "descendant-or-self::"
    // prefix which makes impossible to check for direct children. At the same
    // time we cannot pass "> .selector" to the methods.
    // By using directly the CssSelectorConverter, we can pass "/" as prefix,
    // which allows to find direct children.
    $css = new CssSelectorConverter(TRUE);

    (new ContentBannerAssert())->assertPattern([
      'title' => 'Glossary',
    ], $assert_session->elementExists('xpath', $css->toXPath('.bcl-content-banner', '/'), $view_wrapper)->getOuterHtml());

    $expected_summary = [
      'variant' => 'glossary',
      'links' => [],
    ];

    // Prepare all the expected links in the summary.
    foreach ($all_terms as $character => $terms) {
      $expected_summary['links'][] = [
        'url' => Url::fromUri('internal:/glossary/' . $character)->toString(),
        'label' => mb_strtoupper((string) $character),
      ];
    }

    // When no arguments are passed to the view, the first character is active.
    $expected_summary['links'][0]['active'] = TRUE;

    $summary = $view_wrapper->findAll('xpath', $css->toXPath('.glossary-view__summary nav[role="navigation"]', '/'));
    $this->assertCount(1, $summary);
    /** @var \Behat\Mink\Element\NodeElement $summary */
    $summary = reset($summary);
    $pagination_assert = new PaginationPatternAssert();
    $pagination_assert->assertPattern($expected_summary, $summary->getOuterHtml());

    // Assert the exposed form fields.
    $exposed_form = $assert_session->elementExists('css', 'form.bef-exposed-form', $view_wrapper);
    $sort_by = $assert_session->selectExists('Sort by', $exposed_form);
    $this->assertEquals([
      'az' => 'A-Z',
      'za' => 'Z-A',
      'changed' => 'Latest update',
      'oldest' => 'Oldest update',
    ], $this->getSelectOptions($sort_by));
    $this->assertEquals([
      '20' => '20',
      '50' => '50',
    ], $this->getSelectOptions($assert_session->selectExists('Items per page', $exposed_form)));

    $active_letter = key($all_terms);
    // Safety check to make sure that the first character is not a number.
    $this->assertIsNotNumeric($active_letter);
    $this->assertViewResults($all_terms[$active_letter], $active_letter);

    $previous_index = $index = 0;
    foreach ($all_terms as $character => $terms) {
      // Click the summary link represented by this character.
      $summary->find('named_exact', ['link', mb_strtoupper((string) $character)])->click();

      // Set the current character as active link.
      $expected_summary['links'][$previous_index]['active'] = FALSE;
      $expected_summary['links'][$index]['active'] = TRUE;
      $pagination_assert->assertPattern($expected_summary, $summary->getOuterHtml());

      $this->assertViewResults($terms, (string) $character);

      $previous_index = $index++;
    }

    // Find the first character that has at least 3 items.
    reset($all_terms);
    do {
      $candidate = key($all_terms);
      next($all_terms);
    } while (count($all_terms[$candidate]) < 3);

    // Verify that the inverse sorting works.
    $summary->find('named_exact', ['link', mb_strtoupper((string) $candidate)])->click();
    $sort_by->selectOption('Z-A');
    $exposed_form->pressButton('Apply');
    $this->assertViewResults(array_reverse($all_terms[$candidate]), $candidate, 'za');

    // Create a new term with a very old update date.
    $glossary_vocabulary = Vocabulary::load('glossary');
    $old_term = $this->createTerm($glossary_vocabulary, [
      'name' => $candidate . $this->randomMachineName(),
      'langcode' => 'en',
      'changed' => 1,
    ]);

    // Test the two date-based sorts.
    $sort_by->selectOption('Latest update');
    $exposed_form->pressButton('Apply');
    $this->assertViewResults(array_merge($all_terms[$candidate], [$old_term]), $candidate, 'changed');
    $sort_by->selectOption('Oldest update');
    $exposed_form->pressButton('Apply');
    $this->assertViewResults(array_merge([$old_term], $all_terms[$candidate]), $candidate, 'oldest');
  }

  /**
   * Asserts the views results for a particular character.
   *
   * @param \Drupal\taxonomy\TermInterface[] $expected_terms
   *   The expected terms that will be presented as results.
   * @param string $expected_character
   *   The expected character that will be shown in the title.
   * @param string $expected_sort
   *   The expected sort. Used to build the pagination links.
   */
  protected function assertViewResults(array $expected_terms, string $expected_character, string $expected_sort = 'az'): void {
    $assert_session = $this->assertSession();
    $assert_session->elementTextEquals(
      'css',
      '.glossary-view > .glossary-view__results > h2.bcl-heading',
      sprintf('Starting with "%s" (%s)', mb_strtoupper($expected_character), count($expected_terms))
    );

    $pager_selector = '.glossary-view > .glossary-view__results > nav[role="navigation"]';
    if (count($expected_terms) < 21) {
      $this->assertViewsResultPage($expected_terms);
      $assert_session->elementNotExists('css', $pager_selector);
      return;
    }

    // Results are paged, 20 elements by page.
    $pages = array_chunk($expected_terms, 20);

    $expected_pagination = [
      'variant' => 'default',
      'alignment' => 'center',
      'links' => [],
    ];
    for ($i = 0; $i < count($pages); $i++) {
      $expected_pagination['links'][] = [
        'url' => sprintf('?sort_by=%s&items_per_page=20&page=%s', $expected_sort, $i),
        'label' => (string) ($i + 1),
        'active' => $i === 0,
      ];
    }
    $expected_pagination['links'][] = [
      'url' => sprintf('?sort_by=%s&items_per_page=20&page=1', $expected_sort),
      'label' => 'Next',
    ];
    $expected_pagination['links'][] = [
      'url' => sprintf('?sort_by=%s&items_per_page=20&page=%s', $expected_sort, count($pages) - 1),
      'icon' => 'chevron-double-right',
    ];

    $pagination = $assert_session->elementExists('css', $pager_selector);
    (new PaginationPatternAssert())->assertPattern($expected_pagination, $pagination->getOuterHtml());

    foreach ($pages as $index => $terms) {
      // Navigate to the next page if needed.
      if ($index > 0) {
        $assert_session->elementExists('named_exact', ['link', $index + 1], $pagination)->click();
      }

      $this->assertViewsResultPage($terms);
    }
  }

  /**
   * Asserts a single page of the results.
   *
   * @param \Drupal\taxonomy\TermInterface[] $expected_terms
   *   The expected terms.
   */
  public function assertViewsResultPage(array $expected_terms): void {
    $assert_session = $this->assertSession();
    $content_wrapper = $assert_session->elementExists('css', '.glossary-view > .glossary-view__results');

    $card_elements = $content_wrapper->findAll('css', '.card');
    $this->assertSameSize($expected_terms, $card_elements);

    foreach ($expected_terms as $index => $term) {
      $this->assertSingleResult($term, $card_elements[$index]);
    }
  }

  /**
   * Asserts a single search result.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The term that should be rendered in the result.
   * @param \Behat\Mink\Element\NodeElement $element
   *   The element that wraps the markup.
   */
  protected function assertSingleResult(TermInterface $term, NodeElement $element): void {
    $assert_session = $this->assertSession();
    $this->assertEquals($term->label(), $assert_session->elementExists('css', '.card-title', $element)->getText());
    $this->assertEquals($term->toUrl()->toString(), $assert_session->elementExists('css', '.card-title a.standalone', $element)->getAttribute('href'));
    $this->assertEquals($term->label(), $assert_session->elementExists('css', '.card-title', $element)->getText());
  }

  /**
   * Generates glossary taxonomy terms.
   *
   * @return array|array[]
   *   A multidimensional array of terms, grouped by starting label character.
   */
  protected function generateTerms(): array {
    $glossary_vocabulary = Vocabulary::load('glossary');

    $initials = [];
    $letter_pool = array_merge(range(65, 90), range(97, 122));
    for ($i = 0; $i < 25; $i++) {
      // Pick a random letter.
      $index = array_rand($letter_pool);
      // Add the letter with a random amount of terms to create for it.
      $initials[chr($letter_pool[$index])] = rand(1, 5);
      // Remove the "used" letter from the pool.
      array_splice($letter_pool, $index, 1);
    }

    // To test that the summary is generated only using characters without
    // diacritics, we make sure the letter î replaces the i.
    unset($initials['i']);
    unset($initials['I']);
    $initials['î'] = 1;
    // Always add the character c and with ç.
    $initials['c'] = rand(1, 5);
    $initials['ç'] = rand(1, 5);
    // Add a special character.
    $initials['Æ'] = rand(1, 5);
    // Add one entry with more than 20 terms in it, to trigger paging.
    $initials[array_rand($initials)] = 41;

    $terms_by_letter = [];
    foreach ($initials as $character => $num_terms) {
      $terms = [];
      for ($i = 0; $i < $num_terms; $i++) {
        $terms[] = $this->createTerm($glossary_vocabulary, [
          'name' => $character . $this->randomMachineName(),
          'langcode' => 'en',
        ]);
      }

      // Merge the terms starting with the same letter, ignoring the case.
      $lowercase = mb_strtolower((string) $character);
      $terms_by_letter[$lowercase] = array_merge($terms_by_letter[$lowercase] ?? [], $terms);
    }

    // Diacritics will be ignored by MySQL, so we merge the entries of c and ç.
    $terms_by_letter['c'] = array_merge($terms_by_letter['c'], $terms_by_letter['ç']);
    unset($terms_by_letter['ç']);
    // And we replace the î with i.
    $terms_by_letter['i'] = $terms_by_letter['î'];
    unset($terms_by_letter['î']);

    $terms_by_number = [];
    for ($i = 0; $i < 10; $i++) {
      // Make sure there's one entry with the number 0.
      $starting_number = $i ? rand(0, 9) : 0;

      $terms_by_number += [$starting_number => []];
      $terms_by_number[$starting_number][] = $this->createTerm($glossary_vocabulary, [
        'name' => $starting_number . $this->randomMachineName(),
        'langcode' => 'en',
      ]);
    }

    // Sort the terms alphabetically.
    $this->sortTerms($terms_by_letter, 'letter');
    $this->sortTerms($terms_by_number, 'number');

    // The characters in the summary are alphabetically sorted, with numbers
    // last.
    ksort($terms_by_letter);
    ksort($terms_by_number);

    return $terms_by_letter + $terms_by_number;
  }

  /**
   * Sorts terms alphabetically.
   *
   * @param array $terms
   *   The terms to sort, organised in a multi-level array, with characters as
   *   first level and taxonomy terms in the second level.
   */
  protected function sortTerms(array &$terms): void {
    $transliteration = \Drupal::transliteration();

    $sort_by_label = function (TermInterface $a, TermInterface $b) use ($transliteration): int {
      return strcasecmp($transliteration->removeDiacritics($a->label()), $transliteration->removeDiacritics($b->label()));
    };
    array_walk($terms, function (array &$terms) use ($sort_by_label) {
      usort($terms, $sort_by_label);
    });
  }

}
