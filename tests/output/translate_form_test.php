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
 * Unit tests for translate_form class.
 *
 * @package    local_deepler
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_deepler\output;

use advanced_testcase;
use local_deepler\local\data\lang_helper;
use ReflectionClass;

/**
 * Unit tests for translate_form class.
 */
final class translate_form_test extends advanced_testcase {
    /** @var mixed */
    protected $course;
    /** @var lang_helper */
    protected $langhelper;
    /** @var \filter_multilang2 */
    protected $mlangfilter;

    /**
     * Set it up.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->course = $this->getDataGenerator()->create_course();
        $this->mlangfilter = $this->createMock(\filter_multilang2::class);
        $this->langhelper = $this->createMock(lang_helper::class);

        $this->langhelper->currentlang = 'en';
        $this->langhelper->targetlang = 'fr';
    }

    /**
     * Test the form definition.
     *
     * @covers \local_deepler\output\translate_form::definition
     * @return void
     * */
    public function test_definition(): void {
        global $CFG;

        // Set up custom data for the form.
        $customdata = [
                'course' => $this->course,
                'coursedata' => [],
                'langpack' => $this->langhelper,
                'mlangfilter' => $this->mlangfilter,
        ];

        // Create an instance of the form.
        $form = new translate_form(null, $customdata);

        // Use reflection to access the private _mform property.
        $reflection = new ReflectionClass($form);
        $property = $reflection->getProperty('_form');
        $property->setAccessible(true);
        $mform = $property->getValue($form);

        // Check if the form is defined correctly.
        $this->assertNotEmpty($mform);
        $this->assertInstanceOf('MoodleQuickForm', $mform);

        // Check if the form elements are added correctly.
        $elements = $mform->getAttributes();
        $this->assertNotEmpty($elements);
    }

    /**
     * Test the get_formrow method.
     *
     * @covers \local_deepler\output\translate_form::get_formrow
     * @return void
     */
    public function test_get_formrow(): void {
        global $CFG, $PAGE;
        $PAGE->set_url(new \moodle_url('/local/deepler/translate.php'));
        // Set up custom data for the form.
        $customdata = [
                'course' => $this->course,
                'coursedata' => [],
                'langpack' => $this->langhelper,
                'mlangfilter' => $this->mlangfilter,
        ];

        // Create an instance of the form.
        $form = new translate_form(null, $customdata);

        // Use reflection to access the private mform property.
        $reflection = new ReflectionClass($form);
        $property = $reflection->getProperty('_form');
        $property->setAccessible(true);
        /** @var \MoodleQuickForm $mform */
        $mform = $property->getValue($form);

        // Create a mock item.
        $item = (object) [
                'id' => 2,
                'hierarchy' => 'level1',
                'tid' => '565',
                'format' => 1,
                'table' => 'forum',
                'field' => 'name',
                'text' => 'Forum 1',
                'link' => new \moodle_url('/course/view.php', ['id' => 1]),
                'displaytext' => 'Forum 1',
                'iconurl' => 'http://example.com/icon1.png',
                'pluginname' => 'forum',
                'tneeded' => false,
                'section' => 76,
                'purpose' => 'collaboration',
                'translatedfieldname' => 'Name',
                'cmid' => 1,
        ];

        // Call the get_formrow method.
        $method = $reflection->getMethod('get_formrow');
        $method->setAccessible(true);
        $method->invokeArgs($form, [$mform, $item, 'test-css-class']);

        // Check if the form elements are added correctly.
        $elements = $mform->toHtml();
        $this->assertNotEmpty($elements);
    }

    /**
     * Test the check_field_has_other_and_sourcetag method.
     *
     * @covers \local_deepler\output\translate_form::check_field_has_other_and_sourcetag
     * @return void
     */
    public function test_check_field_has_other_and_sourcetag(): void {
        // Set up custom data for the form.
        $customdata = [
                'course' => $this->course,
                'coursedata' => [],
                'langpack' => $this->langhelper,
                'mlangfilter' => $this->mlangfilter,
        ];

        // Create an instance of the form.
        $form = new translate_form(null, $customdata);

        // Use reflection to access the private method.
        $reflection = new ReflectionClass($form);
        $method = $reflection->getMethod('check_field_has_other_and_sourcetag');
        $method->setAccessible(true);

        // Test cases.
        $this->assertTrue($method->invokeArgs($form, ['{mlang other}{mlang en}']));
        $this->assertFalse($method->invokeArgs($form, ['{mlang other}{mlang fr}']));
        $this->assertFalse($method->invokeArgs($form, ['{mlang en}']));
    }

    /**
     * Test the has_multilang method.
     *
     * @covers \local_deepler\output\translate_form::has_multilang
     * @return void
     */
    public function test_has_multilang(): void {
        // Set up custom data for the form.
        $customdata = [
                'course' => $this->course,
                'coursedata' => [],
                'langpack' => $this->langhelper,
                'mlangfilter' => $this->mlangfilter,
        ];

        // Create an instance of the form.
        $form = new translate_form(null, $customdata);

        // Use reflection to access the private method.
        $reflection = new ReflectionClass($form);
        $method = $reflection->getMethod('has_multilang');
        $method->setAccessible(true);

        // Test cases.
        $this->assertTrue($method->invokeArgs($form, ['{mlang}']));
        $this->assertFalse($method->invokeArgs($form, ['{mlang other}']));
        $this->assertFalse($method->invokeArgs($form, ['']));
    }

}
