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
 * Test case for translate_page class.
 *
 * @package    local_deepler
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_deepler\output\translate_page
 */

namespace local_deepler\output;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/filter/multilang2/filter.php');

use advanced_testcase;
use classes\local\services\lang_helper;
use filter_multilang2\filter;
use moodle_url;
use renderer_base;
use stdClass;

/**
 * Test case for translate_page class.
 *
 * @coversDefaultClass \local_deepler\output\translate_page
 */
final class translate_page_test extends advanced_testcase {
    /**
     * @var translate_page
     */
    private $translatepage;

    /**
     * @var stdClass
     */
    private $course;

    /**
     * @var array
     */
    private $coursedata;

    /**
     * @var \filter_multilang2|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mlangfilter;

    /**
     * @var lang_helper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $langhelper;

    /**
     * Set up the test case.
     *
     * This method creates a test course, mock objects for dependencies,
     * and initializes the translate_page object for testing.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->course = $this->getDataGenerator()->create_course();
        $this->coursedata = [[
                'section' => [
                        (object) [
                                'id' => 1,
                                'hierarchy' => 'level0',
                                'tid' => '6737',
                                'format' => 0,
                                'table' => 'course',
                                'field' => 'fullname',
                                'cmid' => 0,
                                'text' => 'Assignment 1',
                                'link' => new moodle_url('/course/view.php', ['id' => 1]),
                                'displaytext' => 'Assignment 1',
                                'iconurl' => 'http://example.com/icon1.png',
                                'pluginname' => 'assignment',
                                'tneeded' => false,
                                'section' => null,
                                'purpose' => null,
                                'translatedfieldname' => 'Full name',
                        ],
                ],
                'activities' => [
                        (object) [
                                'id' => 2,
                                'hierarchy' => 'level1',
                                'tid' => '565',
                                'format' => 1,
                                'table' => 'forum',
                                'field' => 'name',
                                'cmid' => 1,
                                'text' => 'Forum 1',
                                'link' => new moodle_url('/course/view.php', ['id' => 1]),
                                'displaytext' => 'Forum 1',
                                'iconurl' => 'http://example.com/icon1.png',
                                'pluginname' => 'forum',
                                'tneeded' => false,
                                'section' => 76,
                                'purpose' => 'collaboration',
                                'translatedfieldname' => 'Name',
                        ],
                ],
        ]];
        $this->mlangfilter = $this->createMock(\filter_multilang2::class);
        $this->langhelper = $this->getMockBuilder(lang_helper::class)->enableOriginalConstructor()->getMock();
        $this->langhelper->method('prepareoptionlangs')->willReturn(['en' => 'English', 'fr' => 'French']);
        $this->langhelper->currentlang = 'en';
        $this->langhelper->targetlang = 'fr';

        $this->translatepage = new translate_page($this->course, $this->coursedata, $this->mlangfilter, $this->langhelper,
                'vtest');
    }

    /**
     * Test the export_for_template method.
     *
     * This test verifies that the export_for_template method returns the expected data structure
     * and values, including course information, language options, and form rendering.
     *
     * @covers ::export_for_template
     * @return void
     */
    public function test_export_for_template(): void {
        global $PAGE;
        $PAGE->set_url(new moodle_url('/local/deepler/translate.php'));
        $renderer = $this->getMockBuilder(renderer_base::class)->disableOriginalConstructor()->getMock();

        $result = $this->translatepage->export_for_template($renderer);

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals($this->course, $result->course);
        $this->assertEquals(['en' => 'English', 'fr' => 'French'], $result->targetlangs);
        $this->assertEquals(['en' => 'English', 'fr' => 'French'], $result->sourcelangs);
        $this->assertEquals('en', $result->current_lang);
        $this->assertEquals('fr', $result->target_lang);
        $this->assertEquals($this->mlangfilter, $result->mlangfilter);
        $this->assertEquals($this->coursedata, $result->coursedata);

        // Test form rendering.
        $this->assertStringContainsString('col-md-12', $result->mform);
    }

    /**
     * Test the constructor of the translate_page class.
     *
     * This test ensures that the constructor correctly initializes a translate_page object
     * with the provided course, course data, multilang filter, and language helper.
     *
     * @covers ::__construct
     * @return void
     */
    public function test_constructor(): void {
        $this->assertInstanceOf(translate_page::class, $this->translatepage);
    }

    /**
     * Test the config settings for latex and pre escaping.
     *
     * This test verifies that the export_for_template method correctly applies
     * the configuration settings for latex escaping and pre-escaping.
     *
     * @covers ::export_for_template
     * @return void
     */
    public function test_config_settings(): void {
        global $PAGE;
        $PAGE->set_url(new \moodle_url('/local/deepler/translate.php'));
        set_config('latexescapeadmin', 1, 'local_deepler');
        set_config('preescapeadmin', 0, 'local_deepler');
        $renderer = $this->getMockBuilder(renderer_base::class)->disableOriginalConstructor()->getMock();
        $result = $this->translatepage->export_for_template($renderer);

        $this->assertEquals('checked', $result->escapelatexbydefault);
        $this->assertEquals('', $result->escapeprebydefault);
    }
}
