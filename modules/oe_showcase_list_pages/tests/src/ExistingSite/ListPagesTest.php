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
   * Tests list pages integration.
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
    $countries = [
      'AF', 'BE', 'RO', 'DE', 'FR', 'ES', 'IT', 'AU', 'BB', 'RO', 'CZ', 'FR',
    ];
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
        'oe_sc_event_location' => [
          'country_code' => $countries[$i],
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
    $this->assertResultsTitle('News list page', 12);
    $this->assertResults([
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
    $this->assertResults([
      'News number 10',
      'News number 11',
    ]);

    // Assert that the filter form for News exists.
    $filter_form = $assert_session->elementExists('css', '#oe-list-pages-facets-form');
    $title_input = $filter_form->findField('Keywords');
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
    $this->assertResultsTitle('News list page', 8);
    $this->assertResults([
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
    $this->assertResultsTitle('News list page', 1);
    $this->assertResults([
      'News number 8',
    ]);

    // Filter the News results by content.
    $title_input->setValue('This is a News content number 10');
    $search_button->click();
    $this->assertResultsTitle('News list page', 1);
    $this->assertResults([
      'News number 10',
    ]);

    // Filter the News results by introduction.
    $title_input->setValue('This is a News introduction number 11');
    $search_button->click();
    $this->assertResultsTitle('News list page', 1);
    $this->assertResults([
      'News number 11',
    ]);

    // Filter by a mix of introduction and content in shuffled order.
    $title_input->setValue('6 content number introduction');
    $search_button->click();
    $this->assertResultsTitle('News list page', 1);
    $this->assertResults([
      'News number 6',
    ]);
    // @todo Remove "7" once we have a default order.
    $title_input->setValue('NUMBER 7 this');
    $search_button->click();
    $this->assertResultsTitle('News list page', 1);
    $this->assertResults([
      'News number 7',
    ]);

    // Assert only News nodes are part of the result.
    $title_input->setValue('Event example');
    $search_button->click();
    $this->assertResultsTitle('News list page', 0);

    // Create an Event listing page.
    $this->drupalGet('node/add/oe_list_page');
    $page->fillField('Title', 'Event list page');
    $page->fillField('Source entity type', 'node');
    $page->fillField('Source bundle', 'oe_sc_event');
    $page->pressButton('Save');

    $node = $this->getNodeByTitle('Event list page');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $page->checkField('Override default exposed filters');
    $page->checkField('emr_plugins_oe_list_page[wrapper][exposed_filters][oelp_oe_sc_event__location]');
    $page->checkField('emr_plugins_oe_list_page[wrapper][exposed_filters][oelp_oe_sc_event__title]');
    $page->checkField('emr_plugins_oe_list_page[wrapper][exposed_filters][oelp_oe_sc_event__oe_sc_event_dates]');
    $page->pressButton('Save');

    $this->drupalGet('node/' . $node->id());

    // Assert that only Event items are displayed.
    $this->assertResults([
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
    $this->assertResults([
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
    $this->assertResultsTitle('Event list page', 8);
    $this->assertResults([
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
    $this->assertResultsTitle('Event list page', 1);
    $this->assertResults([
      'Event number 8',
    ]);

    // Assert only Event nodes are part of the result.
    $title_input->setValue('News number 1');
    $search_button->click();
    $this->assertResultsTitle('Event list page', 0);

    // Assert Event title filters only by title.
    $title_input->setValue('This is an Event content number 10');
    $search_button->click();
    $this->assertResultsTitle('Event list page', 0);
    $title_input->setValue('This is an Event introduction number 10');
    $search_button->click();
    $this->assertResultsTitle('Event list page', 0);

    // Filter results by location.
    $filter_form->findButton('Clear filters')->click();
    $location = $filter_form->findField('Location');
    $location->selectOption('France');
    $search_button->click();
    $this->assertResultsTitle('Event list page', 2);
    $this->assertResults([
      'Event number 4',
      'Event number 11',
    ]);
    $location->selectOption('Romania', TRUE);
    $search_button->click();
    $this->assertResultsTitle('Event list page', 4);
    $this->assertResults([
      'Event number 2',
      'Event number 4',
      'Event number 9',
      'Event number 11',
    ]);

    // Test Project list page.
    $date_plus_1 = date('Y-m-d', strtotime('+1 day'));
    $date_plus_10 = date('Y-m-d', strtotime('+10 days'));

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

    // Index content.
    $this->indexItems('oe_list_pages_index');

    $list_page = $this->createListPage('oe_project', [
      'oelp_oe_sc_project__type' => 'oelp_oe_sc_project__type',
      'oelp_oe_sc_project__budget' => 'oelp_oe_sc_project__budget',
      'oelp_oe_sc_project__start_date' => 'oelp_oe_sc_project__start_date',
      'oelp_oe_sc_project__end_date' => 'oelp_oe_sc_project__end_date',
    ]);

    $this->drupalGet($list_page->toUrl());

    $this->assertResultsTitle('Results', 3);
    $this->assertResults([
      'Project closed',
      'Project ongoing',
      'Project pending',
    ]);

    // Assert that the filter form for Events exists.
    $filter_form = $assert_session->elementExists('css', '#oe-list-pages-facets-form');
    $filter_type = $filter_form->findField('Type');
    $filter_budget = $filter_form->findField('Total budget');
    $filter_start = $filter_form->findField('Start date');
    $filter_end = $filter_form->findField('End date');
    $search_button = $filter_form->find('css', '#edit-submit');
    $this->assertNotNull($filter_type);
    $this->assertNotNull($filter_budget);
    $this->assertNotNull($filter_start);
    $this->assertNotNull($filter_end);
    $this->assertNotNull($search_button);

    // Filter results by type.
    $filter_type->selectOption('financing');
    $search_button->click();
    $this->assertResultsTitle('Results', 1);
    $this->assertResults([
      'Project closed',
    ]);
    $filter_type->selectOption('public finance', TRUE);
    $search_button->click();
    $this->assertResultsTitle('Results', 2);
    $this->assertResults([
      'Project closed',
      'Project pending',
    ]);

    // Filter results by budget.
    $filter_form->findButton('Clear filters')->click();
    $filter_budget->setValue(33);
    $search_button->click();
    $this->assertResultsTitle('Results', 1);
    $this->assertResults([
      'Project ongoing',
    ]);

    // Filter results by dates.
    $filter_form->findButton('Clear filters')->click();
    $filter_start->setValue('gt');
    $date_input = $filter_form->findField('Date');
    $date_input->setValue('2022-05-19');
    $search_button->click();
    $this->assertResultsTitle('Results', 2);
    $this->assertResults([
      'Project ongoing',
      'Project pending',
    ]);

    $filter_start->setValue('lt');
    $search_button->click();
    $this->assertResultsTitle('Results', 1);
    $this->assertResults([
      'Project closed',
    ]);

    $dates = $filter_form->findAll('named', ['field', 'Date']);
    $filter_start->setValue('gt');
    $dates[0]->setValue('2022-05-19');
    $filter_end->setValue('lt');
    $dates[1]->setValue($date_plus_10);
    $search_button->click();
    $this->assertResultsTitle('Results', 1);
    $this->assertResults([
      'Project ongoing',
    ]);
  }

  /**
   * Asserts the title above the search results.
   *
   * @param string $list_page_title
   *   Title of the list page node.
   * @param int $expected_count
   *   Expected number of results to be reported in the title.
   */
  protected function assertResultsTitle(string $list_page_title, int $expected_count): void {
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
  protected function assertResults(array $expected): void {
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

  /**
   * Create a list page node with filters configured.
   *
   * @param string $bundle
   *   Nodes of this bundle will be listed.
   * @param array $exposed_filters
   *   Facet machine names linked to the bundle's facet source.
   *
   * @return \Drupal\node\NodeInterface
   *   The list page node created.
   */
  protected function createListPage(string $bundle, array $exposed_filters): NodeInterface {
    $list_page = Node::create([
      'type' => 'oe_list_page',
      'title' => 'Results',
    ]);

    /** @var \Drupal\emr\Entity\EntityMetaInterface $list_page_entity_meta */
    $list_page_entity_meta = $list_page->get('emr_entity_metas')->getEntityMeta('oe_list_page');
    /** @var \Drupal\oe_list_pages\ListPageWrapper $list_page_entity_meta_wrapper */
    $list_page_entity_meta_wrapper = $list_page_entity_meta->getWrapper();
    $list_page_entity_meta_wrapper->setSource('node', $bundle);
    $list_page_entity_meta_wrapper->setConfiguration([
      'override_exposed_filters' => 1,
      'exposed_filters' => $exposed_filters,
      'preset_filters' => [],
      'limit' => 10,
      'sort' => [],
    ]);
    $list_page->get('emr_entity_metas')->attach($list_page_entity_meta);
    $list_page->save();

    return $list_page;
  }

}
