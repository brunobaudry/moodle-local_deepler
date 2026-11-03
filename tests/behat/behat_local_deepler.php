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
 * Behat css finder helper code. Probably a duplicate but helped us understand extensions.
 *
 * @package    local_deepler
 * @category   test
 * @copyright  2024 bruno.baudry@bfh.ch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/env_loader.php');

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Mink\Exception\ExpectationException;

/**
 * Behat css finder helper code. Probably a duplicate but helped us understand extensions.
 */
class behat_local_deepler extends behat_base {
    /**
     * Set DeepL API key in local_deepler configuration.
     *
     * @Given the DeepL API key is configured from environment
     * @Given I set the DeepL api key from env
     * @Given I set the DeepL api key to :value
     * @param string $value
     * @return void
     */
    public function i_set_the_deepl_api_key(string $value = '{{DEEPL_API_TOKEN}}'): void {
        env_loader::load(__DIR__ . '/../../.env');
        if (preg_match('/\{\{(\w+)\}\}/', $value, $matches)) {
            $value = getenv($matches[1]) ?: '';
        }
        if (empty($value) && getenv('DEEPL_API_TOKEN')) {
            $value = getenv('DEEPL_API_TOKEN');
        }
        if (!empty($value)) {
            set_config('apikey', $value, 'local_deepler');
        }
    }

    /**
     * Checks that the specified checkbox is not checked.
     *
     * @Then the :checkbox checkbox should not be checked
     * @Then the :checkbox checkbox is not checked
     * @param string $checkbox The locator, label, id, or name of the checkbox
     * @return void
     */
    public function the_checkbox_should_not_be_checked(string $checkbox): void {
        $node = $this->find_field($checkbox);
        if ($node->isChecked()) {
            throw new ExpectationException("The '$checkbox' checkbox is checked", $this->getSession());
        }
    }

    /**
     * Checks that the specified checkbox is checked.
     *
     * @Then the :checkbox checkbox should be checked
     * @Then the :checkbox checkbox is checked
     * @param string $checkbox The locator, label, id, or name of the checkbox
     * @return void
     */
    public function the_checkbox_should_be_checked(string $checkbox): void {
        $node = $this->find_field($checkbox);
        if (!$node->isChecked()) {
            throw new ExpectationException("The '$checkbox' checkbox is not checked", $this->getSession());
        }
    }

    /**
     * Behat helper for css selector.
     *
     * @When I scroll to the element with css selector :cssselector
     * @param string $cssselector
     */
    public function i_scroll_to_element_with_css(string $cssselector): void {
        $session = $this->getSession();
        $driver = $session->getDriver();

        // More robust retry logic with exponential backoff.
        $maxattempts = 5;
        $attempt = 0;
        $waittime = 1;

        do {
            try {
                $element = $session->getPage()->find('css', $cssselector);
                if ($element !== null && $element->isVisible()) {
                    $driver->executeScript(
                        "arguments[0].scrollIntoView({behavior: 'auto', block: 'center', inline: 'center'});",
                        [$element]
                    );
                    return;
                }
            } catch (Exception $e) {
                // Log the error but continue retrying.
                debugging("Attempt $attempt failed: " . $e->getMessage());
            }

            sleep($waittime);
            $waittime *= 2; // Exponential backoff.
            $attempt++;
        } while ($attempt < $maxattempts);

        throw new Exception("Element with CSS selector '$cssselector' not found or not visible after $maxattempts attempts");
    }
}
