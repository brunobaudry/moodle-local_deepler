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
 * User user_glossary model test case.
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_deepler\local\data\user_glossary
 */
final class user_glossary_test extends advanced_testcase {
    /**
     * Test creation.
     *
     * @covers \local_deepler\local\data\user_glossary
     * @return void
     * @throws \dml_exception
     */
    public function test_create_and_get_user_glossary(): void {
        $user = $this->getDataGenerator()->create_user();
        $glo = new glossary('11111111-2222-3333-4444-555555555555', 'test', 'en', 'fr', 10);
        $glodbid = glossary::create($glo);
        $data = new user_glossary($user->id, $glodbid, 1);

        $usergloid = user_glossary::create($data);
        $record = user_glossary::getbyid($usergloid);

        $this->assertEquals($data->userid, $record->userid);
        $this->assertEquals($glodbid, $record->glossaryid);
        $this->assertEquals($data->glossaryid, $record->glossaryid);
    }

    /**
     * Test update.
     *
     * @covers \local_deepler\local\data\user_glossary
     * @return void
     * @throws \dml_exception|\coding_exception
     */
    public function test_update_user_glossary(): void {
        $user = $this->getDataGenerator()->create_user();
        $data = new user_glossary($user->id, 2, 1);

        $id = user_glossary::create($data);
        $record = user_glossary::getbyid($id);
        $record->isactive = 0;

        user_glossary::update($record);
        $updated = user_glossary::getbyid($id);

        $this->assertEquals(0, $updated->isactive);
    }

    /**
     * Test deletion.
     *
     * @covers \local_deepler\local\data\user_glossary
     * @return void
     * @throws \dml_exception
     */
    public function test_delete_user_glossary(): void {
        $user = $this->getDataGenerator()->create_user();
        $data = new user_glossary($user->id, 3, 0);

        $id = user_glossary::create($data);
        user_glossary::delete($id);

        $this->expectException(dml_missing_record_exception::class);
        user_glossary::getbyid($id);
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
