<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase_list_pages\ExistingSiteJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\ContentBannerAssert;
use Drupal\Tests\oe_showcase\ExistingSiteJavascript\ShowcaseExistingSiteJavascriptTestBase;
use Drupal\Tests\oe_showcase\Traits\EntityBrowserTrait;
use Drupal\Tests\oe_showcase\Traits\MediaCreationTrait;
use Drupal\Tests\oe_showcase\Traits\ScrollTrait;
use Drupal\Tests\oe_showcase\Traits\SlimSelectTrait;
use Drupal\Tests\oe_showcase\Traits\WysiwygTrait;
use Drupal\Tests\pathauto\Functional\PathautoTestHelperTrait;
use Drupal\Tests\search_api\Functional\ExampleContentTrait;
use Drupal\user\Entity\Role;

/**
 * Tests list pages.
 */
class ListPagesTest extends ShowcaseExistingSiteJavascriptTestBase {

  use ExampleContentTrait;
  use SlimSelectTrait;
  use PathautoTestHelperTrait;
  use ScrollTrait;
  use MediaCreationTrait;
  use WysiwygTrait;
  use EntityBrowserTrait;

  /**
   * An editor user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $editorUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->markEntityTypeForCleanup('file');

    // Create editor user.
    $this->editorUser = $this->createUser([]);
    $this->editorUser->addRole('editor');
    $this->editorUser->save();

    // Prevent toolbar from overlapping.
    // Maximizing the window gives inconsistent results, so we remove the
    // element all together.
    $role = Role::load('editor');
    $role->revokePermission('access toolbar');
    $role->save();
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    $role = Role::load('editor');
    $role->grantPermission('access toolbar');
    $role->save();

    parent::tearDown();
  }

  /**
   * Tests list pages integration.
   */
  public function testCreateListPages() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->createTestMedia();

    // Create some News test nodes.
    /** @var \Drupal\taxonomy\VocabularyInterface $news_type */
    $news_type = Vocabulary::load('news_type');
    for ($i = 0; $i < 12; $i++) {
      $term = $this->createTerm(
        $news_type,
        ['name' => 'Term ' . $i]
      );
      $this->createNode([
        'title' => 'News number ' . $i,
        'type' => 'oe_sc_news',
        'field_news_type' => $term->id(),
        'body' => 'This is a News content number ' . $i,
        'oe_summary' => 'This is a News introduction number ' . $i,
        'language' => 'en',
        'status' => NodeInterface::PUBLISHED,
        'oe_publication_date' => sprintf('2022-04-%02d', $i + 1),
        'created' => strtotime(sprintf('-%d days', 12 - $i)),
      ]);
    }

    // Create some Events test nodes.
    $countries = [
      'AF', 'BE', 'RO', 'DE', 'FR', 'ES', 'IT', 'AU', 'BB', 'RO', 'CZ', 'FR',
    ];
    /** @var \Drupal\taxonomy\VocabularyInterface $event_type */
    $event_type = Vocabulary::load('event_type');
    for ($i = 0; $i < 12; $i++) {
      $term = $this->createTerm(
        $event_type,
        ['name' => 'Term ' . $i]
      );
      $this->createNode([
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
        'created' => strtotime(sprintf('-%d days', 12 - $i)),
      ]);
    }

    // Create some Person test nodes.
    for ($i = 0; $i < 12; $i++) {
      $this->createNode([
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
        'created' => strtotime(sprintf('-%d days', 12 - $i)),
      ]);
    }

    // Create some Publication test nodes.
    /** @var \Drupal\taxonomy\VocabularyInterface $publication_type */
    $publication_type = Vocabulary::load('publication_type');
    for ($i = 0; $i < 12; $i++) {
      $term = $this->createTerm(
        $publication_type,
        ['name' => 'Term ' . $i]
      );
      $this->createNode([
        'title' => 'Pub ' . $i,
        'type' => 'oe_sc_publication',
        'language' => 'en',
        'status' => NodeInterface::PUBLISHED,
        'field_publication_type' => $term->id(),
        'oe_summary' => 'This is a Publication summary ' . $i,
        'oe_publication_date' => sprintf('2022-04-%02d', $i + 1),
        'created' => strtotime(sprintf('-%d days', 12 - $i)),
      ]);
    }

    // Index content.
    $this->indexItems('oe_list_pages_index');

