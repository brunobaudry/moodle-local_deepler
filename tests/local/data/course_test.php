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

namespace local_deepler\local\data;

use advanced_testcase;
use course_modinfo;
use moodle_url;

/**
 * Unit tests for the course class.
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class course_test extends advanced_testcase {

    /**
     * @var \stdClass
     */
    protected $course;

    /**
     * @var \local_deepler\local\data\course
     */
    protected $coursewrapper;

    /**
     * Setup function for the tests.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_course_section(['course' => $this->course, 'section' => 1],
                ['_name' => 'Test Section']);
        $this->getDataGenerator()->create_module('quiz',
                ['course' => $this->course->id,
                        'name' => 'Test Quiz',
                        'description' => 'Test intro',
                ]);
        $this->coursewrapper = new course($this->course);
    }

    /**
     * Test the constructor.
     *
     * @return void
     * @covers \local_deepler\local\data\course::__construct
     */
    public function test_constructor(): void {
        $this->assertInstanceOf(course::class, $this->coursewrapper);
    }
    /**
     * Test the constructor and basic getters.
     *
     * @covers \local_deepler\local\data\course::__construct
     * @covers \local_deepler\local\data\course::getinfo
     * @covers \local_deepler\local\data\course::getlink
     * @return void
     */
    public function test_constructor_and_getters(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $courseobj = new course($course);

        $this->assertInstanceOf(course::class, $courseobj);
        $this->assertInstanceOf(course_modinfo::class, $courseobj->getinfo());
        $this->assertEquals($course->id, $courseobj->getinfo()->get_course_id());
        $this->assertEquals(new moodle_url("/course/edit.php", ['id' => $course->id]), $courseobj->getlink());
    }

    /**
     * Test the getfields method.
     *
     * @covers \local_deepler\local\data\course::getfields
     * @return void
     */
    public function test_getfields(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course(['fullname' => 'Test Course', 'shortname' => 'TC',
                'summary' => 'Test summary']);
        $courseobj = new course($course);

        $fields = $courseobj->getfields();
        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);
        $this->assertCount(3, $fields);
        $this->assertContainsOnlyInstancesOf(field::class, $fields);
        $this->assertEquals('fullname', $fields[0]->get_tablefield());
        $this->assertEquals('shortname', $fields[1]->get_tablefield());
        $this->assertEquals('summary', $fields[2]->get_tablefield());
        $this->assertEquals('Test Course', $fields[0]->get_text());
        $this->assertEquals('TC', $fields[1]->get_text());
        $this->assertEquals('Test summary', $fields[2]->get_text());
        $this->assertEquals('course', $fields[0]->get_table());
        $this->assertEquals('course', $fields[1]->get_table());
        $this->assertEquals('course', $fields[2]->get_table());
    }

    /**
     * Test the getfields method.
     *
     * @return void
     * @covers \local_deepler\local\data\course::getfields
     */
    public function test_getfields2(): void {
        $fields = $this->coursewrapper->getfields();
        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);
        foreach ($fields as $field) {
            $this->assertInstanceOf(field::class, $field);
        }
    }

    /**
     * Test the getsections method.
     *
     * @covers \local_deepler\local\data\course::getsections
     * @return void
     */
    public function test_getsections(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $courseobj = new course($course);

        $sections = $courseobj->getsections();
        $this->assertIsArray($sections);
        $this->assertNotEmpty($sections);
    }

    /**
     * Test the getsections method another way.
     *
     * @return void
     * @covers \local_deepler\local\data\course::getsections
     */
    public function test_getsections2(): void {
        $sections = $this->coursewrapper->getsections();
        $this->assertIsArray($sections);
        foreach ($sections as $section) {
            $this->assertInstanceOf(section::class, $section);
        }
    }

    /**
     * Test the getinfo method.
     *
     * @return void
     * @covers \local_deepler\local\data\course::getinfo
     */
    public function test_getinfo(): void {
        $this->assertInstanceOf(course_modinfo::class, $this->coursewrapper->getinfo());
        $this->assertEquals($this->course->id, $this->coursewrapper->getinfo()->get_course()->id);
    }

    /**
     * Test the getlink method.
     *
     * @return void
     * @covers \local_deepler\local\data\course::getlink
     */
    public function test_getlink(): void {
        global $CFG;
        $expectedlink = new moodle_url($CFG->wwwroot . "/course/edit.php", ['id' => $this->course->id]);
        $this->assertEquals($expectedlink->out(), $this->coursewrapper->getlink());
    }
}
