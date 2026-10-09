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
use local_deepler\local\services\field_extractor;

/**
 * Unit tests for the field entity class.
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
     * @covers \local_deepler\local\data\field::get_displaytext
     * @covers \local_deepler\local\data\field::iseditable
     * @covers \local_deepler\local\data\field::get_maxlength
     * @return void
     */
    public function test_constructor_and_getters(): void {
        $sampltext = 'Sample text';

        $field = new field(1, $sampltext, 1, 'shortname', 'course', 2, true, 255);

        $this->assertEquals(1, $field->get_id());
        $this->assertEquals(2, $field->get_cmid());
        $this->assertEquals('shortname', $field->get_tablefield());
        $this->assertEquals('course', $field->get_table());
        $this->assertEquals($sampltext, $field->get_text());
        $this->assertEquals($sampltext, $field->get_displaytext());
        $this->assertEquals(1, $field->get_format());
        $this->assertTrue($field->iseditable());
        $this->assertEquals(255, $field->get_maxlength());
    }

    /**
     * Test maxlength getter and setter.
     *
     * @covers \local_deepler\local\data\field::get_maxlength
     * @covers \local_deepler\local\data\field::set_maxlength
     * @return void
     */
    public function test_maxlength_mutation(): void {
        $field = new field(1, 'Sample', 0, 'name', 'course');
        $this->assertNull($field->get_maxlength());
        $field->set_maxlength(100);
        $this->assertEquals(100, $field->get_maxlength());
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
        $datafromkey = field_extractor::generate_data_from_key($field->getkey());
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
     * @covers \local_deepler\local\data\field::check_field_has_other_and_sourcetag
     * @covers \local_deepler\local\data\multilanger::has_multilandcode_and_others
     * @return void
     */
    public function test_check_field_has_other_and_sourcetag(): void {
        $field1 = new field(1, '{mlang other}Other{mlang}{mlang en}English{mlang}', 1, 'shortname', 'course');
        $field2 = new field(2, 'Regular text', 1, 'name', 'course');
        $this->assertTrue($field1->check_field_has_other_and_sourcetag('en'));
        $this->assertFalse($field2->check_field_has_other_and_sourcetag('en'));

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
}