    // Create the News listing page with complete banner.
    $list_page = $this->createListPage(
      'News list page',
      'oe_sc_news',
      [
        'oelp_oe_sc_news__title',
        'oelp_oe_sc_news__oe_publication_date',
        'oelp_oe_sc_news__type',
      ],
      'This is a summary for oe_sc_news list page.',
      TRUE,
    );
    $this->drupalGet($list_page->toUrl());
    $this->assertEntityAlias($list_page, '/news-list-page');

    // Assert that only News items are displayed.
    $this->assertResultsCount(12);
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

    $this->scrollIntoView('ul.pagination > li:nth-child(2) > a');
    $page->clickLink('2');

    $this->assertResults([
      'News number 10',
      'News number 11',
    ]);

    // Assert that the filter form for News exists.
    $filter_form = $assert_session->elementExists('css', '#oe-list-pages-facets-form');
    $title_input = $filter_form->findField('Keywords');
    $publication_date_input = $filter_form->findField('Publication date');
    $type = $filter_form->findField('Type');
    $search_button = $filter_form->findButton('Refine');
    $this->assertNotNull($search_button);
    $this->assertNotNull($title_input);
    $this->assertNotNull($publication_date_input);
    $this->assertNotNull($type);

    // Filter the News results by date.
    $publication_date_input->setValue('gt');
    $date_input = $filter_form->findField('Date');
    $date_input->setValue('04/04/2022');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(8);
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
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(1);
    $this->assertResults([
      'News number 8',
    ]);

    // Filter the News results by content.
    $title_input->setValue('This is a News content number 10');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(1);
    $this->assertResults([
      'News number 10',
    ]);

    // Filter the News results by introduction.
    $title_input->setValue('This is a News introduction number 11');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(1);
    $this->assertResults([
      'News number 11',
    ]);

    // Filter by a mix of introduction and content in shuffled order.
    $title_input->setValue('6 content number introduction');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(1);
    $this->assertResults([
      'News number 6',
    ]);
    // @todo Remove "7" once we have a default order.
    $title_input->setValue('NUMBER 7 this');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(1);
    $this->assertResults([
      'News number 7',
    ]);

    // Filter results by type.
    $this->scrollIntoView('#edit-reset');
    $filter_form->pressButton('Clear filters');
    $this->selectSlimOption($type, 'Term 4');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(1);
    $this->assertResults([
      'News number 4',
    ]);
    $this->selectSlimOption($type, 'Term 6');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(1);
    $this->assertResults([
      'News number 6',
    ]);

    $this->scrollIntoView('#edit-submit');
    $page->pressButton('Clear filters');
    $this->selectSortByOption('Z-A');
    $this->assertResultsCount(12);
    $this->assertResults([
      'News number 9',
      'News number 8',
      'News number 7',
      'News number 6',
      'News number 5',
      'News number 4',
      'News number 3',
      'News number 2',
      'News number 11',
      'News number 10',
    ]);

    $this->selectSortByOption('A-Z');
    $this->assertResultsCount(12);
    $this->assertResults([
      'News number 0',
      'News number 1',
      'News number 10',
      'News number 11',
      'News number 2',
      'News number 3',
      'News number 4',
      'News number 5',
      'News number 6',
      'News number 7',
    ]);

    $this->selectSortByOption('Date ASC');
    $this->assertResultsCount(12);
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

    $this->selectSortByOption('Date DESC');
    $this->assertResultsCount(12);
    $this->assertResults([
      'News number 11',
      'News number 10',
      'News number 9',
      'News number 8',
      'News number 7',
      'News number 6',
      'News number 5',
      'News number 4',
      'News number 3',
      'News number 2',
    ]);

    // Assert only News nodes are part of the result.
    $title_input->setValue('Event example');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(0);

