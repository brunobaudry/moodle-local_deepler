@local @local_deepler @javascript @deepl_api
Feature: Test external API call with dynamic token
  Scenario: Make an authenticated API call to Deepl
    Given I set the DeepL api token to "{{DEEPL_API_TOKEN}}"
    When I post a DeepL request with body:
      """
      {
        "text": ["Hello, world!"],
        "target_lang": "DE"
      }
      """
    Then the response status code should be 200
    And the response should contain "Hallo, Welt!"
