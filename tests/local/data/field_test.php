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
     * @covers \local_deepler\local\data\field::__construct
     * @covers \local_deepler\local\data\field::get_id
     * @covers \local_deepler\local\data\field::get_cmid
     * @covers \local_deepler\local\data\field::get_tablefield
     * @covers \local_deepler\local\data\field::get_table
     * @covers \local_deepler\local\data\field::get_text
     * @covers \local_deepler\local\data\field::get_format
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
     * @covers \local_deepler\local\data\field::getkey
     * @covers \local_deepler\local\data\field::getkeyid
     * @return void
     */
    public function test_key_generation(): void {
        $field = new field(1, 'Sample text', 1, 'shortname', 'course', 2);

        $this->assertEquals('course[1][shortname][2]', $field->getkey());
        $this->assertEquals('course-1-shortname-2', $field->getkeyid());
        $datafromkey = field::generatedatfromkey($field->getkey());
        $this->assertIsArray($datafromkey);
        $this->assertEquals('course', $datafromkey['table']);
        $this->assertEquals(1, $datafromkey['id']);
        $this->assertEquals('shortname', $datafromkey['field']);
        $this->assertEquals(2, $datafromkey['cmid']);
    }

    /**
     * Test the check_field_has_other_and_sourcetag method.
     *
     * @covers \local_deepler\local\data\field::__construct
     * @covers \local_deepler\local\data\multilanger::has_multilandcode_and_others
     * @return void
     */
    public function test_check_field_has_other_and_sourcetag(): void {
        $field1 = new field(1, '{mlang other}Other{mlang}{mlang en}English{mlang}', 1, 'shortname', 'course');
        $field2 = new field(2, 'Regular text', 1, 'name', 'course');
        $mlanger1 = new multilanger($field1->get_text());
        $mlanger2 = new multilanger($field2->get_text());
        $this->assertTrue($mlanger1->has_multilandcode_and_others('en'));
        $this->assertFalse($mlanger2->has_multilandcode_and_others('en'));
    }

    /**
     * Test the has_multilang method.
     *
     * @covers \local_deepler\local\data\multilanger::has_multilangs
     * @return void
     */
    public function test_has_multilang(): void {
        $field1 = new field(1, '{mlang en}English{mlang}', 1, 'shortname', 'course');
        $field2 = new field(2, 'Regular text', 1, 'name', 'course');
        $mlanger1 = new multilanger($field1->get_text());
        $mlanger2 = new multilanger($field2->get_text());
        $this->assertTrue($mlanger1->has_multilangs());
        $this->assertFalse($mlanger2->has_multilangs());
    }

    /**
     * Test the filterdbtextfields static method.
     *
     * @covers \local_deepler\local\data\field::filterdbtextfields
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
     * @covers \local_deepler\local\data\field::getfieldsfromcolumns
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

        $columns = ['name' => [], 'description' => [], 'empty' => []];
        $result = field::getfieldsfromcolumns($info, 'testtable', $columns);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(field::class, $result[0]);
        $this->assertInstanceOf(field::class, $result[1]);
        $this->assertEquals('name', $result[0]->get_tablefield());
        $this->assertEquals('description', $result[1]->get_tablefield());
    }
}
