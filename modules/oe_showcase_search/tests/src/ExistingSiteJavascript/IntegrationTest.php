<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_search\ExistingSiteJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\oe_showcase\ExistingSiteJavascript\ShowcaseExistingSiteJavascriptTestBase;
use Drupal\Tests\oe_showcase\Traits\SlimSelectTrait;
use Drupal\Tests\search_api\Functional\ExampleContentTrait;

/**
 * Tests the OEL Search feature integration.
 */
class IntegrationTest extends ShowcaseExistingSiteJavascriptTestBase {

  use ExampleContentTrait;
  use SlimSelectTrait;

  /**
   * Test that search page shows filters.
   */
  public function testSearchFilters() {
    $assert_session = $this->assertSession();

    // Create some News test nodes.
    for ($i = 0; $i < 5; $i++) {
      $this->createNode([
        'title' => 'News number ' . $i,
        'type' => 'oe_sc_news',
        'body' => 'This is a News content number ' . $i,
        'oe_publication_date' => sprintf('2022-04-%02d', $i + 1),
      ]);
    }

    // Create some Events test nodes.
    $countries = [
      'AF', 'BE', 'RO', 'DE', 'FR',
    ];
    for ($i = 0; $i < 5; $i++) {
      $term = $this->createTerm(
        Vocabulary::load('event_type'),
        ['name' => 'Event type ' . $i]
      );

      $this->createNode([
        'title' => 'Event number ' . $i,
        'type' => 'oe_sc_event',
        'field_event_type' => $term->id(),
        'oe_sc_event_dates' => [
          'value' => sprintf('2022-04-%02dT02:00:00', $i + 1),
          'end_value' => sprintf('2022-04-%02dT05:00:00', $i + 2),
        ],
        'oe_sc_event_location' => [
          'country_code' => $countries[$i],
          'address_line1' => 'Address line ' . $i,
          'postal_code' => '123 ' . $i,
          'locality' => 'Town' . $i,
        ],
      ]);
    }

    // Create some Publication test nodes.
    for ($i = 0; $i < 5; $i++) {
      $term = $this->createTerm(
        Vocabulary::load('publication_type'),
        ['name' => 'Publication type ' . $i]
      );

      $this->createNode([
        'title' => 'Publication ' . $i,
        'type' => 'oe_sc_publication',
        'field_publication_type' => $term->id(),
        'oe_publication_date' => sprintf('2022-04-%02d', $i + 1),
      ]);
    }

    // Create some Projects test nodes.
    $date_plus_1 = date('Y-m-d', strtotime('+1 day'));
    $date_plus_10 = date('Y-m-d', strtotime('+10 days'));
    $date_plus_10_calendar_format = date('m/d/Y', strtotime('+10 days'));

    $this->createNode([
      'title' => 'Project closed',
      'type' => 'oe_project',
      'oe_project_dates' => [
        'value' => '2020-05-10',
        'end_value' => '2020-05-15',
      ],
      'oe_subject' => 'http://data.europa.eu/uxp/1000',
    ]);

    $this->createNode([
      'title' => 'Project ongoing',
      'type' => 'oe_project',
      'oe_project_dates' => [
        'value' => '2022-05-20',
        'end_value' => $date_plus_1,
      ],
      'oe_subject' => 'http://data.europa.eu/uxp/1567',
    ]);

    $this->createNode([
      'title' => 'Project pending',
      'type' => 'oe_project',
      'oe_project_dates' => [
        'value' => $date_plus_1,
        'end_value' => $date_plus_10,
      ],
      'oe_subject' => 'http://data.europa.eu/uxp/1018',
    ]);

    // Index content.
    $this->indexItems('showcase_search_index');
    $this->drupalGet('/search');
    // Assert that filter facets exist in the sidebar.
    $filter_form = $assert_session->elementExists('css', '#bcl-offcanvas');
    // Project filters.
    $project_status = $assert_session->fieldExists('Project status', $filter_form);
    $project_start_date = $assert_session->elementExists('css', 'fieldset[data-drupal-facets-form-facet="oe_project_dates"]', $filter_form);
    $project_end_date = $assert_session->elementExists('css', 'fieldset[data-drupal-facets-form-facet="oe_sc_project__end_date"]', $filter_form);
    // Event filters.
    $event_type = $assert_session->fieldExists('Event type', $filter_form);
    $event_location = $assert_session->fieldExists('Event location', $filter_form);
    $event_dates = $assert_session->elementExists('css', 'fieldset[data-drupal-facets-form-facet="oe_sc_event__oe_sc_event_dates"]', $filter_form);
    // Publication filters.
    $publication_type = $assert_session->fieldExists('Publication type', $filter_form);
    // This facet is shared with News.
    $publication_date = $assert_session->elementExists('css', 'fieldset[data-drupal-facets-form-facet="oe_publication_date"]', $filter_form);
    // Submit button.
    $search_button = $filter_form->findButton('Refine');

    // Proyect status filter.
    $this->scrollIntoView('#' . $project_status->getAttribute('id'));
    $this->selectSlimOption($project_status, 'Closed');
    $this->scrollIntoView('#' . $search_button->getAttribute('id'));
    $search_button->click();
    $this->assertSearchResultsTitle(1);
    $this->assertSearchResults([
      'Project closed',
    ]);

    // Proyect start date filter.
    $this->drupalGet('/search');
    $selector = $project_start_date->getAttribute('data-drupal-selector');
    $project_start_date_from = $assert_session->elementExists('css', "#$selector-from", $project_start_date)->findField('Date');
    $project_start_date_to = $assert_session->elementExists('css', "#$selector-to", $project_start_date)->findField('Date');
    // First filter by From.
    $project_start_date_from->setValue('05/19/2022');
    $this->scrollIntoView('#' . $search_button->getAttribute('id'));
    $search_button->click();
    $this->assertSearchResultsTitle(2);
    $this->assertSearchResults([
      'Project ongoing',
      'Project pending',
    ]);
    // Then filter by To.
    $project_start_date_to->setValue('05/21/2022');
    $this->scrollIntoView('#' . $search_button->getAttribute('id'));
    $search_button->click();
    $this->assertSearchResultsTitle(1);
    $this->assertSearchResults([
      'Project ongoing',
    ]);

    // Proyect end date filter.
    $this->drupalGet('/search');
    $selector = $project_end_date->getAttribute('data-drupal-selector');
    $project_end_date_from = $assert_session->elementExists('css', "#$selector-from", $project_end_date)->findField('Date');
    $project_end_date_to = $assert_session->elementExists('css', "#$selector-to", $project_end_date)->findField('Date');
    // First filter by From.
    $project_end_date_from->setValue('05/14/2020');
    $this->scrollIntoView('#' . $search_button->getAttribute('id'));
    $search_button->click();
    $this->assertSearchResultsTitle(3);
    $this->assertSearchResults([
      'Project closed',
      'Project ongoing',
      'Project pending',
    ]);
    // Then filter by To.
    $project_end_date_to->setValue(date('m/d/Y', strtotime('+2 day')));
    $this->scrollIntoView('#' . $search_button->getAttribute('id'));
    $search_button->click();
    $this->assertSearchResultsTitle(2);
    $this->assertSearchResults([
      'Project closed',
      'Project ongoing',
    ]);

    // Event type filter.
    $this->drupalGet('/search');
    $this->scrollIntoView('#' . $event_type->getAttribute('id'));
    $this->selectSlimOption($event_type, 'Event type 4');
    $this->scrollIntoView('#' . $search_button->getAttribute('id'));
    $search_button->click();
    $this->assertSearchResultsTitle(1);
    $this->assertSearchResults([
      'Event number 4',
    ]);

    // Event location filter.
    $this->drupalGet('/search');
    $this->scrollIntoView('#' . $event_location->getAttribute('id'));
    $this->selectSlimOption($event_location, 'France');
    $this->scrollIntoView('#' . $search_button->getAttribute('id'));
    $search_button->click();
    $this->assertSearchResultsTitle(1);
    $this->assertSearchResults([
      'Event number 4',
    ]);

    // Event dates.
    $this->drupalGet('/search');
    $selector = $event_dates->getAttribute('data-drupal-selector');
    $event_dates_from = $assert_session->elementExists('css', "#$selector-from", $event_dates)->findField('Date');
    $event_dates_to = $assert_session->elementExists('css', "#$selector-to", $event_dates)->findField('Date');
    // First filter by From.
    $event_dates_from->setValue('04/02/2022');
    $this->scrollIntoView('#' . $search_button->getAttribute('id'));
    $search_button->click();
    $this->assertSearchResultsTitle(4);
    $this->assertSearchResults([
      'Event number 1',
      'Event number 2',
      'Event number 3',
      'Event number 4',
    ]);
    // Then filter by To.
    $event_dates_to->setValue('04/04/2022');
    $this->scrollIntoView('#' . $search_button->getAttribute('id'));
    $search_button->click();
    $this->assertSearchResultsTitle(3);
    $this->assertSearchResults([
      'Event number 1',
      'Event number 2',
      'Event number 3',
    ]);

    // Publication type filter.
    $this->drupalGet('/search');
    $this->scrollIntoView('#' . $publication_type->getAttribute('id'));
    $this->selectSlimOption($publication_type, 'Publication type 1');
    $this->scrollIntoView('#' . $search_button->getAttribute('id'));
    $search_button->click();
    $this->assertSearchResultsTitle(1);
    $this->assertSearchResults([
      'Publication 1',
    ]);
    $this->scrollIntoView('#' . $publication_type->getAttribute('id'));
    $this->selectSlimOption($publication_type, 'Publication type 2');
    $this->scrollIntoView('#' . $search_button->getAttribute('id'));
    $search_button->click();
    $this->assertSearchResultsTitle(1);
    $this->assertSearchResults([
      'Publication 2',
    ]);

    // Publication date filter, this applies to news and publications.
    $this->drupalGet('/search');
    $selector = $publication_date->getAttribute('data-drupal-selector');
    $publication_date_from = $assert_session->elementExists('css', "#$selector-from", $publication_date)->findField('Date');
    $publication_date_to = $assert_session->elementExists('css', "#$selector-to", $publication_date)->findField('Date');
    // First filter by From.
    $publication_date_from->setValue('04/04/2022');
    $this->scrollIntoView('#' . $search_button->getAttribute('id'));
    $search_button->click();
    $this->assertSearchResultsTitle(4);
    $this->assertSearchResults([
      'News number 3',
      'News number 4',
      'Publication 3',
      'Publication 4',
    ]);
    // Then filter by To.
    $publication_date_to->setValue('04/04/2022');
    $this->scrollIntoView('#' . $search_button->getAttribute('id'));
    $search_button->click();
    $this->assertSearchResultsTitle(2);
    $this->assertSearchResults([
      'News number 3',
      'Publication 3',
    ]);

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
      sprintf('Search Results (%s)', $expected_count),
      $title->getText());
  }

  /**
   * Gets the div element above the search results list.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   Element above the search results list.
   */
  protected function getSearchTopRegion(): NodeElement {
    $element = $this->assertSession()
      ->elementExists('css', '#block-oe-showcase-theme-main-page-content')
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

  /**
   * Scroll an element into the viewport.
   *
   * @param string $selector
   *   The css query selector.
   */
  protected function scrollIntoView(string $selector): void {
    $this->getSession()->executeScript("document.querySelector('$selector').scrollIntoView(true)");
    $this->getSession()->wait(1000);
  }

}
