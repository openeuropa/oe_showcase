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
    $search_button = $search_form->find('css', 'button[type="submit"]');
    $this->assertNotNull($search_button);
    $search_button->click();

    // Assert search results.
    $this->assertSearchResultsTitle(2);
    $this->assertActiveFilterBadges([]);
    $this->assertSearchResults([
      'Imputo Neo Sagaciter',
      'Gemino Imputo',
    ]);
    $this->assertNoPager();

    // Go back to /search, to clear all filters.
    // @todo Use 'Clear' once OEL-1315 is resolved.
    $this->drupalGet('/search');

    // Assert that filter facets exist in the sidebar.
    $offcanvas = $assert->elementExists('css', '#bcl-offcanvas');
    $title = $offcanvas->find('css', 'h3');
    $this->assertSame('Filter Options', $title->getText());
    $assert->fieldExists('Category 1', $offcanvas);
    $assert->fieldExists('Category 2', $offcanvas);
    $assert->fieldExists('Category 3', $offcanvas);
    $assert->fieldExists('Category 4', $offcanvas);
    $assert->fieldExists('Category 5', $offcanvas);

    // Assert exposed sort widget.
    $assert->fieldExists('Sort by', $this->getSearchTopRegion());

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
    $this->assertPager(4);

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
    $this->assertPager(2);

    // Filter by another category.
    $page->checkField('Category 3');
    $page->pressButton('Refine');

    // Only check some basics.
    // Filters are combined with OR, therefore more results appear.
    $this->assertSearchResultsTitle(12);
    $this->assertActiveFilterBadges(['Category 2', 'Category 3']);

    // Uncheck the other category.
    $page->uncheckField('Category 3');
    $page->pressButton('Refine');

    $this->assertSearchResultsTitle(8);
    $this->assertActiveFilterBadges(['Category 2']);

    // Filter by date.
    $page->fillField('Date', '2021-08-01');
    $page->pressButton('Refine');

    $this->assertSearchResultsTitle(1);
    // @todo Expect additional badges for the date once OEL-662 is resolved.
    $this->assertActiveFilterBadges(['Category 2']);
    $this->assertSearchResults(['Imputo Neo Sagaciter']);
    $this->assertNoPager();

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
    $this->assertPager(4);

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
    $this->assertPager(4);

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
    $this->assertPager(4, 1);

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
    $this->assertPager(4, 3);
  }

  /**
   * Asserts the title above the search results.
   *
   * @param int $expected_count
   *   Expected number of results to be reported in the title.
   */
  protected function assertSearchResultsTitle(int $expected_count): void {
    $title = $this->getSearchTopRegion()->find('css', 'h4');
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
    $badges = $this->getSearchTopRegion()
      ->findAll('css', '.badge');
    $this->assertElementsTexts($expected, $badges);
  }

  /**
   * Gets the div element above the search results list.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   Element above the search results list.
   */
  protected function getSearchTopRegion(): NodeElement {
    $element = $this->assertSession()
      ->elementExists('css', '#block-oe-whitelabel-main-page-content')
      ->getParent()
      // Get the first child.
      ->find('xpath', 'div');
    return $element;
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
   * Asserts that no pager links exist.
   */
  protected function assertNoPager(): void {
    $this->assertSession()
      ->elementNotExists('css', 'ul.pagination');
  }

  /**
   * Asserts link text on pager links.
   *
   * @param int $total
   *   Total number of pages expected.
   * @param int $active
   *   Active page index.
   */
  protected function assertPager(int $total, int $active = 0): void {
    $pagination = $this->assertSession()
      ->elementExists('css', 'ul.pagination');

    $active_link = $pagination->find('css', 'li.active > a');
    $this->assertNotNull($active_link);
    $this->assertPagerLinkPageNumber($active, $active_link);

    $last_link = $pagination->find('css', 'li:last-child > a');
    $this->assertNotNull($last_link);
    $this->assertPagerLinkPageNumber($total - 1, $last_link);
  }

  /**
   * Asserts the page index in a pager link.
   *
   * @param int $expected
   *   Expected page number.
   * @param \Behat\Mink\Element\NodeElement $pager_link
   *   A link element that belongs to a pager.
   */
  protected function assertPagerLinkPageNumber(int $expected, NodeElement $pager_link): void {
    $href = $pager_link->getAttribute('href');
    $query_string = parse_url($href, PHP_URL_QUERY);
    parse_str($query_string, $query);
    // Compare as string, to detect unexpected values.
    $this->assertSame((string) $expected, $query['page'] ?? NULL);
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
