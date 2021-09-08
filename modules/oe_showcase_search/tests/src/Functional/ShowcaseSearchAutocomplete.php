<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_search\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\search_api_autocomplete\Entity\Search;
use Drupal\views\Entity\View;

/**
 * Ensure Autocomplete is enabled and working.
 *
 * @group oe_showcase_search_autocomplete
 */
class ShowcaseSearchAutocomplete extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'views',
    'system',
    'search_api',
    'search_api_db',
    'oe_showcase_search',
    'oe_showcase_default_content',
    'search_api_autocomplete',
  ];

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'oe_showcase';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * @var bool
   *
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   */
  protected $strictConfigSchema = FALSE;

  /**
   * The search view.
   *
   * @var \Drupal\views\Entity\View
   */
  protected $view_id = 'showcase_search';
  
  /**
   * The ID of the search index used in this test.
   *
   * @var string
   */
  protected $indexId = 'showcase_search_index';

  /**
   * The ID of the search entity created for this test.
   *
   * @var string
   */
  protected $searchId = 'showcase_search';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->view = View::load($this->view_id);
    $this->adminUser = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($this->adminUser);
  }
  
  /**
   * Tests autocompletion is enabled for the demo search index.
   */
  public function testSearchAutocompleteIsEnabled() {
    $assert_session = $this->assertSession();

    // Confirm it is enabled.
    $path = "admin/config/search/search-api/index/$this->indexId/autocomplete";
    $this->drupalGet($path);

    // Check whether all expected groups and searches are present.
    $assert_session->pageTextContains('Searches provided by Views');
    $assert_session->pageTextContains('Showcase Search');
    $assert_session->checkboxChecked("searches[{$this->searchId}]");
  }
  
  /**
   * Verifies that the search entity's dependencies are correct.
   */
  public function testViewAutocompleteDependency() {
    /** @var \Drupal\search_api_autocomplete\SearchInterface $search */
    $search = Search::load($this->searchId);
    $expected = [
      'config' => [
        "search_api.index.$this->indexId",
        "views.view.{$this->searchId}",
      ],
      'module' => [
        'search_api_autocomplete',
        'views',
      ],
    ];
    $dependencies = $search->getDependencies();
    ksort($dependencies);
    sort($dependencies['config']);
    sort($dependencies['module']);
    $this->assertEquals($expected, $dependencies);
  }

}
