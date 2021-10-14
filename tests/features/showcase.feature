@api @casMockServer
Feature: Create a OpenEuropa Showcase Page.
  As an authenticated user
  I need to be able to create a page
  so that I can add multiple paragraphs.
  
  Background:
    Given CAS users:
      | Username     | E-mail                       | Password  | First name | Last name | Department    | Organisation |
      | showcaseuser | showcaseuser@showcase.com.eu | Qwerty098 | User       | Showcase  | DIGIT.A.3.001 | eu.europa.ec |

  @cleanup:user
  Scenario: Login/Logout with eCAS mockup server of internal users
    Given the site is configured to make users active on creation
    When I am on the homepage
    And I click "Log in"

    # Redirected to the mock server.
    And I fill in "Username or e-mail address" with "showcaseuser@showcase.com.eu"
    And I fill in "Password" with "Qwerty098"
    And I press the "Login!" button

    # Redirected back to Drupal.
    Then I should see "You have been logged in."
    And I should see the link "Log out"
    And I should not see the link "Log in"
    
    # Logout
    When I click "Log out"
    And I should see "You are about to be logged out of EU Login."
    And I should see the link "No, stay logged in!"
    # Redirected to the Ecas mockup server.
    And I press the "Log me out" button
    # Redirected back to Drupal.
    And I should see "You have logged out from EU Login."
    And I should not see the link "My account"
    And I should not see the link "Log out"
    And I should see the link "Log in"
  
  @DrupalLogin @cleanup:media
  Scenario: Create demo page using the Showcase Page content type.
  Given I am logged in as a user with the "access toolbar, create oe_showcase_page content, edit any oe_showcase_page content, delete any oe_showcase_page content, access content overview, create document media, delete any document media, edit any document media, create media, delete any media, update any media" permission

    # Create a local "Document" media.
    When I go to "the document creation page"
    And I fill in "Name" with "My example local document 1"
    And I select "Local" from "File Type"
    And I attach the file "example_1.pdf" to "File"
    And I press "Save"
    Then the response status code should be 200

    # Create a page
    When I go to "the showcase page creation page"
    And I fill in "Title" with "Demo showcase page"
    And I fill in "Description" with "Demo showcase page description"

    # Add Accordion
    And I press "Add Accordion"
    And I fill in "Title" with "Accordion item 1 title" in the 1st "Accordion item" paragraph
    And I fill in "Body" with "Accordion item 1 body" in the 1st "Accordion item" paragraph
    And I press "Add Accordion item"
    And I fill in "Title" with "Accordion item 2 title" in the 2nd "Accordion item" paragraph
    And I fill in "Body" with "Accordion item 2 body" in the 2nd "Accordion item" paragraph
    And I select "Feedback" from "Icon" in the 2nd "Accordion item" paragraph

    # Add local document
    And I press "Add Document"
    And I fill in "Use existing media" with "My example local document 1"

    # Save page
    When I press "Save"
    Then the response status code should be 200

    # Assert page has the added elements.
    And I should see the heading "Demo showcase page"
    And I should see the link "example_1.pdf"
    And I should see the text "Accordion item 1 title"
