<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_search\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\search_api_autocomplete\Entity\Search;

/**
 * Ensure Facets is enabled and working.
 *
 * @group oe_showcase_search_facets
 */
class ShowcaseSearchFacets extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'system',
    'facets',
    'facets_form',
    'facets_form_date_range',
    'facets_summary',
    'oe_showcase_search',
    'oe_showcase_default_content',
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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($this->adminUser);
  }
  
  /**
   * Tests the facets exists for the showcase_search view.
   */
  public function testSearchFacetsAreEnabled(): void {
    $assert_session = $this->assertSession();

    // Confirm Facets is enabled.
    $this->drupalGet("admin/config/search/facets");
    $this->assertSession()->statusCodeEquals(200);

    // Check whether all expected elements and searches are present.
    $assert_session->pageTextContains('Facets');
    // Assert that views Showcase Search is available for facets.
    $assert_session->pageTextContains('search_api:views_block__showcase_search__block_1');
    // Assert that Facet Options exists.
    $assert_session->pageTextContains('options');
    // Assert that Facet Authored on exists.
    $assert_session->pageTextContains('authored_on');
    // Assert that Facet Summary exists.
    $assert_session->pageTextContains('showcase_summary');
  }
  
  /**
   * Tests the facets exists for the showcase_search view.
   */
  public function testSearchFacetsSummaryIsConfigured(): void {
    $assert_session = $this->assertSession();

    // Confirm it is enabled.
    $this->drupalGet("admin/config/search/facets/facet-summary/showcase_summary/edit");
    $this->assertSession()->statusCodeEquals(200);

    // Check whether all expected groups and searches are present.
    $assert_session->pageTextContains('Edit Showcase Summary facets summary');

    // Confirms Summary has Authored on enabled.
    $assert_session->checkboxChecked("facets[authored_on][checked]");
    // Confirms Summary has Options enabled.
    $assert_session->checkboxChecked("facets[options][checked]");

    // Confirm Summary settings.
    // Confirm "Show a summary of how many results were found" is checked.
    $assert_session->checkboxChecked("facets_summary_settings[show_count][status]");
    // Confirm "Hide Summary when Facet Source is not rendered" is checked.
    $assert_session->checkboxChecked("facets_summary_settings[hide_when_not_rendered][status]");
    // Confirm "Show a summary of all selected facets" is checked.
    $assert_session->checkboxChecked("facets_summary_settings[show_summary][status]");
    // Confirm "Show a text when there are no results" is checked.
    $assert_session->checkboxChecked("facets_summary_settings[show_text_when_empty][status]");
    // And message "No results found" is set:
    $assert_session->fieldValueEquals('facets_summary_settings[show_text_when_empty][settings][text][value]', 'No results found.');
    //Confirm "Adds reset facets link" is checked.
    $assert_session->checkboxChecked("facets_summary_settings[reset_facets][status]");
    // And the reset link text is "Clear".
    $assert_session->fieldValueEquals('facets_summary_settings[reset_facets][settings][link_text]', 'Clear');
  }

}
