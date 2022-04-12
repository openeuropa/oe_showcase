<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_search\ExistingSite;

use Behat\Mink\Element\NodeElement;
use Drupal\Core\Url;
use Drupal\Tests\oe_showcase\ExistingSite\ShowcaseExistingSiteTestBase;

/**
 * Tests the OEL Search feature integration.
 */
class IntegrationTest extends ShowcaseExistingSiteTestBase {

  /**
   * Test the search page.
   */
  public function testSearchFeature() {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Visit the front page.
    $this->drupalGet(Url::fromRoute('<front>'));

    // Assert that the site-wide search form exists.
    $search_form = $assert->elementExists('css', '#oe-whitelabel-search-form');
    $search_input = $search_form->findField('Search');
    $this->assertNotNull($search_input);
    $this->assertTrue($search_input->hasClass('form-autocomplete'));
    $this->assertTrue($search_input->hasClass('required'));

    // Use the site-wide search form to search for 'Imputo'.
    $search_input->setValue('Imputo');
    $search_button = $search_form->find('css', '#submit');
    $this->assertNotNull($search_button);
    $search_button->click();

    // Assert search results.
    $this->assertSearchResultsTitle(2);
    $this->assertActiveFilterBadges([]);
    $this->assertSearchResults([
      'Imputo Neo Sagaciter',
      'Gemino Imputo',
    ]);
    $this->assertPagerLinks([]);

    // Go back to /search, to clear all filters.
    // @todo Use 'Clear' once OEL-1315 is resolved.
    $this->drupalGet('/search');

    // Assert that filter facets exist in the sidebar.
    $offcanvas = $assert->elementExists('css', '#bcl-offcanvas');
    $title = $offcanvas->find('css', 'h4');
    $this->assertSame('Filter Options', $title->getText());
    $assert->fieldExists('Category 1', $offcanvas);
    $assert->fieldExists('Category 2', $offcanvas);
    $assert->fieldExists('Category 3', $offcanvas);
    $assert->fieldExists('Category 4', $offcanvas);
    $assert->fieldExists('Category 5', $offcanvas);

    // Assert exposed sort widget.
    $assert->fieldExists('Sort by');

    $this->assertSearchResultsTitle(19);
    $this->assertActiveFilterBadges([]);
    $this->assertSearchResults([
      'Imputo Neo Sagaciter',
      'Distineo',
      'Quae Vulputate',
      'Abico Diam Jugis',
      'Obruo',
    ]);
    // @todo Assert "Last" once OEL-1316 is fixed.
    $this->assertPagerLinks(['1', '2', '3', '4', 'Next ›']);

    // Filter by category.
    $page->checkField('Category 2');
    $page->pressButton('Refine');

    $this->assertSearchResultsTitle(8);
    $this->assertActiveFilterBadges(['Category 2']);
    $this->assertSearchResults([
      'Imputo Neo Sagaciter',
      'Abico Diam Jugis',
      'Voco',
      'Comis Incassum',
      'Appellatio Immitto',
    ]);
    $this->assertPagerLinks(['1', '2', 'Next ›']);

    // Filter by date.
    $page->fillField('Date', '2021-08-01');
    $page->pressButton('Refine');

    $this->assertSearchResultsTitle(1);
    // @todo Expect additional badges for the date once OEL-662 is resolved.
    $this->assertActiveFilterBadges(['Category 2']);
    $this->assertSearchResults(['Imputo Neo Sagaciter']);
    $this->assertPagerLinks([]);

    // Clear active filters.
    $page->clickLink('Clear');

    $this->assertSearchResultsTitle(19);
    $this->assertActiveFilterBadges([]);
    $this->assertSearchResults([
      'Imputo Neo Sagaciter',
      'Distineo',
      'Quae Vulputate',
      'Abico Diam Jugis',
      'Obruo',
    ]);
    $this->assertPagerLinks(['1', '2', '3', '4', 'Next ›']);

    // Sort by publication date.
    $page->selectFieldOption('Sort by', 'Published on Asc');
    $page->pressButton('Apply');

    $this->assertSearchResultsTitle(19);
    $this->assertActiveFilterBadges([]);
    $this->assertSearchResults([
      'Hendrerit',
      'Mos',
      'Camur Tego Vulputate',
      'Gemino Imputo',
      'Macto Neque Virtus',
    ]);
    $this->assertPagerLinks(['1', '2', '3', '4', 'Next ›']);

    // Visit the second page of search results.
    $page->clickLink('2');

    $this->assertSearchResultsTitle(19);
    $this->assertActiveFilterBadges([]);
    $this->assertSearchResults([
      'Luctus Sit',
      'Appellatio Immitto',
      'Comis Incassum',
      'Voco',
      'Appellatio Camur',
    ]);
    $this->assertPagerLinks(['‹ Previous', '1', '2', '3', '4', 'Next ›'], 2);

    // Visit the last page.
    $page->clickLink('4');

    $this->assertSearchResultsTitle(19);
    $this->assertActiveFilterBadges([]);
    $this->assertSearchResults([
      'Abico Diam Jugis',
      'Quae Vulputate',
      'Distineo',
      'Imputo Neo Sagaciter',
    ]);
    $this->assertPagerLinks(['‹ Previous', '1', '2', '3', '4'], 4);
  }

