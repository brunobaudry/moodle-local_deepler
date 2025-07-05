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
 * Unit tests for the user_glossary model.
 *
 * @package    local_deepler
 * @category   test
 * @group      local_deepler
 */

use local\data\user_glossary;

/**
 * User glossary model test case.
 */
final class user_glossary_testcase extends advanced_testcase {
    /**
     * Test creation.
     *
     * @covers \local\data\user_glossary
     * @return void
     * @throws \dml_exception
     */
    public function test_create_and_get_user_glossary() {
        $user = $this->getDataGenerator()->create_user();
        $data = (object) [
                'userid' => $user->id,
                'glossaryid' => 1,
                'is_active' => 1
        ];

        $id = user_glossary::create($data);
        $record = user_glossary::getbyid($id);

        $this->assertEquals($data->userid, $record->userid);
        $this->assertEquals($data->glossaryid, $record->glossarydbid);
    }

    /**
     * Test update.
     *
     * @covers \local\data\user_glossary
     * @return void
     * @throws \dml_exception
     */
    public function test_update_user_glossary() {
        $user = $this->getDataGenerator()->create_user();
        $data = (object) [
                'userid' => $user->id,
                'glossaryid' => 2,
                'is_active' => 1
        ];

        $id = user_glossary::create($data);
        $record = user_glossary::getbyid($id);
        $record->is_active = 0;

        user_glossary::update($record);
        $updated = user_glossary::getbyid($id);

        $this->assertEquals(0, $updated->is_active);
    }

    /**
     * Test deletion.
     *
     * @covers \local\data\user_glossary
     * @return void
     * @throws \dml_exception
     */
    public function test_delete_user_glossary() {
        $user = $this->getDataGenerator()->create_user();
        $data = (object) [
                'userid' => $user->id,
                'glossaryid' => 3,
                'is_active' => 1
        ];

        $id = user_glossary::create($data);
        user_glossary::delete($id);

        $this->expectException(dml_missing_record_exception::class);
        user_glossary::getbyid($id);
    }

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void {
        $this->resetAfterTest(true);
    }
}
