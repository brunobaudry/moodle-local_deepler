@local @local_deepler @javascript
Feature: Test DeepLer glossary interface for admin and teachers/translators
  In order to manage custom translation glossaries
  As an administrator or a teacher/translator
  I need to access and view the glossary management interface and help instructions

  Background:
    Given the DeepL API key is configured from environment
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "permission overrides" exist:
      | capability                     | permission | role           | contextlevel | reference |
      | local/deepler:edittranslations | Allow      | editingteacher | System       |           |
    And the following "system role assigns" exist:
      | user     | role           | contextlevel | reference |
      | teacher1 | editingteacher | System       |           |

  @new
  Scenario: Admin can access and view the glossary manager in site administration
    Given I log in as "admin"
    When I navigate to "Plugins > DeepL Translator" in site administration
    Then I should see "DeepLer glossaries"
    When I click on "DeepLer glossaries" "link"
    Then I should see "DeepLer glossaries"
    And I should see "Upload" in the ".glossary-upload" "css_element"
    And I should see "Supported file types: CSV, TSV, XLSX, XLS, ODS"
    And I should see "Name" in the ".generaltable" "css_element"
    And I should see "Entries" in the ".generaltable" "css_element"
    And I should see "Visibility" in the ".generaltable" "css_element"
    And I should see "Pool" in the ".generaltable" "css_element"
    And I should see "Actions" in the ".generaltable" "css_element"
    And I should see "Manage DeepLer's settings"
    And I should see "API Token Settings"
    When I click on ".glossary-upload .fa-circle-question" "css_element"
    Then I should see "Upload a simple Source|Target 2 columns spreadsheet" in the "#helpModal" "css_element"
    And I should see "Rules" in the "#helpModal" "css_element"
    And I click on "OK" "button" in the "#helpModal" "css_element"

  @new
  Scenario: Teacher with translator rights can access and view the glossary manager in user preferences
    Given I log in as "teacher1"
    When I follow "Preferences" in the user menu
    Then I should see "DeepLer glossaries"
    When I click on "DeepLer glossaries" "link"
    Then I should see "DeepLer glossaries"
    And I should see "Private glossaries"
    And I should see "Upload" in the ".glossary-upload" "css_element"
    And I should see "Supported file types: CSV, TSV, XLSX, XLS, ODS"
    And I should see "Name" in the ".generaltable" "css_element"
    And I should see "Entries" in the ".generaltable" "css_element"
    And I should see "Visibility" in the ".generaltable" "css_element"
    And I should see "Actions" in the ".generaltable" "css_element"
    When I click on ".glossary-upload .fa-circle-question" "css_element"
    Then I should see "Upload a simple Source|Target 2 columns spreadsheet" in the "#helpModal" "css_element"
    And I click on "OK" "button" in the "#helpModal" "css_element"

  @new
  Scenario: User without translation capabilities cannot see the glossary manager link in user preferences
    Given I log in as "student1"
    When I follow "Preferences" in the user menu
    Then I should not see "DeepLer glossaries" in the "#region-main" "css_element"
