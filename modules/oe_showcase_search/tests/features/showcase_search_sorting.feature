@api
Feature: Showcase search sorting.
  As a site administrator
  I need to be able to sort content on the showcase search.

  Background:
    Given I am logged in as a user with the "administer account settings, administer blocks" permission
  
  Scenario: Add sorting form block to search
    When I visit "admin/structure/block/add/views_exposed_filter_block%3Ashowcase_search-showcase_search_page/bartik?region=content"
    And I fill in "Pages" with "/showcase-search"
    And I fill in "Machine-readable name" with "exposedformshowcase_searchpage_test"
    And I press "Save block"
    Then I should see "The block configuration has been saved."
    
  Scenario: Test sorting on Showcase Search by title.
    When I visit "showcase-search"
    Then I should see "Showcase Search"
    # Test sorting by title.
    And I select "A-Z" from "Sort by"
    And I press "Apply"
    Then I should see "Abico Diam Jugis"
    When I select "Z-A" from "Sort by"
    And I press "Apply"
    Then I should see "Voco"
    # Test sorting by published date.
    And I select "Published on Asc" from "Sort by"
    And I press "Apply"
    Then I should see "Hendrerit"
    And I select "Published on Desc" from "Sort by"
    And I press "Apply"
    Then I should see "Imputo Neo Sagaciter"
    # Delete display
    Then I visit "admin/structure/block/manage/exposedformshowcase_searchpage_test/delete"
    And I press "Remove"
    Then I should see "The block Exposed form: showcase_search-showcase_search_page has been removed."