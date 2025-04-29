@local @local_deepler @javascript @deepler_page
Feature: Check the main translator page.
  As a translator I should see some fields and buttons on the main page.
  Background:
    Given I set the DeepL api token to "{{DEEPL_API_TOKEN}}"
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | idnumber |
      | Course 1 | C1        | C1       |
    And the following "permission overrides" exist:
      | capability                     | permission | role           | contextlevel | reference |
      | local/deepler:edittranslations | Allow      | editingteacher | Course| C1|
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I reload the page
    And I log in as "teacher1"

  Scenario: Teacher can see the plugin entry in the course 'MORE' menu
    Given I am on the "C1" "course" page
    And I navigate to "DeepL Translator" in current page administration
    Then I should see "Advanced settings"
