@local @local_deepler @javascript
Feature: Feature for local_deepler plugin main admin page
  In order to ensure the plugin admin is loaded

  Scenario: Test example scenario
    Given I log in as "admin"
    When I navigate to "Plugins > DeepL Translator" in site administration
    Then I should see "DeepL Translator"
    Then I should see "local_deepler | apikey"
    Then I should see "local_deepler | hideiframesadmin"
    Then I should see "local_deepler | latexescapeadmin"
    Then I should see "local_deepler | preescapeadmin"
    Then I should see "local_deepler | scannedfieldsize"
    Then I should see "Current version"
