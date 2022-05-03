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
        'language' => 'en',
        'status' => NodeInterface::PUBLISHED,
        'oe_publication_date' => sprintf('2022-04-%02d', $i + 1),
      ];
      $node = Node::create($values);
      $node->save();
    }

    // Create one Event test node.
    $values = [
      'title' => 'Event example',
      'type' => 'oe_sc_event',
      'body' => 'This is an Event content number 1',
      'language' => 'en',
      'status' => NodeInterface::PUBLISHED,
      'oe_sc_event_dates' => [
        'value' => '2022-04-04T02:00:00',
        'end_value' => '2022-04-04T05:00:00',
      ],
    ];
    $node = Node::create($values);
    $node->save();

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
    $title_input = $filter_form->findField('Title');
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
    $this->assertSearchResultsTitle('News', 8);
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
    $this->assertSearchResultsTitle('News', 1);
    $this->assertSearchResults([
      'News number 8',
    ]);

    // Assert only News nodes are part of the result.
    $title_input->setValue('Event example');
    $search_button->click();
    $this->assertSearchResultsTitle('News', 0);

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

    // Assert that the unique Event item is displayed.
    $this->assertSearchResults([
      'Event example',
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
    $date_input->setValue('2022-04-03');
    $search_button->click();
    $this->assertSearchResultsTitle('Event', 1);
    $this->assertSearchResults([
      'Event example',
    ]);

    // Filter Event results by title.
    $title_input->setValue('Event');
    $search_button->click();
    $this->assertSearchResultsTitle('Event', 1);
    $this->assertSearchResults([
      'Event example',
    ]);

    // Assert only Event nodes are part of the result.
    $title_input->setValue('News');
    $search_button->click();
    $this->assertSearchResultsTitle('Event', 0);
  }

  /**
   * Asserts the title above the search results.
   *
   * @param string $listing_page_content
   *   Expected List page content type title.
   * @param int $expected_count
   *   Expected number of results to be reported in the title.
   */
  protected function assertSearchResultsTitle(string $listing_page_content, int $expected_count): void {
    $title = $this->assertSession()->elementExists('css', '.col-xxl-8 h4.mb-0');
    $this->assertSame(
      sprintf('%s list page (%s)', $listing_page_content, $expected_count),
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
