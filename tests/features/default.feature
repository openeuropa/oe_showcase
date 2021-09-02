Feature: Test Behat testing itself

  Scenario: Check that Behat testing works
    When I go to homepage
    Then the response status code should be 200
