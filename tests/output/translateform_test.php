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
 * Unit tests for translateform class.
 *
 * @package    local_deepler
 * @category   test
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_deepler\output\translateform
 */

namespace local_deepler\output;

use advanced_testcase;
use filter_multilang2;
use local_deepler\local\data\course;
use local_deepler\local\services\lang_helper;
use ReflectionClass;

final class translateform_test extends advanced_testcase {
    /** @var mixed */
    protected mixed $course;
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
        $this->mlangfilter = $this->createMock(filter_multilang2::class);
        $this->langhelper = $this->createMock(lang_helper::class);
        $this->langhelper->method('preparehtmlotions')
                ->willReturn('<option value="en">en</option><option value="fr">en</option>');
        $this->makeenv();
        $this->langhelper->currentlang = 'en';
        $this->langhelper->targetlang = 'fr';
        $this->langhelper->initdeepl();
    }

    /**
     * Helper for running test without network.
     *
     * @return void
     */
    private function makeenv() {
        global $CFG;
        // Define the path to the .env file.
        $envfilepath = $CFG->dirroot . '/local/deepler/.env';

        // Check if the .env file exists.
        if (file_exists($envfilepath)) {
            // Read the .env file line by line.
            $lines = file($envfilepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Skip comments.
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }

                // Parse the environment variable.
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                // Set the environment variable.
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        } else {
            $this->assertEquals('DEFAULT', getenv('DEEPL_API_TOKEN'));
        }
    }

    /**
     * Test the definition method.
     *
     * @covers ::definition
     * @returns void
     */
    public function test_definition(): void {
        $this->resetAfterTest(true);
        $coursedata = new course($this->course);

        // Custom data for the form.
        $customdata = [
                'coursedata' => $coursedata,
                'langpack' => $this->langhelper,
                'mlangfilter' => $this->mlangfilter,
        ];

        // Instantiate the form.
        $form = new translateform(null, $customdata);
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
}
