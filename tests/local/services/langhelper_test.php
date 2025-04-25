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
 * Test cases
 *
 * @package    local_deepler
 * @copyright  2022 Kaleb Heitzman <kaleb@jamfire.io>
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/PHPUnit
 */

namespace local_deepler\local\services;
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../../classes/vendor/autoload.php');

use advanced_testcase;
use DeepL\AuthorizationException;
use DeepL\TooManyRequestsException;

/**
 * Lang helper Test.
 *
 * @covers \local_deepler\local\services\lang_helper
 */
final class langhelper_test extends advanced_testcase {
    /**
     * The object to test.
     *
     * @var lang_helper
     */
    private $langhelper;

    /**
     * Set up.
     *
     * @return void
     * @throws \DeepL\DeepLException
     * @throws \dml_exception
     */
    protected function setUp(): void {
        parent::setUp();
        $this->makeenv();
        $this->langhelper = new lang_helper();
        $this->langhelper->initdeepl();
    }

    /**
     * Tests the values returned as object ready to be transformed .
     *
     * @covers \local_deepler\local\services\lang_helper::prepareoptionlangs
     * @return void
     */
    public function test_prepareoptionlangs(): void {
        $optionscourse = $this->langhelper->preparesourcesoptionlangs();
        $optionstargets = $this->langhelper->preparetargetsoptionlangs();

        $this->assertIsArray($optionscourse);
        $this->assertIsArray($optionstargets);
        $this->assertNotEmpty($optionscourse);
        $this->assertNotEmpty($optionstargets);

        foreach ($optionscourse as $option) {
            $this->assertArrayHasKey('code', $option);
            $this->assertArrayHasKey('lang', $option);
            $this->assertArrayHasKey('selected', $option);
            $this->assertArrayHasKey('disabled', $option);
        }
        foreach ($optionstargets as $option) {
            $this->assertArrayHasKey('code', $option);
            $this->assertArrayHasKey('lang', $option);
            $this->assertArrayHasKey('selected', $option);
            $this->assertArrayHasKey('disabled', $option);
        }
    }

    /**
     * Tests the values returned as object ready to be transformed as HTM dropdown option list.
     *
     * @covers \local_deepler\local\services\lang_helper::preparehtmlotions
     *
     * @return void
     */
    public function test_preparehtmloptions(): void {
        $htmltargets = $this->langhelper->preparehtmltagets();
        $htmlsources = $this->langhelper->preparehtmlsources();
        $this->assertIsString($htmltargets);
        $this->assertIsString($htmlsources);
        $this->assertStringContainsString('<option', $htmltargets);
        $this->assertStringContainsString('<option', $htmlsources);
    }

    /**
     * Basic setting tests.
     *
     * @covers \local_deepler\local\services\lang_helper::initdeepl
     * @covers \local_deepler\local\services\lang_helper::isapikeynoset
     * @return void
     * @throws \DeepL\DeepLException
     * @throws \dml_exception
     */
    public function test_settings(): void {
        if ($this->langhelper->isapikeynoset()) {
            $this->makeenv();
        }
        try {
            $this->langhelper->initdeepl();
        } catch (AuthorizationException $e) {
            $this->assertEquals('Authorization failed: Invalid auth key.', $e->getMessage());
        } catch (TooManyRequestsException $e) {
            $this->assertEquals('Too many requests, DeepL servers are currently experiencing high load, ', $e->getMessage());
        }
    }

    /**
     * Helper for running test without.
     *
     * @return void
     */
    private function makeenv() {
        global $CFG;
        // Define the path to the .env file.
        $envfilepath = $CFG->dirroot . '/local/deepler/.env';

        // Check if the .env file exists.
        if (file_exists($envfilepath)) {
            // Read the .env file line by line.
            $lines = file($envfilepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Skip comments.
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }

                // Parse the environment variable.
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                // Set the environment variable.
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        } else {
            $this->assertNotEmpty(getenv('DEEPL_API_TOKEN'));
        }
    }
}
