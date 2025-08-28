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
    public function i_scroll_to_element_with_css(string $cssselector): void {
        $session = $this->getSession();
        $driver = $session->getDriver();

        // More robust retry logic with exponential backoff.
        $maxattempts = 5;
        $attempt = 0;
        $waittime = 1;

        /*do {
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
        } while ($attempt < $maxattempts);*/
        for ($attempt = 1; $attempt <= $maxattempts; $attempt++) {
            $element = $session->getPage()->find('css', $cssselector);

            if ($element !== null && $element->isVisible()) {
                // Re-fetch the element using the driver to avoid stale element issues
                $xpath = $element->getXpath();
                $webelements = $driver->find($xpath);

                if (!empty($webelements)) {
                    $webelement = $webelements[0];

                    // Try scrolling with full options
                    try {
                        //debugging("Attempting scroll with full options on attempt $attempt");
                        $driver->executeScript(
                                "arguments[0].scrollIntoView({behavior: 'auto', block: 'center', inline: 'center'});",
                                [$webelement]
                        );
                        //debugging("Scroll executed successfully.");
                        return;
                    } catch (Exception $e) {
                        //debugging("Scroll with full options failed: " . $e->getMessage());
                        // Try fallback scroll
                        try {
                            //debugging("Attempting fallback scroll on attempt $attempt");
                            $driver->executeScript("arguments[0].scrollIntoView(true);", [$webelement]);
                            //debugging("Fallback scroll executed successfully.");
                            return;
                        } catch (Exception $e2) {
                            debugging("Fallback scroll failed: " . $e2->getMessage());
                        }
                    }
                } else {
                    debugging("Element found but could not re-fetch via driver.");
                }
            } else {
                debugging("Element not found or not visible on attempt $attempt.");
            }

            sleep($waittime);
            $waittime *= 2;
        }

        throw new Exception("Failed to scroll to element with selector '$cssselector' after $maxattempts attempts.");
    }

}
