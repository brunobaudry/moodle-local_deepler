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

/**
 * Unit tests for database_updater.
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class databaseupdater_test extends advanced_testcase {
    /**
     * Test update_records method.
     *
     * @covers \local_deepler\local\services\database_updater::update_records
     * @return void
     */
    public function test_update_records(): void {
        global $DB;

        // Set up test data.
        $this->resetAfterTest(true);
        $data = [
            'id' => 1,
            'field' => 'fullname',
            'text' => 'Updated Name',
            'table' => 'course',
            'tid' => 1,
        ];
        $response = [];

        // Insert initial record.
        $DB->insert_record('course', (object) ['id' => 1, 'fullname' => 'Original Name']);
        $DB->insert_record('local_deepler', (object) ['t_id' => 1, 't_lastmodified' => 0]);

        // Call the method.
        database_updater::update_records($data, $response);

        // Verify the record was updated.
        $updatedrecord = $DB->get_record('course', ['id' => 1]);
        $this->assertEquals('Updated Name', $updatedrecord->fullname);

        // Verify the response.
        $this->assertArrayHasKey('t_lastmodified', $response);
        $this->assertArrayHasKey('text', $response);
        $this->assertEquals('Updated Name', $response['text']);
    }
}