    // Create an Event listing page with only summary.
    $list_page = $this->createListPage(
      'Event list page',
      'oe_sc_event',
      [
        'oelp_oe_sc_event__location',
        'oelp_oe_sc_event__oe_sc_event_dates',
        'oelp_oe_sc_event__type',
        'oelp_oe_sc_event__title',
      ],
      'This is a summary for oe_sc_event list page.',
    );
    $this->drupalGet($list_page->toUrl());
    $this->assertEntityAlias($list_page, '/event-list-page');

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
    $this->assertResultMetas([
      '1 Apr 2022 - 2 Apr 2022 Town0, Afghanistan',
      '2 Apr 2022 - 3 Apr 2022 Town1, Belgium',
      '3 Apr 2022 - 4 Apr 2022 Town2, Romania',
      '4 Apr 2022 - 5 Apr 2022 Town3, Germany',
      '5 Apr 2022 - 6 Apr 2022 Town4, France',
      '6 Apr 2022 - 7 Apr 2022 Town5, Spain',
      '7 Apr 2022 - 8 Apr 2022 Town6, Italy',
      '8 Apr 2022 - 9 Apr 2022 Town7, Australia',
      '9 Apr 2022 - 10 Apr 2022 Town8, Barbados',
      '10 Apr 2022 - 11 Apr 2022 Town9, Romania',
    ]);

    $this->scrollIntoView('ul.pagination > li:nth-child(2) > a');
    $page->clickLink('2');

    $this->assertResults([
      'Event number 10',
      'Event number 11',
    ]);

    // Assert that the filter form for Events exists.
    $filter_form = $assert_session->elementExists('css', '#oe-list-pages-facets-form');
    $title_input = $filter_form->findField('Title');
    $event_date_input = $filter_form->findField('Event dates');
    $type = $filter_form->findField('Type');
    $search_button = $filter_form->findButton('Refine');
    $this->assertNotNull($search_button);
    $this->assertNotNull($title_input);
    $this->assertNotNull($event_date_input);

    // Filter results by date.
    $event_date_input->setValue('gt');
    $date_input = $filter_form->findField('Date');
    $date_input->setValue('04/04/2022');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(8);
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
    $title_input->setValue('Event number 8');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(1);
    $this->assertResults([
      'Event number 8',
    ]);

    // Assert only Event nodes are part of the result.
    $title_input->setValue('News number 1');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(0);

    // Assert Event title filters only by title.
    $title_input->setValue('This is an Event content number 10');
    $search_button->click();
    $this->assertResultsCount(0);
    $title_input->setValue('This is an Event introduction number 10');
    $search_button->click();
    $this->assertResultsCount(0);

    // Filter results by location.
    $filter_form->pressButton('Clear filters');
    $location = $filter_form->findField('Location');
    $this->selectSlimOption($location, 'France');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(2);
    $this->assertResults([
      'Event number 4',
      'Event number 11',
    ]);
    $this->selectSlimOption($location, 'Romania', TRUE);
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(4);
    $this->assertResults([
      'Event number 2',
      'Event number 4',
      'Event number 9',
      'Event number 11',
    ]);

    // Filter results by type.
    $this->scrollIntoView('#edit-reset');
    $filter_form->pressButton('Clear filters');
    $type = $filter_form->findField('Type');
    $this->selectSlimOption($type, 'Term 3');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(1);
    $this->assertResults([
      'Event number 3',
    ]);
    $this->selectSlimOption($type, 'Term 8');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(1);
    $this->assertResults([
      'Event number 8',
    ]);

    $this->scrollIntoView('#edit-reset');
    $page->pressButton('Clear filters');
    $this->selectSortByOption('Z-A');
    $this->assertResultsCount(12);
    $this->assertResults([
      'Event number 9',
      'Event number 8',
      'Event number 7',
      'Event number 6',
      'Event number 5',
      'Event number 4',
      'Event number 3',
      'Event number 2',
      'Event number 11',
      'Event number 10',
    ]);

    // Test Project list page.
    $date_plus_1 = date('Y-m-d', strtotime('+1 day'));
    $date_plus_10 = date('Y-m-d', strtotime('+10 days'));
    $date_plus_10_calendar_format = date('m/d/Y', strtotime('+10 days'));

    $this->createNode([
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
    ]);

    $this->createNode([
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
    ]);

    $this->createNode([
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
    ]);

    // Index content.
    $this->indexItems('oe_list_pages_index');

    // Create an Project listing page with only image.
    $list_page = $this->createListPage(
      'Project list page',
      'oe_project', [
        'oelp_oe_sc_project__end_date',
        'oelp_oe_sc_project__start_date',
        'oelp_oe_sc_project__status',
        'oelp_oe_sc_project__type',
      ],
      '',
      TRUE,
    );

    $this->drupalGet($list_page->toUrl());
    $this->assertEntityAlias($list_page, '/project-list-page');

