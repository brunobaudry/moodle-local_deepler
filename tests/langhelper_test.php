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

namespace local_deepler;
defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_deepler\data\lang_helper;

require_once(__DIR__ . '/../classes/vendor/autoload.php');

/**
 * Lang helper Test.
 *
 * @covers \lang_helper
 */
final class langhelper_test extends advanced_testcase {
    /**
     * The object to test.
     *
     * @var lang_helper
     */
    private $langhelper;
    /**
     * The API.
     *
     * @var \DeepL\Translator
     */
    protected $translator;

    /**
     * Set up.
     *
     * @return void
     * @throws \DeepL\DeepLException
     * @throws \dml_exception
     */
    protected function setUp(): void {
        parent::setUp();
        $this->langhelper = new lang_helper();
        $this->langhelper->init('abcd');
        $this->resetAfterTest(true);
    }

    /**
     * Tests the values returned as object ready to be transformed .
     *
     * @covers ::prepareoptionlangs
     * @return void
     */
    public function test_prepareoptionlangs(): void {
        $options = $this->langhelper->prepareoptionlangs(true, true);

        $this->assertIsArray($options);
        $this->assertNotEmpty($options);

        foreach ($options as $option) {
            $this->assertArrayHasKey('code', $option);
            $this->assertArrayHasKey('lang', $option);
            $this->assertArrayHasKey('selected', $option);
            $this->assertArrayHasKey('disabled', $option);
        }
    }

    /**
     * Tests the values returned as object ready to be transformed as HTM dropdown option list.
     * @covers ::preparehtmlotions
     *
     * @return void
     */
    public function test_preparehtmloptions(): void {
        $htmloptions = $this->langhelper->preparehtmlotions(true, true);
        $this->assertIsString($htmloptions);
        $this->assertStringContainsString('<option', $htmloptions);
    }

}
