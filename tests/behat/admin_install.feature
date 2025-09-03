@local @local_deepler @javascript @admin
Feature: Feature for local_deepler plugin in the plugin list overview
  In order to ensure the plugin is installed

  Scenario: View plugin in plugin overview
    Given I log in as "admin"
    When I navigate to "Plugins > Plugins overview" in site administration
    Then the following should exist in the "plugins-control-panel" table:
      | Plugin name   |
      | local_deepler |
