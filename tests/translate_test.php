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

use advanced_testcase;
use context_course;
use filter_multilang2;
use local_deepler\local\data\course_data;
use local_deepler\local\data\lang_helper;
use local_deepler\output\translate_page;

/**
 * Translate Test
 *
 */
final class translate_test extends advanced_testcase {

    /**
     * Helper to trace
     *
     * @param mixed $var
     * @param string $info
     * @return void
     */
    private function trace_to_cli(mixed $var, string $info): void {
        echo "\n" . $info . "\n";
        var_dump($var);
        ob_flush();
    }

    /**
     * Testing that all settings are loaded
     *
     * @covers ::get_config
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_plugin_config(): void {
        global $CFG;
        $this->assertNotNull(get_config('local_deepler', 'apikey'));
        $this->assertFalse(get_config('local_deepler', 'apikey') === '');
        $this->assertMatchesRegularExpression('/^0|1$/', get_config('local_deepler', 'deeplpro'));
        $this->assertNotEquals('', current_language());
        $this->assertTrue(strlen(current_language()) > 0);
    }

    /**
     * Checking the filter_multilang2
     *
     * @covers \filter_multilang2
     * @return void
     */
    public function test_mlang_filter(): void {
        global $CFG;
        $this->assertFileExists($CFG->dirroot . '/filter/multilang2/filter.php');
        require_once($CFG->dirroot . '/filter/multilang2/filter.php');
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $mlangfilter = new filter_multilang2($context, []);
        $this->assertNotNull($mlangfilter);
        $this->assertIsString($mlangfilter->filter($course->fullname));
        $this->assertTrue($mlangfilter->filter($course->fullname) > 0);
    }

    /**
     * Verifying test
     *
     * @covers \local_deepler\data\course_data
     * @return void
     */
    public function test_course_data(): void {
        global $CFG;
        $this->assertFileExists($CFG->dirroot . '/local/deepler/classes/output/translate_page.php');
        $this->assertFileExists($CFG->dirroot . '/local/deepler/classes/local/data/course_data.php');
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $coursedata = new course_data($course, $CFG->lang, $context->id);
        $this->assertNotNull($coursedata);
        $getdata = $coursedata->getdata();
        $this->assertIsArray($getdata);
        $this->assertIsArray($getdata['0']);
        foreach ($getdata as $v) {
            $this->assertIsArray($v);
            $this->assertArrayHasKey('section', $v);
            $this->assertArrayHasKey('activities', $v);
        }
        $langhelper = new lang_helper();
        $langhelper->init('abcd');
        $renderable = new translate_page($course, $coursedata->getdata(),
                new filter_multilang2($context, []), $langhelper);
        $this->assertNotNull($renderable);
    }

    /**
     * Set it up
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }
}
