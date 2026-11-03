@local @local_deepler @deepler_menu
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
    And the following "activities" exist:
      | activity | name                 | intro                   | course | assignsubmission_onlinetext_enabled |
      | assign   | Test assignment name | Submit your online text | C1     | 1                                   |
    And the following "permission overrides" exist:
      | capability                     | permission | role           | contextlevel | reference |
      | local/deepler:edittranslations | Allow      | editingteacher | Course| C1|
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1"

  Scenario: Teacher can see the plugin entry in the course 'MORE' menu
    Given I am on the "C1" "course" page
    Then I should see "Course 1"
    And I navigate to "DeepL Translator" in current page administration
    Then I should see "DeepL Translator"

  Scenario: Teacher can see the plugin entry in the activity setting and section setting
    Given I am on the "Test assignment name" Activity page
    And I navigate to "Settings" in current page administration
    Then I should see "DeepL Translator"
    And I am on "C1" course homepage with editing mode on
    When I edit the section "1"
    Then I should see "DeepL Translator"
