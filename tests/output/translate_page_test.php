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
use filter_multilang2;
use filter_multilang2\filter;
use local_deepler\local\data\course;
use local_deepler\local\services\lang_helper;
use moodle_url;
use PHPUnit\Framework\MockObject\MockObject;
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
     * @var course
     */
    private course $course;

    /**
     * @var \filter_multilang2|\PHPUnit\Framework\MockObject\MockObject
     */
    private filter_multilang2|MockObject $mlangfilter;

    /**
     * Set up the test case.
     *
     * This method creates a test course, mock objects for dependencies,
     * and initializes the translate_page object for testing.
     *
     * @return void
     */
    protected function setUp(): void {
        global $CFG;
        parent::setUp();
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $this->course = new course($course);
        if (version_compare($CFG->version, '2024042200', '>')) {
            // Moodle 4.5+ logic.
            $this->mlangfilter = $this->createMock(filter_multilang2::class);
        } else {
            // Pre-4.5 workaround.
            $mockbuilder = $this->getMockBuilder(filter_multilang2::class);
            $mockbuilder->disableOriginalConstructor();
            $this->mlangfilter = $mockbuilder->getMock();
        }
        $langhelper = $this->createMock(lang_helper::class);
        $langhelper->initdeepl();
        $langhelper->method('preparetargetsoptionlangs')->willReturn(['en' => 'English', 'fr' => 'French']);
        $langhelper->method('preparesourcesoptionlangs')->willReturn(['en' => 'English', 'fr' => 'French']);
        $langhelper->method('preparehtmltagets')->willReturn(
                '<option value="en">en</option><option value="fr">en</option>');
        $langhelper->currentlang = 'en';
        $langhelper->targetlang = 'fr';

        $this->translatepage = new translate_page($this->course, $this->mlangfilter, $langhelper, 'v1.3.5');
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
        $this->resetAfterTest(true);
        $PAGE->set_url(new moodle_url('/local/deepler/translate.php'));
        $renderer = $this->createMock(renderer_base::class);

        $result = $this->translatepage->export_for_template($renderer);

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(['en' => 'English', 'fr' => 'French'], $result->targetlangs);
        $this->assertEquals(['en' => 'English', 'fr' => 'French'], $result->sourcelangs);
        $this->assertEquals('en', $result->current_lang);
        $this->assertEquals('fr', $result->target_lang);
        $this->assertEquals($this->mlangfilter, $result->mlangfilter);

        // Test form rendering.
        $this->assertStringContainsString('<form', $result->mform);
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
