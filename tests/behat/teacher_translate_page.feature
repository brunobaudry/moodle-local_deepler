@local @local_deepler @javascript
Feature: Check initial state of the course translation page
  In order to translate course content
  As a teacher with translator rights
  I need to see the default initial controls and states on the translation page

  Background:
    Given the DeepL API key is configured from environment
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | idnumber |
      | Course 1 | C1        | C1       |
    And the following "permission overrides" exist:
      | capability                     | permission | role           | contextlevel | reference |
      | local/deepler:edittranslations | Allow      | editingteacher | Course       | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1"

  Scenario: Initial state of controls when launching DeepL Translator in a course
    Given I am on the "C1" "course" page
    When I navigate to "DeepL Translator" in current page administration
    Then I should see "Source lang {mlang other}"
    And I should see "Target language {mlang ?}"
    And the field "select-target-language" matches value ""
    And the "show-updated" checkbox should not be checked
    And the "show-needsupdate" checkbox should be checked
    And the "show-hidden" checkbox should be checked
    And the field "select-section" matches value "* Settings"
    And the "select-all" "checkbox" should be disabled
    And the "local_deepler-translate-header-translatebutton" "button" should be disabled
    And the "local_deepler-translate-header-saveallbutton" "button" should be disabled
