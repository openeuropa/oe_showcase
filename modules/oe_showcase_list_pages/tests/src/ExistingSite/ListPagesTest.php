<?php

declare(strict_types = 1);

use Drupal\Tests\oe_showcase\ExistingSite\ShowcaseExistingSiteTestBase;

use Behat\Mink\Element\NodeElement;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Tests\search_api\Functional\ExampleContentTrait;
use Drupal\user\Entity\Role;

/**
 * Class to test List pages content type on existing site tests.
 */
class ListPagesTest extends ShowcaseExistingSiteTestBase {

  use ExampleContentTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create admin user.
    $role = Role::load($this->createRole([]));
    $role->setIsAdmin(TRUE);
    $role->save();
    $user = $this->createUser([]);
    $user->addRole($role->id());
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
    $this->indexItems('oe_whitelabel_list_page_index_test');

    $this->drupalGet('node/add/oe_list_page');
    $page->fillField('Title', 'News list page');
    $page->fillField('Source entity type', 'node');
    $page->fillField('Source bundle', 'oe_sc_news');
    $page->pressButton('Save');

    $node = $this->getNodeByTitle('News list page');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $page->checkField('Override default exposed filters');
    $page->checkField('emr_plugins_oe_list_page[wrapper][exposed_filters][oe_sc_news_title]');
    $page->checkField('emr_plugins_oe_list_page[wrapper][exposed_filters][publication_date]');
    $page->pressButton('Save');

    $this->drupalGet('node/' . $node->id());

    // Assert that the filter form exists.
    $search_form = $assert_session->elementExists('css', '#oe-list-pages-facets-form');
    $title_input = $search_form->findField('Title');
    $publication_date_input = $search_form->findField('Publication date');
    $search_button = $search_form->find('css', '#edit-submit');
    $this->assertNotNull($search_button);
    $this->assertNotNull($title_input);
    $this->assertNotNull($publication_date_input);

    // Use the site-wide search for filter news created after 4th april 2022.
    $publication_date_input->setValue('gt');
    $date_input = $search_form->findField('Date');
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
    // Use the site-wide search for filter news with specific title.
    $title_input->setValue('News number 8');
    $search_button->click();
    $this->assertSearchResultsTitle(1);
    $this->assertSearchResults([
      'News number 8',
    ]);
  }

  /**
   * Gets the div element above the search results list.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   Element above the search results list.
   */
  protected function getSearchTopRegion(): NodeElement {
    $element = $this->assertSession()
      ->elementExists('css', 'div.col-xxl-8');
    return $element;
  }

  /**
   * Asserts the title above the search results.
   *
   * @param int $expected_count
   *   Expected number of results to be reported in the title.
   */
  protected function assertSearchResultsTitle(int $expected_count): void {
    $title = $this->getSearchTopRegion()->find('css', 'h4.mb-0');
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