    $this->assertResultsCount(3);
    $this->assertResults([
      'Project closed',
      'Project ongoing',
      'Project pending',
    ]);

    // Assert that the filter form for Projects exists.
    $filter_form = $assert_session->elementExists('css', '#oe-list-pages-facets-form');
    $filter_status = $filter_form->findField('Status');
    $filter_type = $filter_form->findField('Project type');
    $assert_session->fieldNotExists('Total budget');
    $filter_start = $filter_form->findField('Start date');
    $filter_end = $filter_form->findField('End date');
    $search_button = $filter_form->findButton('Refine');
    $this->assertNotNull($filter_status);
    $this->assertNotNull($filter_type);
    $this->assertNotNull($filter_start);
    $this->assertNotNull($filter_end);
    $this->assertNotNull($search_button);

    // Filter results by type.
    $this->selectSlimOption($filter_type, 'financing');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(1);
    $this->assertResults([
      'Project closed',
    ]);

    // Filter results by dates.
    $this->scrollIntoView('#edit-reset');
    $filter_form->pressButton('Clear filters');
    $filter_start->setValue('gt');
    $date_input = $filter_form->findField('Date');
    $date_input->setValue('05/19/2022');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(2);
    $this->assertResults([
      'Project ongoing',
      'Project pending',
    ]);

    $filter_start->setValue('lt');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(1);
    $this->assertResults([
      'Project closed',
    ]);

    $dates = $filter_form->findAll('named', ['field', 'Date']);
    $filter_start->setValue('gt');
    $dates[0]->setValue('05/19/2022');
    $filter_end->setValue('lt');
    $dates[1]->setValue($date_plus_10_calendar_format);
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(1);
    $this->assertResults([
      'Project ongoing',
    ]);

    // Filter results by status.
    $this->scrollIntoView('#edit-reset');
    $filter_form->pressButton('Clear filters');
    $this->selectSlimOption($filter_status, 'Closed');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(1);
    $this->assertResults([
      'Project closed',
    ]);

    $this->selectSlimOption($filter_status, 'Ongoing and Planned');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(2);
    $this->assertResults([
      'Project ongoing',
      'Project pending',
    ]);

    $this->scrollIntoView('#edit-reset');
    $page->pressButton('Clear filters');
    $this->selectSortByOption('Z-A');
    $this->assertResultsCount(3);
    $this->assertResults([
      'Project pending',
      'Project ongoing',
      'Project closed',
    ]);

    $this->selectSortByOption('Total budget ASC');
    $this->assertResultsCount(3);
    $this->assertResults([
      'Project closed',
      'Project pending',
      'Project ongoing',
    ]);

    $this->selectSortByOption('Total budget DESC');
    $this->assertResultsCount(3);
    $this->assertResults([
      'Project ongoing',
      'Project pending',
      'Project closed',
    ]);

    // Create a Person listing page with no summary nor image.
    $list_page = $this->createListPage(
      'Person list page',
      'oe_sc_person', [
        'oelp_oe_sc_person__title',
      ]
    );
    $this->drupalGet($list_page->toUrl());
    $this->assertEntityAlias($list_page, '/person-list-page');

    // Assert that only Person items are displayed.
    $this->assertResults([
      'John Doe 0',
      'John Doe 1',
      'John Doe 2',
      'John Doe 3',
      'John Doe 4',
      'John Doe 5',
      'John Doe 6',
      'John Doe 7',
      'John Doe 8',
      'John Doe 9',
    ]);

    $this->scrollIntoView('ul.pagination > li:nth-child(2) > a');
    $page->clickLink('2');

    $this->assertResults([
      'John Doe 10',
      'John Doe 11',
    ]);

    // Assert that the filter form for Person exists.
    $filter_form = $assert_session->elementExists('css', '#oe-list-pages-facets-form');
    $title_input = $filter_form->findField('Name');
    $search_button = $filter_form->findButton('Refine');
    $this->assertNotNull($search_button);
    $this->assertNotNull($title_input);

    // Filter Person results by title.
    $title_input->setValue('John Doe 8');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(1);
    $this->assertResults([
      'John Doe 8',
    ]);

    // Assert only Person nodes are part of the result.
    $title_input->setValue('News number 1');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(0);

    // Assert Title filters only by Person title.
    $title_input->setValue('This is a person short description number 10');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(0);
    $title_input->setValue('This is a person additional info number 10');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(0);

