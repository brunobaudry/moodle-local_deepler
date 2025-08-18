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
use DeepL\Usage;
use stdClass;
use DeepL\DeepLClient;

/**
 * Lang helper Test.
 *
 * @covers \local_deepler\local\services\lang_helper
 */
final class langhelper_test extends advanced_testcase {
    /** @var lang_helper */
    private $langhelper;

    /** @var stdClass */
    private $user;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DeepLClient */
    private $mocktranslator;

    /**
     * Set up.
     *
     * @return void
     * @throws \DeepL\DeepLException
     * @throws \dml_exception
     */
    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest();

        $this->user = new stdClass();
        $this->user->id = 1;
        $this->user->username = 'testuser';
        $this->user->email = 'testuser@example.com';
        $this->user->department = 'testdepartment';

        $this->mocktranslator = $this->createMock(DeepLClient::class);

        $mockusage = $this->createMock(Usage::class);
        $mockusage->method('anyLimitReached')->willReturn(false);

        $this->mocktranslator->method('getUsage')->willReturn($mockusage);
        $this->mocktranslator->method('getSourceLanguages')->willReturn([
                (object) ['code' => 'EN', 'name' => 'English'],
                (object) ['code' => 'FR', 'name' => 'French'],
        ]);
        $this->mocktranslator->method('getTargetLanguages')->willReturn([
                (object) ['code' => 'DE', 'name' => 'German'],
                (object) ['code' => 'ES', 'name' => 'Spanish'],
        ]);

        $this->langhelper = new lang_helper(
                $this->mocktranslator,
                'mockapikey',
                ['en' => 'English', 'fr' => 'French', 'de' => 'German', 'es' => 'Spanish'],
                'en',
                'de'
        );
    }

    /**
     * Test helper init.
     *
     * @return void
     * @covers \local_deepler\local\services\lang_helper::initdeepl
     * @throws \DeepL\DeepLException
     * @throws \dml_exception
     */
    public function test_initdeepl_returns_true(): void {
        $result = $this->langhelper->initdeepl($this->user);
        $this->assertTrue($result);
    }

    /**
     * Tests the values returned as object ready to be transformed .
     *
     * @covers \local_deepler\local\services\lang_helper::prepareoptionlangs
     * @return void
     */
    public function test_prepareoptionlangs_returns_valid_structure(): void {
        $this->langhelper->initdeepl($this->user);
        $sources = $this->langhelper->preparesourcesoptionlangs();
        $targets = $this->langhelper->preparetargetsoptionlangs();

        $this->assertIsArray($sources);
        $this->assertIsArray($targets);

        foreach ($sources as $option) {
            $this->assertArrayHasKey('code', $option);
            $this->assertArrayHasKey('lang', $option);
            $this->assertArrayHasKey('selected', $option);
            $this->assertArrayHasKey('disabled', $option);
        }

        foreach ($targets as $option) {
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
    /*    public function test_preparehtmloptions_returns_valid_html(): void {
            $this->langhelper->initdeepl($this->user);
            $htmltargets = $this->langhelper->preparehtmltagets();
            $htmlsources = $this->langhelper->preparehtmlsources();

            $this->assertIsString($htmltargets);
            $this->assertIsString($htmlsources);
            $this->assertStringContainsString('<option', $htmltargets);
            $this->assertStringContainsString('<option', $htmlsources);
        }*/
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
            $this->langhelper->initdeepl($this->user);
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
