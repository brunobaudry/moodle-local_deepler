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
use stdClass;

/**
 * Unit tests for the field class.
 *
 * @package    local_deepler
 * @category   test
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_deepler\local\data\field
 */
final class field_test extends advanced_testcase {

    /**
     * Test the constructor and basic getters.
     *
     * @covers ::__construct
     * @covers ::get_id
     * @covers ::get_cmid
     * @covers ::get_tablefield
     * @covers ::get_table
     * @covers ::get_text
     * @covers ::get_format
     * @return void
     */
    public function test_constructor_and_getters(): void {
        $field = new field(1, 'Sample text', 1, 'shortname', 'course', 2);

        $this->assertEquals(1, $field->get_id());
        $this->assertEquals(2, $field->get_cmid());
        $this->assertEquals('shortname', $field->get_tablefield());
        $this->assertEquals('course', $field->get_table());
        $this->assertEquals('Sample text', $field->get_text());
        $this->assertEquals(1, $field->get_format());
    }

    /**
     * Test the getkey and getkeyid methods.
     *
     * @covers ::getkey
     * @covers ::getkeyid
     * @return void
     */
    public function test_key_generation(): void {
        $field = new field(1, 'Sample text', 1, 'shortname', 'course', 2);

        $this->assertEquals('course[1][shortname][2]', $field->getkey());
        $this->assertEquals('course-1-shortname-2', $field->getkeyid());
    }

    /**
     * Test the check_field_has_other_and_sourcetag method.
     *
     * @covers ::check_field_has_other_and_sourcetag
     * @return void
     */
    public function test_check_field_has_other_and_sourcetag(): void {
        $field1 = new field(1, '{mlang other}Other{mlang}{mlang en}English{mlang}', 1, 'shortname', 'course');
        $field2 = new field(2, 'Regular text', 1, 'name', 'course');

        $this->assertTrue($field1->check_field_has_other_and_sourcetag('en'));
        $this->assertFalse($field2->check_field_has_other_and_sourcetag('en'));
    }

    /**
     * Test the has_multilang method.
     *
     * @covers ::has_multilang
     * @return void
     */
    public function test_has_multilang(): void {
        $field1 = new field(1, '{mlang en}English{mlang}', 1, 'shortname', 'course');
        $field2 = new field(2, 'Regular text', 1, 'name', 'course');

        $this->assertTrue($field1->has_multilang());
        $this->assertFalse($field2->has_multilang());
    }

    /**
     * Test the filterdbtextfields static method.
     *
     * @covers ::filterdbtextfields
     * @return void
     */
    public function test_filterdbtextfields(): void {
        global $DB;

        // Mock the $DB->get_columns() method.
        $DB = $this->getMockBuilder(stdClass::class)->addMethods(['get_columns'])->getMock();

        $mockcolumns = [
                'id' => (object) [
                        'name' => 'id',
                        'meta_type' => 'R',
                        'max_length' => 10,
                ],
                'name' => (object) [
                        'name' => 'name',
                        'meta_type' => 'C',
                        'max_length' => 255,
                ],
                'description' => (object) [
                        'name' => 'description',
                        'meta_type' => 'X',
                        'max_length' => -1,
                ],
                'smalltext' => (object) [
                        'name' => 'smalltext',
                        'meta_type' => 'C',
                        'max_length' => 50,
                ],
        ];

        $DB->expects($this->once())->method('get_columns')->with('testtable')->willReturn($mockcolumns);

        $result = field::filterdbtextfields('testtable');

        $this->assertEquals(['name', 'description'], $result);
    }

    /**
     * Test the getfieldsfromcolumns static method.
     *
     * @covers ::getfieldsfromcolumns
     * @return void
     */
    public function test_getfieldsfromcolumns(): void {
        $info = (object) [
                'id' => 1,
                'name' => 'Test Name',
                'description' => 'Test Description',
                'descriptionformat' => 1,
                'empty' => '',
        ];

        $columns = ['name', 'description', 'empty'];
        $result = field::getfieldsfromcolumns($info, 'testtable', $columns);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(field::class, $result[0]);
        $this->assertInstanceOf(field::class, $result[1]);
        $this->assertEquals('name', $result[0]->get_tablefield());
        $this->assertEquals('description', $result[1]->get_tablefield());
    }
}
