<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_list_pages\ExistingSite;

use Behat\Mink\Element\NodeElement;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Tests\oe_showcase\ExistingSite\ShowcaseExistingSiteTestBase;
use Drupal\Tests\search_api\Functional\ExampleContentTrait;

/**
 * Tests Event list pages.
 */
class ListPagesEventTest extends ShowcaseExistingSiteTestBase {

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
   * Create Event List pages content through the UI.
   */
  public function testCreateListPages() {
    // Mark test content for deletion after the test has finished.
    $this->markEntityTypeForCleanup('node');

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Create some test event nodes.
    for ($i = 0; $i < 12; $i++) {
      $values = [
        'title' => 'Event number ' . $i,
        'type' => 'oe_sc_event',
        'body' => 'This is content number ' . $i,
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

    // Assert that the filter form exists.
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
    $this->assertSearchResultsTitle(8);
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

    // Filter results by title.
    $title_input->setValue('Event number 8');
    $search_button->click();
    $this->assertSearchResultsTitle(1);
    $this->assertSearchResults([
      'Event number 8',
    ]);
  }

  /**
   * Asserts the title above the search results.
   *
   * @param int $expected_count
   *   Expected number of results to be reported in the title.
   */
  protected function assertSearchResultsTitle(int $expected_count): void {
    $title = $this->assertSession()->elementExists('css', '.col-xxl-8 h4.mb-0');
    $this->assertSame(
      sprintf('Event list page (%s)', $expected_count),
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
