@local @local_deepler @javascript
Feature: Feature for local_deepler plugin
  In order to ensure the plugin is installed

  Scenario: Test example scenario
    Given I log in as "admin"
    When I navigate to "Plugins > Plugins overview" in site administration
    When I scroll to the element with css selector "tr.plugintypeheader.type-local"
    Then the following should exist in the "plugins-control-panel" table:
      | Plugin name   |
      | local_deepler |
