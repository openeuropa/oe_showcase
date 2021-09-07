@api
Feature: Showcase search sorting.
  As a site administrator
  I need to be able to sort content on the showcase search.

  Scenario: Test sorting on Showcase Search.
    Given I visit "user/login"
    Then I fill in "Username" with "admin"
    Then I fill in "Password" with "admin"
    And I press "Log in"
    # Add search block to page for testing:
    Then I visit "admin/structure/block/add/views_exposed_filter_block%3Ashowcase_search-showcase_search_page/bartik?region=content"
    Then I fill in "Pages" with "/showcase-search"
    Then I fill in "Machine-readable name" with "exposedformshowcase_searchpage_test"
    And I press "Save block"
    Then I visit "showcase-search"
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