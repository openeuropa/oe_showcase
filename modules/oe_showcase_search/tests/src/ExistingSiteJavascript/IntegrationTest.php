<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_search\ExistingSite;

use Behat\Mink\Element\NodeElement;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
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
    // Mark test content for deletion after the test has finished.
    $this->markEntityTypeForCleanup('node');
    $this->markEntityTypeForCleanup('taxonomy_term');
    $assert_session = $this->assertSession();

    // Create some News test nodes.
    for ($i = 0; $i < 5; $i++) {
      $values = [
        'title' => 'News number ' . $i,
        'type' => 'oe_sc_news',
        'body' => 'This is a News content number ' . $i,
        'oe_summary' => 'This is a News introduction number ' . $i,
        'language' => 'en',
        'status' => NodeInterface::PUBLISHED,
        'oe_publication_date' => sprintf('2022-04-%02d', $i + 1),
      ];
      $node = Node::create($values);
      $node->save();
    }

    // Create some Events test nodes.
    $countries = [
      'AF', 'BE', 'RO', 'DE', 'FR', 'ES', 'IT', 'AU', 'BB', 'RO', 'CZ', 'FR',
    ];
    for ($i = 0; $i < 5; $i++) {
      $term = Term::create([
        'vid' => 'event_type',
        'name' => 'Event type ' . $i,
      ]);
      $term->save();
      $values = [
        'title' => 'Event number ' . $i,
        'type' => 'oe_sc_event',
        'field_event_type' => $term->id(),
        'body' => 'This is an Event content number ' . $i,
        'oe_summary' => 'This is an Event introduction number ' . $i,
        'language' => 'en',
        'status' => NodeInterface::PUBLISHED,
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
      ];
      $node = Node::create($values);
      $node->save();
    }

    // Create some Person test nodes.
    for ($i = 0; $i < 5; $i++) {
      $values = [
        'oe_sc_person_first_name' => 'John',
        'oe_sc_person_last_name' => 'Doe ' . $i,
        'type' => 'oe_sc_person',
        'oe_summary' => 'This is a person short description number ' . $i,
        'oe_sc_person_additional_info' => 'This is a person additional info number ' . $i,
        'language' => 'en',
        'status' => NodeInterface::PUBLISHED,
        'oe_sc_person_country' => 'DE',
        'oe_sc_person_occupation' => 'DG TEST',
        'oe_sc_person_position' => 'Director',
        'created' => sprintf('2022-04-%02d', $i + 1),
      ];
      $node = Node::create($values);
      $node->save();
    }

    // Create some Publication test nodes.
    for ($i = 0; $i < 5; $i++) {
      $term = Term::create([
        'vid' => 'publication_type',
        'name' => 'Publication type ' . $i,
      ]);
      $term->save();
      $values = [
        'title' => 'Pub ' . $i,
        'type' => 'oe_sc_publication',
        'language' => 'en',
        'status' => NodeInterface::PUBLISHED,
        'field_publication_type' => $term->id(),
        'oe_summary' => 'This is a Publication summary ' . $i,
        'oe_publication_date' => sprintf('2022-04-%02d', $i + 1),
      ];
      $node = Node::create($values);
      $node->save();
    }

    // Create some Projects test nodes.
    $date_plus_1 = date('Y-m-d', strtotime('+1 day'));
    $date_plus_10 = date('Y-m-d', strtotime('+10 days'));
    $date_plus_10_calendar_format = date('m/d/Y', strtotime('+10 days'));

    Node::create([
      'title' => 'Project closed',
      'type' => 'oe_project',
      'oe_summary' => 'This is a closed Project',
      'language' => 'en',
      'status' => NodeInterface::PUBLISHED,
      'oe_project_budget' => 100,
      'oe_project_dates' => [
        'value' => '2020-05-10',
        'end_value' => '2020-05-15',
      ],
      'oe_subject' => 'http://data.europa.eu/uxp/1000',
    ])->save();

    Node::create([
      'title' => 'Project ongoing',
      'type' => 'oe_project',
      'oe_summary' => 'This is a ongoing Project',
      'language' => 'en',
      'status' => NodeInterface::PUBLISHED,
      'oe_project_budget' => 33,
      'oe_project_dates' => [
        'value' => '2022-05-20',
        'end_value' => $date_plus_1,
      ],
      'oe_subject' => 'http://data.europa.eu/uxp/1567',
    ])->save();

    Node::create([
      'title' => 'Project pending',
      'type' => 'oe_project',
      'oe_summary' => 'This is a pending Project',
      'language' => 'en',
      'status' => NodeInterface::PUBLISHED,
      'oe_project_budget' => 1234,
      'oe_project_dates' => [
        'value' => $date_plus_1,
        'end_value' => $date_plus_10,
      ],
      'oe_subject' => 'http://data.europa.eu/uxp/1018',
    ])->save();

    // Create some Page test nodes.
    for ($i = 0; $i < 5; $i++) {
      $values = [
        'title' => 'Page number ' . $i,
        'type' => 'oe_showcase_page',
        'field_description' => 'This is a Page introduction number ' . $i,
        'language' => 'en',
        'status' => NodeInterface::PUBLISHED,
      ];
      $node = Node::create($values);
      $node->save();
    }

    // Index content.
    $this->indexItems('showcase_search_index');
    $this->drupalGet('/search');

    // Assert that filter facets exist in the sidebar.
    $filter_form = $assert_session->elementExists('css', '#bcl-offcanvas');
    // Project filters.
    $project_status = $filter_form->findField('Project status');
    $project_start_date = $assert_session->elementExists('css', 'fieldset[data-drupal-facets-form-facet="oe_project_dates"]', $filter_form);
    $project_end_date = $assert_session->elementExists('css', 'fieldset[data-drupal-facets-form-facet="oe_sc_project__end_date"]', $filter_form);
    // Event filters.
    $event_type = $filter_form->findField('Event type');
    $event_location = $filter_form->findField('Event location');
    $event_dates = $assert_session->elementExists('css', 'fieldset[data-drupal-facets-form-facet="oe_sc_event__oe_sc_event_dates"]', $filter_form);
    // Publication filters.
    $publication_type = $filter_form->findField('Publication type');
    // This facet is shared with News.
    $publication_date = $assert_session->elementExists('css', 'fieldset[data-drupal-facets-form-facet="oe_publication_date"]', $filter_form);
    // Submit buttons.
    $search_button = $filter_form->findButton('Refine');
    $clear_button = $filter_form->findLink('Clear');
    // Assert all form filters.
    $this->assertNotNull($project_status);
    $this->assertNotNull($project_start_date);
    $this->assertNotNull($project_end_date);
    $this->assertNotNull($event_type);
    $this->assertNotNull($event_location);
    $this->assertNotNull($event_dates);
    $this->assertNotNull($publication_type);
    $this->assertNotNull($publication_date);
    $this->assertNotNull($search_button);
    $this->assertNotNull($clear_button);

    // Proyect status filter.
    $this->scrollIntoView('#' . $project_status->getAttribute('id'));
    $this->selectSlimOption($project_status, 'Closed');
    $this->scrollIntoView('#' . $search_button->getAttribute('id'));
    $search_button->click();
    $this->assertSearchResultsTitle(1);
    $this->assertSearchResults([
      'Project closed',
    ]);
    $this->scrollIntoView('#' . $clear_button->getAttribute('id'));
    $clear_button->click();

    // Proyect start date filter.
    $this->scrollIntoView('#' . $project_start_date->getAttribute('id'));
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
    $this->scrollIntoView('#' . $clear_button->getAttribute('id'));
    $clear_button->click();

    // Proyect end date filter.
    $this->scrollIntoView('#' . $project_end_date->getAttribute('id'));
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
    $this->scrollIntoView('#' . $clear_button->getAttribute('id'));
    $clear_button->click();

    // Event type filter.
    $this->scrollIntoView('#' . $event_type->getAttribute('id'));
    $this->selectSlimOption($event_type, 'Event type 3');
    $this->scrollIntoView('#' . $search_button->getAttribute('id'));
    $search_button->click();
    $this->assertSearchResultsTitle(1);
    $this->assertSearchResults([
      'Event number 3',
    ]);
    $this->scrollIntoView('#' . $event_type->getAttribute('id'));
    $this->selectSlimOption($event_type, 'Event type 4');
    $this->scrollIntoView('#' . $search_button->getAttribute('id'));
    $search_button->click();
    $this->assertSearchResultsTitle(1);
    $this->assertSearchResults([
      'Event number 4',
    ]);
    $this->scrollIntoView('#' . $clear_button->getAttribute('id'));
    $clear_button->click();

    // Event location filter.
    $this->scrollIntoView('#' . $event_location->getAttribute('id'));
    $this->selectSlimOption($event_location, 'France');
    $this->scrollIntoView('#' . $search_button->getAttribute('id'));
    $search_button->click();
    $this->assertSearchResultsTitle(1);
    $this->assertSearchResults([
      'Event number 4',
    ]);
    $this->scrollIntoView('#' . $event_location->getAttribute('id'));
    $this->selectSlimOption($event_location, 'Romania');
    $this->scrollIntoView('#' . $search_button->getAttribute('id'));
    $search_button->click();
    $this->assertSearchResultsTitle(1);
    $this->assertSearchResults([
      'Event number 2',
    ]);
    $this->scrollIntoView('#' . $clear_button->getAttribute('id'));
    $clear_button->click();

    // Event dates.
    $this->scrollIntoView('#' . $event_dates->getAttribute('id'));
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
    $this->scrollIntoView('#' . $clear_button->getAttribute('id'));
    $clear_button->click();

    // Publication type filter.
    $this->scrollIntoView('#' . $publication_type->getAttribute('id'));
    $this->selectSlimOption($publication_type, 'Publication type 1');
    $this->scrollIntoView('#' . $search_button->getAttribute('id'));
    $search_button->click();

    $this->assertSearchResultsTitle(1);
    $this->assertSearchResults([
      'Pub 1',
    ]);
    $this->scrollIntoView('#' . $publication_type->getAttribute('id'));
    $this->selectSlimOption($publication_type, 'Publication type 2');
    $this->scrollIntoView('#' . $publication_type->getAttribute('id'));
    $search_button->click();
    $this->assertSearchResultsTitle(1);
    $this->assertSearchResults([
      'Pub 2',
    ]);
    $this->scrollIntoView('#' . $clear_button->getAttribute('id'));
    $clear_button->click();

    // Publication date filter, this applies to news and publications.
    $this->scrollIntoView('#' . $publication_date->getAttribute('id'));
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
      'Pub 3',
      'Pub 4',
    ]);
    // Then filter by To.
    $publication_date_to->setValue('04/04/2022');
    $this->scrollIntoView('#' . $search_button->getAttribute('id'));
    $search_button->click();
    $this->assertSearchResultsTitle(2);
    $this->assertSearchResults([
      'News number 3',
      'Pub 3',
    ]);
    $this->scrollIntoView('#' . $clear_button->getAttribute('id'));
    $clear_button->click();

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
