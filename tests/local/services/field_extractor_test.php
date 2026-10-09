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

namespace local_deepler\local\services;

use advanced_testcase;
use coding_exception;
use local_deepler\local\data\field;
use moodle_database;
use stdClass;

/**
 * Unit tests for the field_extractor service class.
 *
 * @package    local_deepler
 * @category   test
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_deepler\local\services\field_extractor
 */
final class field_extractor_test extends advanced_testcase {
    /**
     * Test key parsing and error handling for invalid keys.
     *
     * @covers ::generate_data_from_key
     * @covers ::generatedatfromkey
     * @return void
     */
    public function test_key_parsing(): void {
        $key = 'course[42][summary][10]';
        $data = field_extractor::generate_data_from_key($key);
        $this->assertEquals([
            'table' => 'course',
            'id' => 42,
            'field' => 'summary',
            'cmid' => 10,
        ], $data);

        $aliasdata = field_extractor::generatedatfromkey($key);
        $this->assertEquals($data, $aliasdata);

        $this->expectException(coding_exception::class);
        field_extractor::generate_data_from_key('invalid-key-format');
    }

    /**
     * Test filter_db_text_fields using mocked DB columns.
     *
     * @covers ::filter_db_text_fields
     * @covers ::filterdbtextfields
     * @return void
     */
    public function test_filter_db_text_fields(): void {
        $dbmock = $this->getMockBuilder(moodle_database::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_columns'])
            ->getMockForAbstractClass();

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

        $dbmock->expects($this->once())->method('get_columns')->with('testtable')->willReturn($mockcolumns);

        $extractor = new field_extractor($dbmock, null, 254);
        $result = $extractor->filter_db_text_fields('testtable');

        $this->assertEquals(['name', 'description'], $result);
    }

    /**
     * Test get_fields_from_columns extracting translatable fields.
     *
     * @covers ::get_fields_from_columns
     * @covers ::getfieldsfromcolumns
     * @return void
     */
    public function test_get_fields_from_columns(): void {
        $info = (object) [
            'id' => 1,
            'name' => 'Test Name',
            'description' => 'Test Description',
            'descriptionformat' => 1,
            'empty' => '',
        ];

        $columns = ['name' => [], 'description' => [], 'empty' => []];
        $extractor = new field_extractor();
        $result = $extractor->get_fields_from_columns($info, 'testtable', $columns);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(field::class, $result[0]);
        $this->assertInstanceOf(field::class, $result[1]);
        $this->assertEquals('name', $result[0]->get_tablefield());
        $this->assertEquals('description', $result[1]->get_tablefield());
        $this->assertEquals(1, $result[1]->get_format());
    }

    /**
     * Test exclusion and editable clauses in get_fields_from_columns.
     *
     * @covers ::get_fields_from_columns
     * @return void
     */
    public function test_get_fields_from_columns_exclusions(): void {
        $info = (object) [
            'id' => 1,
            'keep' => 'keep me',
            'skip' => 'skip me',
            'boolskip' => 'boolean skip',
            'editablefield' => 'content',
        ];

        $columns = [
            'keep' => null,
            'skip' => ['exclude' => 'skip me'],
            'boolskip' => ['exclude' => true],
            'editablefield' => ['editable' => false],
        ];

        $extractor = new field_extractor();
        $result = $extractor->get_fields_from_columns($info, 'testtable', $columns);

        $this->assertCount(2, $result);
        $this->assertEquals('keep', $result[0]->get_tablefield());
        $this->assertTrue($result[0]->iseditable());
        $this->assertEquals('editablefield', $result[1]->get_tablefield());
        $this->assertFalse($result[1]->iseditable());
    }

    /**
     * Test create_class_from_string helper.
     *
     * @covers ::create_class_from_string
     * @return void
     */
    public function test_create_class_from_string(): void {
        $extractor = new field_extractor();
        $nonexistent = $extractor->create_class_from_string('non_existent_sub_class', new stdClass());
        $this->assertNull($nonexistent);
    }
}
