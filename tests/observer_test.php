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
 * Test cases for observer.
 *
 * @package    local_deepler
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/PHPUnit
 */

namespace local_deepler;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/local/deepler/classes/observer.php');

use advanced_testcase;
use core\event\course_module_updated;
use core\event\course_section_updated;
use core\event\course_updated;

/**
 *  Test cases for observer.
 */
final class observer_test extends advanced_testcase {
    /**
     * Set it up.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Test the update course event.
     *
     * @covers \local_deepler\local_deepler_observer::course_updated
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_course_updated(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $record = new \stdClass();
        $record->t_id = $course->id;
        $record->t_table = 'course';
        $record->s_lastmodified = time() - 3600; // 1 hour ago
        $recordid = $DB->insert_record('local_deepler', $record);

        $event = course_updated::create(['objectid' => $course->id, 'context' => \context_course::instance($course->id)]);
        observer::course_updated($event);

        $updatedrecord = $DB->get_record('local_deepler', ['id' => $recordid]);
        $this->assertGreaterThan($record->s_lastmodified, $updatedrecord->s_lastmodified);
    }

    /**
     * Test the update section event.
     *
     * @covers \local_deepler\local_deepler_observer::course_section_updated
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_course_section_updated(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $section = $this->getDataGenerator()->create_course_section(['course' => $course, 'section' => 1]);
        $record = new \stdClass();
        $record->t_id = $section->id;
        $record->t_table = 'course_sections';
        $record->s_lastmodified = time() - 3600; // 1 hour ago.
        $recordid = $DB->insert_record('local_deepler', $record);

        $event = course_section_updated::create([
                        'objectid' => $section->id,
                        'context' => \context_course::instance($course->id),
                        'other' => ['sectionnum' => $section->sectionnum ?? 1],
                ]
        );
        observer::course_section_updated($event);

        $updatedrecord = $DB->get_record('local_deepler', ['id' => $recordid]);
        $this->assertGreaterThan($record->s_lastmodified, $updatedrecord->s_lastmodified);
    }

    /**
     * Tests the module event.
     *
     * @covers \local_deepler\local_deepler_observer::course_module_updated
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_course_module_updated(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $record = new \stdClass();
        $record->t_id = $page->id;
        $record->t_table = 'page';
        $record->s_lastmodified = time() - 3600; // 1 hour ago
        $recordid = $DB->insert_record('local_deepler', $record);

        $cm = get_coursemodule_from_instance('page', $page->id);
        $event = course_module_updated::create([
                'objectid' => $cm->id,
                'context' => \context_module::instance($cm->id),
                'other' => ['modulename' => 'page',
                        'instanceid' => $page->id, 'name' => 'page',
                ],
        ]);
        observer::course_module_updated($event);

        $updatedrecord = $DB->get_record('local_deepler', ['id' => $recordid]);
        $this->assertGreaterThan($record->s_lastmodified, $updatedrecord->s_lastmodified);
    }
}
