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
use dml_missing_record_exception;

/**
 * Unit tests for the glossary model.
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_deepler\local\data\glossary
 */
final class glossary_test extends advanced_testcase {
    /**
     * Test creation.
     *
     * @covers \local_deepler\local\data\glossary
     * @return void
     * @throws \dml_exception
     */
    public function test_create_and_get_glossary(): void {
        $data = new glossary(
                'abc123',
                'Test Glossary',
                'en',
                'de',
                3,
                time()
        );

        $id = glossary::create($data);
        $record = glossary::getbyid($id);

        $this->assertEquals($data->glossaryid, $record->glossaryid);
        $this->assertEquals($data->name, $record->name);
    }

    /**
     * Test update.
     *
     * @covers \local_deepler\local\data\glossary
     * @return void
     * @throws \dml_exception|\coding_exception
     */
    public function test_update_glossary(): void {
        $data = new glossary(
                'abc124',
                'Test Glossary 2',
                'en',
                'de',
                3,
                time()
        );

        $id = glossary::create($data);
        $record = glossary::getbyid($id);
        $record->name = 'Updated Name';

        glossary::update($record);
        $updated = glossary::getbyid($id);

        $this->assertEquals('Updated Name', $updated->name);
    }

    /**
     * Test deletion.
     *
     * @covers \local_deepler\local\data\glossary
     * @return void
     * @throws \dml_exception
     */
    public function test_delete_glossary(): void {
        $data = new glossary(
                'abc12356',
                'Test Glossary 3',
                'it',
                'fr',
                3,
                time()
        );

        $id = glossary::create($data);
        glossary::delete($id);

        $this->expectException(dml_missing_record_exception::class);
        glossary::getbyid($id);
    }

    /**
     * Basic setup.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }
}
