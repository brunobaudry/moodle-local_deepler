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
 * Test cases for coursedata.
 *
 * @package    local_deepler
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/PHPUnit
 */

namespace local_deepler;

use advanced_testcase;
use ReflectionMethod;
use stdClass;

/**
 * Test case for course data.
 */
final class coursedata_test extends advanced_testcase {
    /**
     * @var stdClass
     */
    protected $course;
    /**
     * @var local\data\course_data
     */
    protected $coursedata;

    /**
     * Setup.
     *
     * @return void
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        // Create a test course.
        $this->course = $this->getDataGenerator()->create_course([
                'fullname' => 'Test Course',
                'shortname' => 'TC101',
                'summary' => 'This is a test course summary',
        ]);

        // Create a test section.
        $this->getDataGenerator()->create_course_section([
                'course' => $this->course->id,
                'section' => 1,
                'name' => 'Test Section',
                'summary' => 'Test section summary',
        ]);

        // Create a test activity (page).
        $this->getDataGenerator()->create_module('page', [
                'course' => $this->course->id,
                'name' => 'Test Page',
                'content' => 'Test page content',
        ]);

        $this->coursedata =
                new \local_deepler\local\data\course_data($this->course, 'en', \context_course::instance($this->course->id)->id);
    }

    /**
     * Test constructor.
     * @covers ::_construct()
     *
     * @return void
     */
    public function test_constructor(): void {
        $this->assertInstanceOf(\local_deepler\local\data\course_data::class, $this->coursedata);
    }

    /**
     * Test getdata.
     *
     * @covers ::getdata
     * @return void
     */
    public function test_getdata(): void {
        $this->resetAfterTest();

        // Capture debugging messages.
        $debuggings = [];
        set_debugging(DEBUG_DEVELOPER);

        $data = $this->coursedata->getdata();

        // Assert that data is returned and has the expected structure.
        $this->assertIsArray($data);
        $this->assertArrayHasKey('0', $data);
        $this->assertArrayHasKey('section', $data['0']);
        $this->assertArrayHasKey('activities', $data['0']);

        // Additional assertions to check the content of $data.
        $this->assertNotEmpty($data['0']['section']);
        $this->assertNotEmpty($data['0']['activities']);

        // Check for specific fields in the activities.
        $foundapgecontent = false;
        foreach ($data as $section) {
            foreach ($section['activities'] as $activity) {
                if ($activity->table === 'page' && $activity->field === 'content') {
                    $foundapgecontent = true;
                    $this->assertNotEmpty($activity->translatedfieldname);
                    break 2;
                }
            }
        }
        $this->assertTrue($foundapgecontent, 'Page content field not found in the data');
    }

    /**
     * Test if gettting the course data is all good.
     *
     * @covers ::getcoursedata
     * @return void
     * @throws \ReflectionException
     */
    public function test_getcoursedata(): void {
        $method = new ReflectionMethod(\local_deepler\local\data\course_data::class, 'getcoursedata');
        $method->setAccessible(true);

        $coursedata = $method->invoke($this->coursedata);

        $this->assertIsArray($coursedata);
        $this->assertCount(3, $coursedata); // Fullname, shortname, summary.

        $this->assertEquals('Test Course', $coursedata[0]->text);
        $this->assertEquals('TC101', $coursedata[1]->text);
        $this->assertEquals('This is a test course summary', $coursedata[2]->text);
    }

    /**
     * Test to get the activity data.
     *
     * @covers ::getactivitydata
     * @return void
     * @throws \ReflectionException
     */
    public function test_getactivitydata(): void {
        $method = new ReflectionMethod(\local_deepler\local\data\course_data::class, 'getactivitydata');
        $method->setAccessible(true);

        $activitydata = $method->invoke($this->coursedata);

        $this->assertIsArray($activitydata);
        $this->assertGreaterThanOrEqual(1, count($activitydata)); // At least 1 activity.

        $foundtestpage = false;
        foreach ($activitydata as $activity) {
            if ($activity->text === 'Test Page' || $activity->text === 'Test page content') {
                $foundtestpage = true;
                break;
            }
        }
        $this->assertTrue($foundtestpage, 'Test page data not found');
    }

    /**
     * Test to build data.
     *
     * @covers ::build_data
     * @return void
     * @throws \ReflectionException
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function test_build_data(): void {
        global $DB;

        // Create a test course.
        $course = $this->getDataGenerator()->create_course();

        // Create a test page activity.
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        // Get the course module.
        $cm = get_coursemodule_from_instance('page', $page->id);

        $method = new \ReflectionMethod(\local_deepler\local\data\course_data::class, 'build_data');
        $method->setAccessible(true);

        $activity = new \stdClass();
        $activity->modname = 'page';
        $activity->id = $cm->id;  // This is the course module ID (cmid).
        $activity->section = $cm->section;
        // Add this if needed for file URL generation.

        $coursedata = new \local_deepler\local\data\course_data($course, 'en', \context_course::instance($course->id)->id);

        $data = $method->invoke($coursedata, $page->id, 'Test content', 1, 'content', $activity);

        $this->assertInstanceOf(\stdClass::class, $data);
        $this->assertEquals('Test content', $data->text);
        $this->assertEquals('page', $data->table);
        $this->assertEquals('content', $data->field);
        $this->assertEquals($page->id, $data->id);
    }

    /**
     * Test the storing to Deepler's DB.
     *
     * @covers ::store_status_db
     * @return void
     * @throws \ReflectionException
     * @throws \dml_exception
     */

    public function test_store_status_db(): void {
        global $DB;

        $method = new ReflectionMethod(\local_deepler\local\data\course_data::class, 'store_status_db');
        $method->setAccessible(true);

        $result = $method->invoke($this->coursedata, 1, 'course', 'fullname');

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertTrue(property_exists($result, 'id'));
        $this->assertTrue(property_exists($result, 's_lastmodified'));
        $this->assertTrue(property_exists($result, 't_lastmodified'));

        // Check if the record was actually inserted into the database.
        $record = $DB->get_record('local_deepler', ['t_id' => 1, 't_table' => 'course', 't_field' => 'fullname']);
        $this->assertNotFalse($record);
    }

    /**
     * Test building an edit link.
     *
     * @covers ::link_builder
     * @return void
     * @throws \ReflectionException
     */
    public function test_link_builder(): void {
        $method = new ReflectionMethod(\local_deepler\local\data\course_data::class, 'link_builder');
        $method->setAccessible(true);

        $courselink = $method->invoke($this->coursedata, $this->course->id, 'course', null);
        $this->assertStringContainsString('/course/edit.php?id=' . $this->course->id, $courselink);

        $sectionlink = $method->invoke($this->coursedata, 1, 'course_sections', null);
        $this->assertStringContainsString('/course/editsection.php?id=1', $sectionlink);

        $activitylink = $method->invoke($this->coursedata, 1, 'page', 1);
        $this->assertNotNull($activitylink);

        $activitylink2 = $method->invoke($this->coursedata, 2, 'page', 0);
        $this->assertNull($activitylink2);
    }
}
