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
 * Test cases for course
 *
 * @package    local_deepler
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/PHPUnit
 */

namespace local_deepler;

use advanced_testcase;
use context_course;

/**
 * Course test class
 */
final class course_test extends advanced_testcase {
    /**
     * Test course creation and context
     *
     * @covers \context_course
     * @return void
     * @throws \dml_exception
     */
    public function test_course(): void {
        global $CFG;
        global $PAGE;
        global $DB;

        $course1 = $this->getDataGenerator()->create_course();
        $coursedb = $DB->get_record('course', ['id' => $course1->id], '*', MUST_EXIST);
        $coursedbid = intval($coursedb->id);
        $PAGE->set_context(context_course::instance($coursedbid));
        $this->assertEquals($PAGE->context->id, context_course::instance($coursedbid)->id);
        $PAGE->set_context(context_course::instance($course1->id));
        $this->assertEquals($PAGE->context->id, context_course::instance($course1->id)->id);
    }

    /**
     * Check modinfo
     *
     * @covers \local_deepler\data\course_data
     * @return void
     * @throws \moodle_exception
     */
    public function test_modinfo(): void {
        $course = $this->getDataGenerator()->create_course();
        $modinfo = get_fast_modinfo($course);
        $this->assertNotNull($modinfo);
        $clone = $modinfo->get_course();
        $this->assertNotNull($clone);
        $this->assertEquals($course->id, $clone->id);
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
