@local @local_deepler @javascript @deepler_menu
Feature: Check course 'MORE' menu entry with DeepL Translator
  In order to access plugin functionality
  As a teacher
  I need to see the plugin entry in the course 'MORE' menu
  Background:
    Given the following "users" exist:
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
    Then I should see "Course 1"
    And I navigate to "More" in current page administration
    And I navigate to "DeepL Translator" in current page administration
    Then I should see "DeepL Translator"