    $this->scrollIntoView('#edit-reset');
    $page->pressButton('Clear filters');
    $this->selectSortByOption('Z-A');
    $this->assertResultsCount(12);
    $this->assertResults([
      'John Doe 9',
      'John Doe 8',
      'John Doe 7',
      'John Doe 6',
      'John Doe 5',
      'John Doe 4',
      'John Doe 3',
      'John Doe 2',
      'John Doe 11',
      'John Doe 10',
    ]);

    // Create a Publication listing page with no summary nor image.
    $list_page = $this->createListPage(
      'Publication list page',
      'oe_sc_publication',
      [
        'oelp_oe_sc_publication__keyword',
        'oelp_oe_sc_publication__publication_date',
        'oelp_oe_sc_publication__type',
      ]
    );
    $this->drupalGet($list_page->toUrl());
    $this->assertEntityAlias($list_page, '/publication-list-page');

    // Assert that only Publication items are displayed.
    $this->assertResultsCount(12);
    $this->assertResults([
      'Pub 0',
      'Pub 1',
      'Pub 2',
      'Pub 3',
      'Pub 4',
      'Pub 5',
      'Pub 6',
      'Pub 7',
      'Pub 8',
      'Pub 9',
    ]);

    $this->scrollIntoView('ul.pagination > li:nth-child(2) > a');
    $page->clickLink('2');

    $this->assertResults([
      'Pub 10',
      'Pub 11',
    ]);

    // Assert that the filter form for Publication exists.
    $this->drupalGet($list_page->toUrl());
    $filter_form = $assert_session->elementExists('css', '#oe-list-pages-facets-form');
    $keyword_input = $filter_form->findField('Keyword');
    $publication_date_input = $filter_form->findField('Publication date');
    $type_select = $filter_form->findField('Type');
    $search_button = $filter_form->findButton('Refine');
    $this->assertNotNull($keyword_input);
    $this->assertNotNull($publication_date_input);
    $this->assertNotNull($type_select);
    $this->assertNotNull($search_button);

    // Filter the News results by date.
    $publication_date_input->setValue('gt');
    $date_input = $filter_form->findField('Date');
    $date_input->setValue('04/04/2022');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();

    $this->assertResultsCount(8);
    $this->assertResults([
      'Pub 4',
      'Pub 5',
      'Pub 6',
      'Pub 7',
      'Pub 8',
      'Pub 9',
      'Pub 10',
      'Pub 11',
    ]);

    // Filter the Publication results by title.
    $keyword_input->setValue('Pub 8');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(1);
    $this->assertResults([
      'Pub 8',
    ]);

    // Filter the Publication results by content.
    $keyword_input->setValue('This is a Publication summary 10');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(1);
    $this->assertResults([
      'Pub 10',
    ]);

    // Filter results by type.
    $this->scrollIntoView('#edit-reset');
    $filter_form->pressButton('Clear filters');
    $type = $filter_form->findField('Type');
    $this->selectSlimOption($type, 'Term 3');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(1);
    $this->assertResults([
      'Pub 3',
    ]);
    $this->selectSlimOption($type, 'Term 8');
    $this->scrollIntoView('#edit-submit');
    $search_button->click();
    $this->assertResultsCount(1);
    $this->assertResults([
      'Pub 8',
    ]);

