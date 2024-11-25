@local @local_deepler
Feature: Example feature for local_deepler plugin
  In order to ensure the plugin works correctly
  As a developer
  I need to test its functionality

  Scenario: Test example scenario
    Given I log in as "admin"
    When I navigate to "Plugins overview" in site administration
    Then the following should exist in the "plugins-control-panel" table:
      | Plugin name   |
      | local_deepler |
