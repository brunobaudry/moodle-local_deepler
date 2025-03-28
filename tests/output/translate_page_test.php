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

namespace local_deepler\output;
defined('MOODLE_INTERNAL') || die();
global $CFG;

use advanced_testcase;
use filter_multilang2;
use local_deepler\local\data\course;
use local_deepler\local\services\lang_helper;
use ReflectionClass;
use renderer_base;

// Include the filter_multilang2 class manually.
require_once($CFG->dirroot . '/filter/multilang2/filter.php');

/**
 * Unit tests for the translate_page class.
 *
 * @package    local_deepler
 * @category   test
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class translate_page_test extends advanced_testcase {


    /**
     * Test the constructor of translate_page.
     *
     * @covers \local_deepler\output\translate_page::__construct
     * @return void
     */
    public function test_constructor(): void {
        if (!class_exists('\core_filters\text_filter')) {
            // Create an alias for pre-4.5 versions
            class_alias(filter_multilang2::class, core_filters\text_filter::class);
        }
        $coursedata = $this->createMock(course::class);
        $mlangfilter = $this->createMock(filter_multilang2::class);
        $languagepack = $this->createMock(lang_helper::class);
        $languagepack->currentlang = 'en';
        $languagepack->targetlang = 'fr';

        $version = '1.0';

        $translatepage = new translate_page($coursedata, $mlangfilter, $languagepack, $version);

        $this->assertInstanceOf(translate_page::class, $translatepage);
        $this->assertEquals('1.0', $this->getprivateproperty($translatepage, 'version'));
        $this->assertSame($coursedata, $this->getprivateproperty($translatepage, 'coursedata'));
        $this->assertSame($mlangfilter, $this->getprivateproperty($translatepage, 'mlangfilter'));
        $this->assertSame($languagepack, $this->getprivateproperty($translatepage, 'langpacks'));
    }

    /**
     * Test the export_for_template method.
     *
     * @covers \local_deepler\output\translate_page::export_for_template
     * @return void
     */
    public function test_export_for_template(): void {
        if (!class_exists('\core_filters\text_filter')) {
            // Create an alias for pre-4.5 versions
            class_alias(filter_multilang2::class, core_filters\text_filter::class);
        }
        $coursedata = $this->createMock(course::class);
        $mlangfilter = $this->createMock(filter_multilang2::class);
        $languagepack = $this->createMock(lang_helper::class);
        $languagepack->currentlang = 'en';
        $languagepack->targetlang = 'fr';

        $version = '1.0';

        $translatepage = new translate_page($coursedata, $mlangfilter, $languagepack, $version);

        $output = $this->createMock(renderer_base::class);
        $data = $translatepage->export_for_template($output);

        $this->assertIsObject($data);
        $this->assertTrue(property_exists($data, 'langstrings'));
        $this->assertTrue(property_exists($data, 'targethtmloptions'));
        $this->assertTrue(property_exists($data, 'targetlangs'));
        $this->assertTrue(property_exists($data, 'sourcelangs'));
        $this->assertTrue(property_exists($data, 'mform'));
        $this->assertTrue(property_exists($data, 'current_lang'));
        $this->assertTrue(property_exists($data, 'deeplsource'));
        $this->assertTrue(property_exists($data, 'target_lang'));
        $this->assertTrue(property_exists($data, 'notarget'));
        $this->assertTrue(property_exists($data, 'mlangfilter'));
        $this->assertTrue(property_exists($data, 'escapelatexbydefault'));
        $this->assertTrue(property_exists($data, 'escapeprebydefault'));
        $this->assertTrue(property_exists($data, 'version'));
    }

    /**
     * Helper method to access private properties.
     *
     * @param object $object
     * @param string $property
     * @return mixed
     */
    private function getprivateproperty($object, $property) {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        return $property->getValue($object);
    }
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
}
