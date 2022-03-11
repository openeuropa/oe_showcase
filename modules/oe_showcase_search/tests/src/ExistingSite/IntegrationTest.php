<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_search\ExistingSite;

use Drupal\Core\Url;
use Drupal\Tests\oe_showcase\ExistingSite\ShowcaseExistingSiteTestBase;

/**
 * Tests the OEL Search feature integration.
 */
class IntegrationTest extends ShowcaseExistingSiteTestBase {

  /**
   * Test the search page.
   */
  public function testCreateShowCasePage() {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Assert the correct elements are in place.
    $this->drupalGet(Url::fromRoute('<front>'));
    $search_form = $assert->elementExists('css', '#oe-whitelabel-search-form');
    $search_input = $search_form->findField('Search');
    $this->assertNotNull($search_input);
    $this->assertTrue($search_input->hasClass('form-autocomplete'));
    $this->assertTrue($search_input->hasClass('required'));
    $search_input->setValue('Imputo');
    $search_button = $search_form->find('css', '#submit');
    $this->assertNotNull($search_button);
    $search_button->click();

    $view = $assert->elementExists('css', '.views-element-container');
    $items = $view->findAll('css', '.card-title');
    $this->assertCount(2, $items);
    $this->assertSame('Imputo Neo Sagaciter', $items[0]->getText());
    $this->assertSame('Gemino Imputo', $items[1]->getText());

    // @todo Use 'Clear' once OEL-1315 is resolved.
    $this->drupalGet('/search');
    $offcanvas = $assert->elementExists('css', '#bcl-offcanvas');
    $title = $offcanvas->find('css', 'h4');
    $this->assertSame('Filter Options', $title->getText());
    $assert->fieldExists('Category 1');
    $assert->fieldExists('Category 2');
    $assert->fieldExists('Category 3');
    $assert->fieldExists('Category 4');
    $assert->fieldExists('Category 5');

    $title = $assert->elementExists('css', 'h4.mb-4');
    $this->assertSame('Search results (19)', $title->getText());
    $assert->fieldExists('Sort by');

    $items = $view->findAll('css', '.card-title');
    $this->assertCount(5, $items);
    $this->assertSame('Imputo Neo Sagaciter', $items[0]->getText());
    $this->assertSame('Distineo', $items[1]->getText());
    $this->assertSame('Quae Vulputate', $items[2]->getText());
    $this->assertSame('Abico Diam Jugis', $items[3]->getText());
    $this->assertSame('Obruo', $items[4]->getText());

    // @todo Assert "Last" once OEL-1316 is fixed.
    $pagination = $assert->elementExists('css', 'ul.pagination');
    $links = $pagination->findAll('css', '.page-item');
    $this->assertCount(5, $links);
    $this->assertSame('1', $links[0]->getText());
    $this->assertTrue($links[0]->hasClass('active'));
    $this->assertSame('2', $links[1]->getText());
    $this->assertSame('3', $links[2]->getText());
    $this->assertSame('4', $links[3]->getText());
    $this->assertSame('Next ›', $links[4]->getText());

    // Assert using filters alters the result.
    $page->checkField('Category 2');
    $page->pressButton('Refine');

    $title = $assert->elementExists('css', 'h4.mb-4');
    $this->assertSame('Search results (8)', $title->getText());
    $badge = $assert->elementExists('css', '.badge');
    $this->assertSame('Category 2', $badge->getText());
    $items = $view->findAll('css', '.card-title');
    $this->assertSame('Imputo Neo Sagaciter', $items[0]->getText());
    $this->assertSame('Abico Diam Jugis', $items[1]->getText());
    $this->assertSame('Voco', $items[2]->getText());
    $this->assertSame('Comis Incassum', $items[3]->getText());
    $this->assertSame('Appellatio Immitto', $items[4]->getText());
    $links = $pagination->findAll('css', '.page-item');
    $this->assertCount(3, $links);

    $page->fillField('Date', '2021-08-01');
    $page->pressButton('Refine');

    $title = $assert->elementExists('css', 'h4.mb-4');
    $this->assertSame('Search results (1)', $title->getText());
    // @todo Assert the second badge once OEL-662 is resolved.
    $items = $view->findAll('css', '.card-title');
    $this->assertSame('Imputo Neo Sagaciter', $items[0]->getText());
    $links = $pagination->findAll('css', '.page-item');
    $this->assertCount(0, $links);

    $page->clickLink('Clear');
    $title = $assert->elementExists('css', 'h4.mb-4');
    $this->assertSame('Search results (19)', $title->getText());
    $items = $view->findAll('css', '.card-title');
    $this->assertCount(5, $items);
    $links = $pagination->findAll('css', '.page-item');
    $this->assertCount(5, $links);

    // Assert sorting changes order of items.
    $page->selectFieldOption('Sort by', 'Published on Asc');
    $page->pressButton('Apply');

    $items = $view->findAll('css', '.card-title');
    $this->assertSame('Hendrerit', $items[0]->getText());
    $this->assertSame('Mos', $items[1]->getText());
    $this->assertSame('Camur Tego Vulputate', $items[2]->getText());
    $this->assertSame('Gemino Imputo', $items[3]->getText());
    $this->assertSame('Macto Neque Virtus', $items[4]->getText());

    // Assert items after using pager.
    $page->clickLink('2');
    $view = $assert->elementExists('css', '.views-element-container');
    $items = $view->findAll('css', '.card-title');
    $this->assertCount(5, $items);
    $this->assertSame('Luctus Sit', $items[0]->getText());
    $this->assertSame('Appellatio Immitto', $items[1]->getText());
    $this->assertSame('Comis Incassum', $items[2]->getText());
    $this->assertSame('Voco', $items[3]->getText());
    $this->assertSame('Appellatio Camur', $items[4]->getText());

    $pagination = $assert->elementExists('css', 'ul.pagination');
    $links = $pagination->findAll('css', '.page-item');
    $this->assertCount(6, $links);
    $this->assertSame('‹ Previous', $links[0]->getText());
    $this->assertSame('1', $links[1]->getText());
    $this->assertSame('2', $links[2]->getText());
    $this->assertTrue($links[2]->hasClass('active'));
    $this->assertSame('3', $links[3]->getText());
    $this->assertSame('4', $links[4]->getText());
    $this->assertSame('Next ›', $links[5]->getText());
  }

}
