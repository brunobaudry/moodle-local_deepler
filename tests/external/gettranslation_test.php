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

namespace local_deepler\external;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/local/deepler/tests/external/base_external.php');

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use DeepL\AppInfo;

/**
 * PHPUnit tests for get_translation external service.
 *
 * @package    local_deepler
 * @category   test
 * @copyright  2025 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class gettranslation_test extends base_external {
    /**
     * Test execute_parameters method.
     *
     * @covers \local_deepler\external\get_translation::execute_parameters
     * @covers \local_deepler\external\get_rephrase::execute_parameters
     * @return void
     */
    public function test_execute_parameters(): void {
        if ($this->is_below_four_one()) {
            return;
        }

        $params = get_translation::execute_parameters();
        $paramsreph = get_rephrase::execute_parameters();
        $this->assertInstanceOf(external_function_parameters::class, $params);
        $this->assertInstanceOf(external_function_parameters::class, $paramsreph);
    }
    /**
     * Test execute_returns method.
     *
     * @covers \local_deepler\external\get_translation::execute_returns
     * @return void
     */
    public function test_executereturns(): void {
        if ($this->is_below_four_one()) {
            return;
        }
        $returns = get_translation::execute_returns();
        $returnsrephraz = get_rephrase::execute_returns();
        $this->assertInstanceOf(external_multiple_structure::class, $returns);
        $this->assertInstanceOf(external_multiple_structure::class, $returnsrephraz);
    }

    /**
     * Test setdeeplapikey method.
     *
     * @return void
     * @covers \local_deepler\external\get_translation::setdeeplapikey
     * @throws \dml_exception
     */
    public function test_setdeeplapikey(): void {
        if ($this->is_below_four_one()) {
            return;
        }
        // Set up the environment.
        $this->resetAfterTest(true);

        // Set the environment variable.
        putenv('DEEPL_APIKEY=testapikey');

        // Call the method.
        $apikey = get_translation::setdeeplapikey();

        // Assert the API key is returned correctly.
        $this->assertEquals('testapikey', $apikey);
    }

    /**
     * Test setdeeplappinfo method.
     *
     * @return void
     * @covers \local_deepler\external\get_translation::setdeeplappinfo
     */
    public function test_setdeeplappinfo(): void {
        if ($this->is_below_four_one()) {
            return;
        }
        // Set up the environment.
        $this->resetAfterTest(true);
        // Call the method.
        $appinfo = get_translation::setdeeplappinfo('1.0');
        $appinforephraz = get_rephrase::setdeeplappinfo('1.0');

        // Assert the AppInfo object is created correctly.
        $this->assertInstanceOf(AppInfo::class, $appinfo);
        $this->assertInstanceOf(AppInfo::class, $appinforephraz);
        $this->assertEquals('Moodle-Deepler', $appinfo->appName);
        $this->assertEquals('Moodle-Deepler', $appinforephraz->appName);
        $this->assertEquals('1.0', $appinfo->appVersion);
        $this->assertEquals('1.0', $appinforephraz->appVersion);
    }
}
