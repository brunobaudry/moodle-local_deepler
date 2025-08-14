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
    /** @var bool */
    private static bool $loaded = false; // Flag to prevent reload.

    /**
     * Behat helper for css selector.
     *
     * @When I scroll to the element with css selector :cssselector
     * @param string $cssselector
     */
    public function i_scroll_to_element_with_css(string $cssselector): void {
        $session = $this->getSession();
        $driver = $session->getDriver();
        // Add retry logic.
        $timeout = time() + 10; // Ten second timeout.
        $element = null;

        while (time() < $timeout) {
            try {
                $element = $session->getPage()->find('css', $cssselector);
                if ($element !== null) {
                    break;
                }
            } catch (Exception $e) {
                // Wait and retry.
                sleep(1);
            }
        }
        if (null === $element) {
            throw new Exception("Element with CSS selector '$cssselector' not found after 10 seconds");
        }

        $driver->executeScript("document.querySelector('$cssselector').scrollIntoView(true);");
    }
}
