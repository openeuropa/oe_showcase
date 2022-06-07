<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_list_pages\ExistingSite;

use Behat\Mink\Element\NodeElement;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Tests\oe_showcase\ExistingSite\ShowcaseExistingSiteTestBase;
use Drupal\Tests\search_api\Functional\ExampleContentTrait;

/**
 * Tests list pages.
 */
class ListPagesTest extends ShowcaseExistingSiteTestBase {

  use ExampleContentTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create editor user.
    $user = $this->createUser([]);
    $user->addRole('editor');
    $user->save();
    $this->drupalLogin($user);
  }

  /**
   * Check creation List pages content through the UI.
   */
  public function testCreateListPages() {
    // Mark test content for deletion after the test has finished.
    $this->markEntityTypeForCleanup('node');

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Create some News test nodes.
    for ($i = 0; $i < 12; $i++) {
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
    for ($i = 0; $i < 12; $i++) {
      $values = [
        'title' => 'Event number ' . $i,
        'type' => 'oe_sc_event',
        'body' => 'This is an Event content number ' . $i,
        'oe_summary' => 'This is an Event introduction number ' . $i,
        'language' => 'en',
        'status' => NodeInterface::PUBLISHED,
        'oe_sc_event_dates' => [
          'value' => sprintf('2022-04-%02dT02:00:00', $i + 1),
          'end_value' => sprintf('2022-04-%02dT05:00:00', $i + 2),
        ],
      ];
      $node = Node::create($values);
      $node->save();
    }

    // Index content.
    $this->indexItems('oe_list_pages_index');

    // Create the News listing page.
    $this->drupalGet('node/add/oe_list_page');
    $page->fillField('Title', 'News list page');
    $page->fillField('Source entity type', 'node');
    $page->fillField('Source bundle', 'oe_sc_news');
    $page->pressButton('Save');

    $node = $this->getNodeByTitle('News list page');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $page->checkField('Override default exposed filters');
    $page->checkField('emr_plugins_oe_list_page[wrapper][exposed_filters][oelp_oe_sc_news__title]');
    $page->checkField('emr_plugins_oe_list_page[wrapper][exposed_filters][oelp_oe_sc_news__oe_publication_date]');
    $page->pressButton('Save');

    $this->drupalGet('node/' . $node->id());

    // Assert that only News items are displayed.
    $this->assertSearchResultsTitle('News list page', 12);
    $this->assertSearchResults([
      'News number 0',
      'News number 1',
      'News number 2',
      'News number 3',
      'News number 4',
      'News number 5',
      'News number 6',
      'News number 7',
      'News number 8',
      'News number 9',
    ]);
    $pager = $page->find('css', 'ul.pagination > li:nth-child(2) > a');
    $pager->click();
    $this->assertSearchResults([
      'News number 10',
      'News number 11',
    ]);

    // Assert that the filter form for News exists.
    $filter_form = $assert_session->elementExists('css', '#oe-list-pages-facets-form');
    $title_input = $filter_form->findField('Keyword');
    $publication_date_input = $filter_form->findField('Publication date');
    $search_button = $filter_form->find('css', '#edit-submit');
    $this->assertNotNull($search_button);
    $this->assertNotNull($title_input);
    $this->assertNotNull($publication_date_input);

    // Filter the News results by date.
    $publication_date_input->setValue('gt');
    $date_input = $filter_form->findField('Date');
    $date_input->setValue('2022-04-04');
    $search_button->click();
    $this->assertSearchResultsTitle('News list page', 8);
    $this->assertSearchResults([
      'News number 4',
      'News number 5',
      'News number 6',
      'News number 7',
      'News number 8',
      'News number 9',
      'News number 10',
      'News number 11',
    ]);

    // Filter the News results by title.
    $title_input->setValue('News number 8');
    $search_button->click();
    $this->assertSearchResultsTitle('News list page', 1);
    $this->assertSearchResults([
      'News number 8',
    ]);

    // Filter the News results by content.
    $title_input->setValue('This is a News content number 10');
    $search_button->click();
    $this->assertSearchResultsTitle('News list page', 1);
    $this->assertSearchResults([
      'News number 10',
    ]);

    // Filter the News results by introduction.
    $title_input->setValue('This is a News introduction number 11');
    $search_button->click();
    $this->assertSearchResultsTitle('News list page', 1);
    $this->assertSearchResults([
      'News number 11',
    ]);

    // Assert only News nodes are part of the result.
    $title_input->setValue('Event example');
    $search_button->click();
    $this->assertSearchResultsTitle('News list page', 0);

    // Create an Event listing page.
    $this->drupalGet('node/add/oe_list_page');
    $page->fillField('Title', 'Event list page');
    $page->fillField('Source entity type', 'node');
    $page->fillField('Source bundle', 'oe_sc_event');
    $page->pressButton('Save');

    $node = $this->getNodeByTitle('Event list page');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $page->checkField('Override default exposed filters');
    $page->checkField('emr_plugins_oe_list_page[wrapper][exposed_filters][oelp_oe_sc_event__title]');
    $page->checkField('emr_plugins_oe_list_page[wrapper][exposed_filters][oelp_oe_sc_event__oe_sc_event_dates]');
    $page->pressButton('Save');

    $this->drupalGet('node/' . $node->id());

    // Assert that only Event items are displayed.
    $this->assertSearchResults([
      'Event number 0',
      'Event number 1',
      'Event number 2',
      'Event number 3',
      'Event number 4',
      'Event number 5',
      'Event number 6',
      'Event number 7',
      'Event number 8',
      'Event number 9',
    ]);
    $pager = $page->find('css', 'ul.pagination > li:nth-child(2) > a');
    $pager->click();
    $this->assertSearchResults([
      'Event number 10',
      'Event number 11',
    ]);

    // Assert that the filter form for Events exists.
    $filter_form = $assert_session->elementExists('css', '#oe-list-pages-facets-form');
    $title_input = $filter_form->findField('Title');
    $event_date_input = $filter_form->findField('Event dates');
    $search_button = $filter_form->find('css', '#edit-submit');
    $this->assertNotNull($search_button);
    $this->assertNotNull($title_input);
    $this->assertNotNull($event_date_input);

    // Filter results by date.
    $event_date_input->setValue('gt');
    $date_input = $filter_form->findField('Date');
    $date_input->setValue('2022-04-04');
    $search_button->click();
    $this->assertSearchResultsTitle('Event list page', 8);
    $this->assertSearchResults([
      'Event number 4',
      'Event number 5',
      'Event number 6',
      'Event number 7',
      'Event number 8',
      'Event number 9',
      'Event number 10',
      'Event number 11',
    ]);

    // Filter Event results by title.
    $title_input->setValue('Event number 8',);
    $search_button->click();
    $this->assertSearchResultsTitle('Event list page', 1);
    $this->assertSearchResults([
      'Event number 8',
    ]);

    // Assert only Event nodes are part of the result.
    $title_input->setValue('News number 1');
    $search_button->click();
    $this->assertSearchResultsTitle('Event list page', 0);

    // Assert Event title filters only by title.
    $title_input->setValue('This is an Event content number 10');
    $search_button->click();
    $this->assertSearchResultsTitle('Event list page', 0);
    $title_input->setValue('This is an Event introduction number 10');
    $search_button->click();
    $this->assertSearchResultsTitle('Event list page', 0);
  }

  /**
   * Asserts the title above the search results.
   *
   * @param string $list_page_title
   *   Title of the list page node.
   * @param int $expected_count
   *   Expected number of results to be reported in the title.
   */
  protected function assertSearchResultsTitle(string $list_page_title, int $expected_count): void {
    $title = $this->assertSession()->elementExists('css', '.col-xxl-8 h4.mb-0');
    $this->assertSame(
      sprintf('%s (%s)', $list_page_title, $expected_count),
      $title->getText());
  }

  /**
   * Asserts search result items.
   *
   * @param array $expected
   *   Expected titles of search result items.
   */
  protected function assertSearchResults(array $expected): void {
    $items = $this->assertSession()
      ->elementExists('css', '.bcl-listing')
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

}
