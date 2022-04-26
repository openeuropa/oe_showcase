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

    // Create some test nodes.
    for ($i = 0; $i < 12; $i++) {
      $values = [
        'title' => 'News number ' . $i,
        'type' => 'oe_sc_news',
        'body' => 'This is content number ' . $i,
        'language' => 'en',
        'status' => NodeInterface::PUBLISHED,
        'oe_publication_date' => sprintf('2022-04-%02d', $i + 1),
      ];
      $node = Node::create($values);
      $node->save();
    }

    // Index content.
    $this->indexItems('oe_list_pages_index');

    $this->drupalGet('node/add/oe_list_page');
    $page->fillField('Title', 'News list page');
    $page->fillField('Source entity type', 'node');
    $page->fillField('Source bundle', 'oe_sc_news');
    $page->pressButton('Save');

    $node = $this->getNodeByTitle('News list page');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $page->checkField('Override default exposed filters');
    $page->checkField('emr_plugins_oe_list_page[wrapper][exposed_filters][oelp_oe_sc_news__title]');
    $page->checkField('emr_plugins_oe_list_page[wrapper][exposed_filters][oelp_oe_sc_news__publication_date]');
    $page->pressButton('Save');

    $this->drupalGet('node/' . $node->id());

    // Assert that the filter form exists.
    $filter_form = $assert_session->elementExists('css', '#oe-list-pages-facets-form');
    $title_input = $filter_form->findField('Title');
    $publication_date_input = $filter_form->findField('Publication date');
    $search_button = $filter_form->find('css', '#edit-submit');
    $this->assertNotNull($search_button);
    $this->assertNotNull($title_input);
    $this->assertNotNull($publication_date_input);

    // Filter results by date.
    $publication_date_input->setValue('gt');
    $date_input = $filter_form->findField('Date');
    $date_input->setValue('2022-04-04');
    $search_button->click();
    $this->assertSearchResultsTitle(8);
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

    // Filter results by title.
    $title_input->setValue('News number 8');
    $search_button->click();
    $this->assertSearchResultsTitle(1);
    $this->assertSearchResults([
      'News number 8',
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
      sprintf('News list page (%s)', $expected_count),
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
