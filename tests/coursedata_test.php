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

class local_deepler_course_data_testcase extends advanced_testcase {
    protected $course;
    protected $coursedata;

    public function setUp(): void {
        $this->resetAfterTest();

        // Create a test course
        $this->course = $this->getDataGenerator()->create_course([
                'fullname' => 'Test Course',
                'shortname' => 'TC101',
                'summary' => 'This is a test course summary'
        ]);

        // Create a test section
        $this->getDataGenerator()->create_course_section([
                'course' => $this->course->id,
                'section' => 1,
                'name' => 'Test Section',
                'summary' => 'Test section summary'
        ]);

        // Create a test activity (page)
        $this->getDataGenerator()->create_module('page', [
                'course' => $this->course->id,
                'name' => 'Test Page',
                'content' => 'Test page content'
        ]);

        $this->coursedata =
                new \local_deepler\local\data\course_data($this->course, 'en', \context_course::instance($this->course->id)->id);
    }

    public function test_constructor() {
        $this->assertInstanceOf(\local_deepler\local\data\course_data::class, $this->coursedata);
    }

    public function test_getdata() {
        $this->resetAfterTest();

        // Capture debugging messages
        $debuggings = [];
        set_debugging(DEBUG_DEVELOPER);

        $data = $this->coursedata->getdata();

        $debuggingmessages = $this->getDebuggingMessages();
        $this->assertEmpty($debuggingmessages, 'Unexpected debugging messages: ' . print_r($debuggingmessages, true));

        // Assert that data is returned and has the expected structure
        $this->assertIsArray($data);
        $this->assertArrayHasKey('0', $data);
        $this->assertArrayHasKey('section', $data['0']);
        $this->assertArrayHasKey('activities', $data['0']);

        // Additional assertions to check the content of $data
        $this->assertNotEmpty($data['0']['section']);
        $this->assertNotEmpty($data['0']['activities']);

        // Check for specific fields in the activities
        $foundPageContent = false;
        foreach ($data as $section) {
            foreach ($section['activities'] as $activity) {
                if ($activity->table === 'page' && $activity->field === 'content') {
                    $foundPageContent = true;
                    $this->assertNotEmpty($activity->translatedfieldname);
                    break 2;
                }
            }
        }
        $this->assertTrue($foundPageContent, 'Page content field not found in the data');
    }

    public function test_getcoursedata() {
        $method = new ReflectionMethod(\local_deepler\local\data\course_data::class, 'getcoursedata');
        $method->setAccessible(true);

        $coursedata = $method->invoke($this->coursedata);

        $this->assertIsArray($coursedata);
        $this->assertCount(3, $coursedata); // fullname, shortname, summary

        $this->assertEquals('Test Course', $coursedata[0]->text);
        $this->assertEquals('TC101', $coursedata[1]->text);
        $this->assertEquals('This is a test course summary', $coursedata[2]->text);
    }

    /*public function test_getsectiondata() {
        global $DB;

        $method = new \ReflectionMethod(\local_deepler\local\data\course_data::class, 'getsectiondata');
        $method->setAccessible(true);

        $sectiondata = $method->invoke($this->coursedata) ?? [];

        $this->assertIsArray($sectiondata, 'Section data should be an array');

        // Debug output
        //debugging('Number of sections: ' . count($sectiondata));
        //debugging('Section data: ' . print_r($sectiondata, true));

        // Check if there are any sections in the database
        $coursesections = $DB->get_records('course_sections', ['course' => $this->course->id]);
        //debugging('Number of course sections in DB: ' . count($coursesections));
        //debugging('Course sections in DB: ' . print_r($coursesections, true));

        // Check if there are any sections
        if (empty($sectiondata) && empty($coursesections)) {
            // Instead of skipping, let's fail with a meaningful message
            $this->fail('No sections found in the course. Check course setup and getsectiondata method implementation.');
        }

        // Assert that there's at least one section (the default section)
        $this->assertGreaterThanOrEqual(1, count($sectiondata), 'There should be at least one section');

        $foundTestSection = false;
        foreach ($sectiondata as $section) {
            //debugging('Section: ' . print_r($section, true));
            if (isset($section->text) && ($section->text === 'Test Section' || $section->text === 'Test section summary')) {
                $foundTestSection = true;
                break;
            }
        }

        // If we didn't find the test section, output all section names for debugging
        if (!$foundTestSection) {
            $sectionNames = array_map(function($section) {
                return $section->text ?? 'Unnamed Section';
            }, $sectiondata);
            //debugging('All section names: ' . implode(', ', $sectionNames));
        }

        $this->assertTrue($foundTestSection, 'Test section data not found');
    }*/

    public function test_getactivitydata() {
        $method = new ReflectionMethod(\local_deepler\local\data\course_data::class, 'getactivitydata');
        $method->setAccessible(true);

        $activitydata = $method->invoke($this->coursedata);

        $this->assertIsArray($activitydata);
        $this->assertGreaterThanOrEqual(1, count($activitydata)); // At least 1 activity

        $foundTestPage = false;
        foreach ($activitydata as $activity) {
            if ($activity->text === 'Test Page' || $activity->text === 'Test page content') {
                $foundTestPage = true;
                break;
            }
        }
        $this->assertTrue($foundTestPage, 'Test page data not found');
    }

    public function test_build_data() {
        global $DB;

        // Create a test course
        $course = $this->getDataGenerator()->create_course();

        // Create a test page activity
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course->id));

        // Get the course module
        $cm = get_coursemodule_from_instance('page', $page->id);

        $method = new \ReflectionMethod(\local_deepler\local\data\course_data::class, 'build_data');
        $method->setAccessible(true);

        $activity = new \stdClass();
        $activity->modname = 'page';
        $activity->id = $cm->id;  // This is the course module ID (cmid)
        $activity->section = $cm->section;
        $activity->content = 'Test content';  // Add this if needed for file URL generation

        $coursedata = new \local_deepler\local\data\course_data($course, 'en', \context_course::instance($course->id)->id);

        $data = $method->invoke($coursedata, $page->id, 'Test text', 1, 'content', $activity);

        $this->assertInstanceOf(\stdClass::class, $data);
        $this->assertEquals('Test text', $data->text);
        $this->assertEquals('page', $data->table);
        $this->assertEquals('content', $data->field);
        $this->assertEquals($cm->id, $data->id);
    }

    public function test_store_status_db() {
        global $DB;

        $method = new ReflectionMethod(\local_deepler\local\data\course_data::class, 'store_status_db');
        $method->setAccessible(true);

        $result = $method->invoke($this->coursedata, 1, 'course', 'fullname');

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertObjectHasProperty('id', $result);
        $this->assertObjectHasProperty('s_lastmodified', $result);
        $this->assertObjectHasProperty('t_lastmodified', $result);

        // Check if the record was actually inserted into the database
        $record = $DB->get_record('local_deepler', ['t_id' => 1, 't_table' => 'course', 't_field' => 'fullname']);
        $this->assertNotFalse($record);
    }

    public function test_link_builder() {
        $method = new ReflectionMethod(\local_deepler\local\data\course_data::class, 'link_builder');
        $method->setAccessible(true);

        $courseLink = $method->invoke($this->coursedata, $this->course->id, 'course', null);
        $this->assertStringContainsString('/course/edit.php?id=' . $this->course->id, $courseLink);

        $sectionLink = $method->invoke($this->coursedata, 1, 'course_sections', null);
        $this->assertStringContainsString('/course/editsection.php?id=1', $sectionLink);

        $activityLink = $method->invoke($this->coursedata, 1, 'page', 1);
        $this->assertNull($activityLink);
    }
}