    $this->scrollIntoView('#edit-reset');
    $page->pressButton('Clear filters');
    $this->selectSortByOption('Z-A');
    $this->assertResultsCount(12);
    $this->assertResults([
      'Pub 9',
      'Pub 8',
      'Pub 7',
      'Pub 6',
      'Pub 5',
      'Pub 4',
      'Pub 3',
      'Pub 2',
      'Pub 11',
      'Pub 10',
    ]);
  }

  /**
   * Asserts the total results of the search.
   *
   * @param int $expected_count
   *   Expected number of results to be reported.
   */
  protected function assertResultsCount(int $expected_count): void {
    $title = $this->assertSession()->elementExists('css', '.col-xl-8 h4.mb-0');
    $this->assertSame(
      sprintf('Results (%s)', $expected_count),
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
   * Asserts search results item's meta information.
   *
   * @param array $expected
   *   Expected meta items.
   */
  protected function assertResultMetas(array $expected): void {
    $items = $this->assertSession()
      ->elementExists('css', '.bcl-listing')
      ->findAll('css', '.mt-3.me-n3');
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
   * @param string $title
   *   The title of the list page.
   * @param string $bundle
   *   Nodes of this bundle will be listed.
   * @param array $exposed_filters
   *   Facet machine names linked to the bundle's facet source.
   * @param string $summary
   *   The summary of the list page.
   * @param string $has_media
   *   The list page has a media in the banner.
   *
   * @return \Drupal\node\NodeInterface
   *   The list page node created.
   */
  protected function createListPage(string $title, string $bundle, array $exposed_filters, string $summary = '', bool $has_media = FALSE): NodeInterface {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $expected_content_banner = [
      'title' => $title,
      'content' => $summary,
      'meta' => [],
      'badges' => [],
      'image' => NULL,
    ];

    if ($has_media) {
      // Create a sample image media entity to be embedded.
      $media = $this->createImageMedia([
        'name' => "$bundle List page Image test",
        'oe_media_image' => [
          'alt' => "$bundle List page Image test alt",
        ],
      ]);

      $expected_content_banner['image'] = [
        'alt' => "$bundle List page Image test alt",
        'src' => $media->get('oe_media_image')->entity->getFilename(),
      ];
    }

    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/add/oe_list_page');
    $page->fillField('Title', $title);
    if ($has_media) {
      $this->addEntityBrowserMedia("$bundle List page Image test");
    }
    $summary_field = $page->findField('Summary');
    $this->assertEquals('simple_rich_text', $this->getWysiwigTextFormat($summary_field));
    $this->enterTextInWysiwyg('Summary', $summary);
    $page->selectFieldOption('Source entity type', 'node');
    $page->selectFieldOption('Source bundle', $bundle);
    $assert_session->waitForField('Expose sort')->check();
    $page->checkField('Override default exposed filters');
    foreach ($exposed_filters as $filter_name) {
      $page->checkField("emr_plugins_oe_list_page[wrapper][exposed_filters][$filter_name]");
    }
    $page->pressButton('Save');

    $node = $this->getNodeByTitle($title);
    $content_banner_assert = new ContentBannerAssert();
    $content_banner_assert->assertPattern($expected_content_banner, $assert_session->elementExists('css', '.bcl-content-banner')->getOuterHtml());

    $this->drupalLogout();

    return $node;
  }

  /**
   * Select a sort option and waits until is applied.
   *
   * @param string $option
   *   The option to be applied.
   */
  protected function selectSortByOption(string $option): void {
    $this->getSession()->getPage()->selectFieldOption('Sort by', $option);
    $this->assertSession()->waitForElementVisible('xpath', "//option[@selected=selected and text()='$option']");
  }

  /**
   * Adds a media through entity browser.
   *
   * @param string $media_name
   *   The media name to be added.
   */
  protected function addEntityBrowserMedia(string $media_name) {
    $assert_session = $this->assertSession();

    // Assert the media browser for the thumbnail field.
    $thumbnail_fieldset = $assert_session->elementExists('css', '[data-drupal-selector="edit-oe-featured-media-wrapper"]');
    $assert_session->buttonExists('Select media', $thumbnail_fieldset)->press();
    $assert_session->assertWaitOnAjaxRequest();
    $this->getSession()->switchToIFrame('entity_browser_iframe_images');
    $assert_session->linkExistsExact('Media library');
    $assert_session->linkExistsExact('Search in AV Portal');

    // Assert the exposed filters.
    $assert_session->fieldExists('Filter by name');
    $assert_session->fieldExists('Language');
    $this->assertEquals([
      'All' => '- Any -',
      'av_portal_photo' => 'AV Portal Photo',
      'image' => 'Image',
    ], $this->getSelectOptions($assert_session->selectExists('Media type')));

    // Make sure this entity browser shows only the expected media bundles.
    $existing_medias = \Drupal::entityTypeManager()->getStorage('media_type')->loadMultiple();
    $expected_media_bundles = [
      'av_portal_photo',
      'image',
    ];
    foreach (array_diff(array_keys($existing_medias), $expected_media_bundles) as $unwanted_bundle) {
      $assert_session->pageTextNotContains($existing_medias[$unwanted_bundle]->label());
    }

    // Check that the media is present after adding it.
    $this->getMediaBrowserTileByMediaName($media_name)->click();
    $assert_session->buttonExists('Select media')->press();
    $this->getSession()->switchToIFrame();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains($media_name);
  }

}
