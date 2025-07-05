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

/**
 * Unit tests for the glossary model.
 *
 * @package    local_deepler
 * @category   test
 * @group      local_deepler
 */

use local\data\glossary;

/**
 * Glossary model test case.
 */
class glossary_testcase extends advanced_testcase {
    /**
     * Test creation..
     *
     * @covers \local\data\glossary
     * @return void
     * @throws \dml_exception
     */
    public function test_create_and_get_glossary() {
        $data = (object) [
                'glossaryid' => 'abc123',
                'name' => 'Test Glossary',
                'source' => 'en',
                'target' => 'de',
                'timecreated' => time()
        ];

        $id = glossary::create($data);
        $record = glossary::getbyid($id);

        $this->assertEquals($data->glossaryid, $record->glossaryid);
        $this->assertEquals($data->name, $record->name);
    }

    /**
     * Test update.
     *
     * @covers \local\data\glossary
     * @return void
     * @throws \dml_exception
     */
    public function test_update_glossary() {
        $data = (object) [
                'glossaryid' => 'xyz789',
                'name' => 'Initial Name',
                'source' => 'en',
                'target' => 'fr',
                'timecreated' => time()
        ];

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
     * @covers \local\data\glossary
     * @return void
     * @throws \dml_exception
     */
    public function test_delete_glossary() {
        $data = (object) [
                'glossaryid' => 'del001',
                'name' => 'To Delete',
                'source' => 'en',
                'target' => 'es',
                'timecreated' => time()
        ];

        $id = glossary::create($data);
        glossary::delete($id);

        $this->expectException(dml_missing_record_exception::class);
        glossary::getbyid($id);
    }

    protected function setUp(): void {
        $this->resetAfterTest(true);
    }
}
