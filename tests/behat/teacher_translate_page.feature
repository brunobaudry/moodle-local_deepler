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
    When I navigate to "DeepLer Translator" in current page administration
    Then I should see "Source"
    And I should see "Target"
    And the field "select-target-language" matches value ""
    And the "show-updated" checkbox should not be checked
    And the "show-needsupdate" checkbox should be checked
    And the "show-hidden" checkbox should be checked
    And the field "select-section" matches value "* Settings"
    And the "select-all" "checkbox" should be disabled
    And the "local_deepler-translate-header-translatebutton" "button" should be disabled
    And the "local_deepler-translate-header-saveallbutton" "button" should be disabled

  Scenario: Open and customize row translation settings via modal
    Given I am on the "C1" "course" page
    When I navigate to "DeepLer Translator" in current page administration
    Then ".local_deepler__row-settings-btn" "css_element" should exist
    When I click on ".local_deepler__row-settings-btn" "css_element"
    Then I should see "Options for:"
    And I should see "Reset to Global Defaults"
    When I click on "Reset to Global Defaults" "button"
    Then "Options for:" "dialogue" should not be visible

  Scenario: Row settings modal inherits active global settings and can reset to current global values
    Given I am on the "C1" "course" page
    When I navigate to "DeepLer Translator" in current page administration
    And I click on "#local_deepler__settings" "css_element"
    And I click on "#preserve_formatting" "css_element"
    And I set the field "local_deepler__context" to "Global translation context"
    And I click on "Save changes" "button"
    When I click on ".local_deepler__row-settings-btn" "css_element"
    Then I should see "Options for:"
    And the "modal_preserve_formatting" checkbox should be checked
    And the field "modal_local_deepler__context" matches value "Global translation context"
    When I set the field "modal_local_deepler__context" to "Row custom context"
    And I click on "Save changes" "button"
    Then "Options for:" "dialogue" should not be visible
    And ".local_deepler__custom-settings-indicator" "css_element" should be visible
    When I click on ".local_deepler__row-settings-btn" "css_element"
    Then I should see "Options for:"
    And the field "modal_local_deepler__context" matches value "Row custom context"
    When I click on "Reset to Global Defaults" "button"
    Then "Options for:" "dialogue" should not be visible
    And ".local_deepler__custom-settings-indicator" "css_element" should not be visible

  Scenario: Dynamic refresh of previousTranslationStatus bullets when selecting target language
    Given I am on the "C1" "course" page
    When I navigate to "DeepLer Translator" in current page administration
    Then "#previousTranslationStatus.badge-dark" "css_element" should exist
    When I select target language "English"
    Then "#previousTranslationStatus.badge-dark" "css_element" should not exist
    And "#previousTranslationStatus.badge-primary" "css_element" should exist
    And the "select-all" "checkbox" should be enabled
    When I click on "select-all" "checkbox"
    Then the "local_deepler-translate-header-translatebutton" "button" should be enabled
