<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

use Drupal\Core\Datetime\DrupalDateTime;
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
    $day = 00;
    for ($i = 0; $i < 12; $i++) {
      $date = new DrupalDateTime('20-10-2020');
      $values = [
        'title' => 'News number ' . $i,
        'type' => 'oe_sc_news',
        'body' => 'This is content number ' . $i,
        'language' => 'en',
        'status' => NodeInterface::PUBLISHED,
        'oe_publication_date' => [
          'value' => '2022-04-' . $day++,
        ],
        'created' => $date->getTimestamp(),
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

    // Assert the left column.
    $assert_session->elementExists('css', 'div.row > div.col-12.col-lg-3');
    // Assert offcanvas.
    $assert_session->elementExists('css', 'div.bcl-offcanvas');
    $assert_session->elementTextEquals('css', 'h4.offcanvas-title', 'Filter options');
    $assert_session->elementExists('css', 'button.btn-light > svg');
    $assert_session->elementTextEquals('css', 'button[data-bs-toggle="offcanvas"]', 'Filters');
    // Assert Filters.
    $assert_session->elementExists('css', 'input[name="oe_sc_news_title"]');
    $assert_session->elementExists('css', 'select[name="publication_date_op"]');
    $assert_session->elementExists('css', 'input[name="publication_date_first_date_wrapper[publication_date_first_date][date]"]');
    $assert_session->elementExists('css', 'input[data-drupal-selector="edit-submit"]');
    $assert_session->elementExists('css', 'input[data-drupal-selector="edit-reset"]');

    // Assert right column.
    $assert_session->elementExists('css', 'div.row > div.col-12.col-lg-9.col-xxl-8');
    $assert_session->elementContains('css', 'h4.mb-0 > span', 'News list page');
    $assert_session->elementContains('css', 'h4.mb-0', '(12)');
    // Assert listing.
    $assert_session->elementsCount('css', 'hr', 2);
    $assert_session->elementsCount('css', 'div.listing-item', '10');
    // Assert pagination.
    $assert_session->elementExists('css', 'nav > ul.pagination');
    $assert_session->elementsCount('css', 'ul.pagination > li.page-item', 3);

    // Assert search.
    $page->fillField('Publication date', 'gt');
    $page->fillField('Date', '2022-04-04');
    $page->pressButton('Search');
    $assert_session->elementContains('css', 'h4.mb-0', '(7)');
    $page->fillField('Title', 'News number 8');
    $page->pressButton('Search');
    $assert_session->elementContains('css', 'h4.mb-0', '(1)');
    $assert_session->elementTextEquals('css', 'span.badge.bg-light', 'News number 8');
  }

}
