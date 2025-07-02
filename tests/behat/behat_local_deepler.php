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

/**
 * Behat css finder helper code. Probably a duplicate but helped us understand extensions.
 */
class behat_local_deepler extends behat_base {

    /**
     * Behat helper for css selector.
     *
     * @When I scroll to the element with css selector :cssselector
     * @param string $cssselector
     */
    public function i_scroll_to_element_with_css($cssselector): void {
        $session = $this->getSession();
        $driver = $session->getDriver();
        $element = $session->getPage()->find('css', $cssselector);
        if (null === $element) {
            throw new \Exception('Element not found');
        }
        $driver->executeScript("document.querySelector('$cssselector').scrollIntoView(true);");
    }

    /**
     * @Then I dump the DEEPL_API_TOKEN
     */
    public function i_dump_the_deepl_api_token(): void {
        echo "DEEPL_API_TOKEN = " . getenv('DEEPL_API_TOKEN') . "\n";
    }

    /**
     * @BeforeSuite
     */
    #[BeforeSuite]
    public static function load_env() {
        require_once(__DIR__ . '/env_loader.php');
        env_loader::load(__DIR__ . '/../../.env');
        echo "Loaded .env: DEEPL_API_TOKEN = " . getenv('DEEPL_API_TOKEN') . "\n";
    }

    /**
     * Test
     *
     * @return void
     */
    #[BeforeScenario]
    public static function test() {
        echo 'DEEPLER SUITE';
    }

}
