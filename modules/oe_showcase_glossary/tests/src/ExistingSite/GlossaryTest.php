<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_glossary\ExistingSite;

use Behat\Mink\Element\NodeElement;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\PaginationPatternAssert;
use Drupal\Tests\oe_showcase\ExistingSite\ShowcaseExistingSiteTestBase;
use Drupal\Tests\oe_whitelabel\PatternAssertions\ContentBannerAssert;
use Symfony\Component\CssSelector\CssSelectorConverter;

/**
 * Tests the glossary functionality.
 */
class GlossaryTest extends ShowcaseExistingSiteTestBase {

  /**
   * Tests the glossary view.
   */
  public function testGlossaryView(): void {
    $glossary_vocabulary = Vocabulary::load('glossary');

    $terms_by_letter = [];
    for ($i = 0; $i < 50; $i++) {
      $term = $this->createTerm($glossary_vocabulary, [
        'langcode' => 'en',
      ]);
      $letter = mb_strtolower(substr($term->label(), 0, 1));
      $terms_by_letter += [$letter => []];
      $terms_by_letter[$letter][] = $term;
    }

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
    $this->sortTerms($terms_by_letter);
    $this->sortTerms($terms_by_number);

    $this->drupalGet('/glossary');
    $assert_session = $this->assertSession();
    $view_wrapper = $assert_session->elementExists('css', '.glossary-view');

    // The CSS selector uses the converter with a default "descendant-or-self::"
    // prefix which makes impossible to check for direct children. At the same
    // time we cannot pass "> .selector" to the methods.
    $css = new CssSelectorConverter(TRUE);

    (new ContentBannerAssert())->assertPattern([
      'title' => 'Glossary',
    ], $assert_session->elementExists('xpath', $css->toXPath('.bcl-content-banner', '/'), $view_wrapper)->getOuterHtml());

    $expected_summary = [
      'variant' => 'glossary',
      'links' => [],
    ];

    // The characters in the summary are alphabetically sorted, with numbers
    // last.
    ksort($terms_by_letter);
    ksort($terms_by_number);
    $all_terms = $terms_by_letter + $terms_by_number;

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

    $active_letter = key($terms_by_letter);
    $this->assertViewResults($terms_by_letter[$active_letter], $active_letter);

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
  }

  /**
   * Asserts the views results for a particular character.
   *
   * @param \Drupal\taxonomy\TermInterface[] $expected_terms
   *   The expected terms that will be presented as results.
   * @param string $expected_character
   *   The expected character that will be shown in the title.
   */
  protected function assertViewResults(array $expected_terms, string $expected_character): void {
    $this->assertSession()->elementTextEquals(
      'css',
      '.glossary-view > .glossary-view__results > h2.bcl-heading',
      sprintf('Starting with "%s" (%s)', mb_strtoupper($expected_character), count($expected_terms))
    );

    // Results are paged, 20 elements by page.
    $pages = array_chunk($expected_terms, 20);
    foreach ($pages as $terms) {
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
   * Sorts terms alphabetically.
   *
   * @param array $terms
   *   The terms to sort, organised in a multi-level array, with characters as
   *   first level and taxonomy terms in the second level.
   */
  protected function sortTerms(array &$terms): void {
    $sort_by_label = function (TermInterface $a, TermInterface $b): int {
      return strnatcasecmp($a->label(), $b->label());
    };
    array_walk($terms, function (array &$terms) use ($sort_by_label) {
      uasort($terms, $sort_by_label);
    });
  }

}