  /**
   * Asserts the title above the search results.
   *
   * @param int $expected_count
   *   Expected number of results to be reported in the title.
   */
  protected function assertSearchResultsTitle(int $expected_count): void {
    $title = $this->assertSession()->elementExists('css', 'h4.mb-4');
    $this->assertSame(
      sprintf('Search results (%s)', $expected_count),
      $title->getText());
  }

  /**
   * Asserts badges for active filters.
   *
   * @param string[] $expected
   *   Expected badge labels.
   */
  protected function assertActiveFilterBadges(array $expected): void {
    $badges_close_icons = $this->assertSession()
      ->elementExists('css', '#block-oe-whitelabel-main-page-content')
      ->getParent()
      ->findAll('css', '.badge > .icon--close');
    $badges = array_map(
      static fn(NodeElement $item): NodeElement => $item->getParent(),
      $badges_close_icons);
    $this->assertElementsTexts($expected, $badges);
  }

  /**
   * Asserts search result items.
   *
   * @param array $expected
   *   Expected titles of search result items.
   */
  protected function assertSearchResults(array $expected): void {
    $items = $this->assertSession()
      ->elementExists('css', '.views-element-container')
      ->findAll('css', '.card-title');
    $this->assertElementsTexts($expected, $items);
  }

  /**
   * Asserts link text on pager links.
   *
   * @param string[] $expected
   *   Expected pager link texts.
   * @param int $active_item_index
   *   Index of a pager link with the 'active' class.
   */
  protected function assertPagerLinks(array $expected, int $active_item_index = 0): void {
    if ($expected === []) {
      $this->assertSession()
        ->elementNotExists('css', 'ul.pagination');
      return;
    }
    $links = $this->assertSession()
      ->elementExists('css', 'ul.pagination')
      ->findAll('css', '.page-item');
    $this->assertElementsTexts($expected, $links);
    $this->assertSame(
      [$active_item_index],
      array_keys(array_filter(
        $links,
        static fn(NodeElement $link): bool => $link->hasClass('active'),
      )),
      sprintf('Pager item %s is active, other items are not.', $active_item_index),
    );
  }

  /**
   * Asserts text contents for multiple elements at once.
   *
   * @param string[] $expected
   *   Expected element texts.
   * @param \Behat\Mink\Element\NodeElement[] $elements
   *   Elements to compare.
   */
  protected function assertElementsTexts(array $expected, array $elements): void {
    $this->assertSame($expected, array_map(
      static fn(NodeElement $element): string => $element->getText(),
      $elements,
    ));
  }

}
