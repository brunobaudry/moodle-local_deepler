<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Behat Context Class.
 *
 * @package    local_deepler
 * @copyright  2024 bruno.baudry@bfh.ch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/env_loader.php');

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\DebugExtension\Message;
use Behat\Gherkin\Node\PyStringNode;

/**
 * Class feature_context.
 * Defines steps for Behat tests.
 *
 * @package local_deepler
 */
class behat_local_deepler_apitester implements Context {
    /**
     * Api pro endpoint.
     *
     * @var string
     */
    static protected $deeplpro = 'https://api.deepl.com/v2/translate?';
    /**
     *  Api free endpoint.
     *
     * @var string
     */
    static protected $deeplfree = 'https://api-free.deepl.com/v2/translate?';
    /** @var array */
    private $headers = [];
    /**
     * @var string
     */
    private $response;
    /** @var string */
    private $statuscode;

    /**
     * feature_context constructor.
     * Loads environment variables.
     */
    public function __construct() {
        env_loader::load(__DIR__ . '/../../.env');
    }

    /**
     * Set the API Token.
     *
     * @Given I set the DeepL api token to :value
     * @param string $value
     * @return void
     */
    public function i_set_the_deepl_token_to($value): void {
        $this->i_set_the_header_to('Authorization', "DeepL-Auth-Key $value");
    }

    /**
     * Set a header for the API request.
     *
     * @Given I set the header :header to :value
     * @param string $header The header name.
     * @param string $value The header value.
     */
    public function i_set_the_header_to($header, $value): void {
        // Replace placeholders with actual environment variable values.
        $value = preg_replace_callback('/\{\{(\w+)\}\}/', function($matches) {
            $envvar = $matches[1];
            return isset($_ENV[$envvar]) ? $_ENV[$envvar] : $matches[0];
        }, $value);
        $this->headers[$header] = $value;
        $this->headers['Content-Type'] = 'application/json';
    }

    /**
     * Wrapper for DeepL.
     *
     * @When I post a DeepL request with body:
     * @param string $body
     * @return void
     */
    public function i_post_a_deepl_request_to($body): void {
        $url = substr($this->headers['Authorization'], -3) === ':fx' ? self::$deeplfree : self::$deeplpro;
        $this->i_send_a_request_to_with_body('POST', $url, $body);
    }

    /**
     * Send an API request with a specified method, URL, and body.
     *
     * @When I send a :method request to :url with body:
     * @param string $method The HTTP method (e.g., GET, POST).
     * @param string $url The URL to send the request to.
     * @param PyStringNode $body The request body.
     */
    public function i_send_a_request_to_with_body($method, $url, PyStringNode $body): void {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_map(
                function($key, $value) {
                    return "$key: $value";
                },
                array_keys($this->headers),
                $this->headers
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

        $this->response = curl_exec($ch);
        $this->statuscode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
    }
    /**
     * Before scenario hook to check for the presence of the API token.
     *
     * @param \Behat\Behat\Hook\Scope\BeforeScenarioScope $scope
     * @return void
     * @BeforeScenario
     */
    public function beforescenario(BeforeScenarioScope $scope) {
        if (empty($_ENV['DEEPL_API_TOKEN'])) {
            throw new PendingException('DEEPL_API_TOKEN is not set. Skipping scenario.');
        }
    }

    /**
     * Verify the response status code.
     *
     * @Then the response status code should be :statusCode
     * @param int $statusCode The expected status code.
     * @throws \Exception If the status code does not match.
     */
    public function the_response_status_code_should_be($statuscode): void {
        if ($this->statuscode != $statuscode) {
            throw new Exception("Expected status code $statuscode but got " . $this->statuscode);
        }
    }

    /**
     * Verify the response contains specific text.
     *
     * @Then the response should contain :text
     * @param string $text The text expected in the response.
     * @throws \Exception If the response does not contain the text.
     */
    public function the_response_should_contain($text): void {
        if (strpos($this->response, $text) === false) {
            throw new Exception("Response does not contain expected text: $text");
        }
    }
}