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
use coding_exception;
use lang_string;

/**
 * Tests for multilanger class
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_deepler\local\data\multilanger
 */
final class multilanger_test extends advanced_testcase {

    /**
     * Test field string resolution for course table.
     *
     * @covers \local_deepler\local\data\multilanger::findfieldstring
     */
    public function test_course_table_fields(): void {
        $field = $this->create_mock_field('course', 'fullname');
        $this->assertEquals(get_string('fullname'), multilanger::findfieldstring($field));
    }

    /**
     * Create a mock field object with full constructor parameters.
     *
     * @param string $table
     * @param string $fieldname
     * @return field
     */
    private function create_mock_field(string $table, string $fieldname): field {
        return new class(0, '', 0, $fieldname, $table, 0) extends field {
            // No constructor needed - uses parent constructor directly.
        };
    }

    /**
     * Test course sections field resolution.
     *
     * @covers \local_deepler\local\data\multilanger::findfieldstring
     */
    public function test_course_sections_fields(): void {
        $field = $this->create_mock_field('course_sections', 'name');
        $this->assertEquals(get_string('sectionname'), multilanger::findfieldstring($field));

        $field = $this->create_mock_field('course_sections', 'summary');
        $this->assertEquals(get_string('description'), multilanger::findfieldstring($field));
    }

    /**
     * Test standard module intro/name fields.
     *
     * @covers \local_deepler\local\data\multilanger::findfieldstring
     */
    public function test_module_standard_fields(): void {
        // Test intro field.
        $field = $this->create_mock_field('forum', 'intro');
        $this->assertEquals(get_string('description'), multilanger::findfieldstring($field));

        // Test name field.
        $field = $this->create_mock_field('quiz', 'name');
        $this->assertEquals(get_string('name'), multilanger::findfieldstring($field));
    }

    /**
     * Test fallback to field name when no string found.
     *
     * @covers \local_deepler\local\data\multilanger::findfieldstring
     */
    public function test_fieldname_fallback(): void {
        $field = $this->create_mock_field('unknown_table', 'unknown_field');
        $this->assertEquals('unknown_field', multilanger::findfieldstring($field));
    }
}
