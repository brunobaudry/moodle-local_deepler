@local @local_deepler @envtest
Feature: Test env loading
  Scenario: Env is available
    Then the environment variable 'DEEPL_API_TOKEN' should be set
