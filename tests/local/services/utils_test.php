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
use context_user;

/**
 * Test cases.
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/PHPUnit
 */
final class utils_test extends advanced_testcase {
    /**
     * Color index test.
     *
     * @covers \local_deepler\local\services\utils::makecolorindex
     * @return void
     */
    public function test_makecolorindex_returns_correct_colors(): void {
        $input = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
        $result = utils::makecolorindex($input);
        $this->assertCount(8, $result);
        $this->assertEquals('a', $result[0]['key']);
        $this->assertEquals('FloralWhite', $result[0]['value']);
        $this->assertEquals('Ivory', $result[7]['value']); // Last color in list.
    }

    /**
     * Test make HTML id.
     *
     * @covers \local_deepler\local\services\utils::makehtmlid
     * @return void
     */
    public function test_makehtmlid_transforms_text_correctly(): void {
        $input = 'Hello World_123!';
        $expected = 'hello-world-123';
        $this->assertEquals($expected, utils::makehtmlid($input));
    }

    /**
     * Test make HTML id.
     *
     * @covers \local_deepler\local\services\utils::makehtmlid
     * @return void
     */
    public function test_makehtmlid_prefixes_digit_start(): void {
        $input = '123abc';
        $expected = 'id-123abc';
        $this->assertEquals($expected, utils::makehtmlid($input));
    }

    /**
     * Test wildcard_match.
     *
     * @covers \local_deepler\local\services\utils::wildcard_match
     * @return void
     */
    public function test_wildcard_match_basic_patterns(): void {
        $this->assertTrue(utils::wildcard_match('he_lo', 'hello'));
        $this->assertTrue(utils::wildcard_match('he%', 'hello'));
        $this->assertFalse(utils::wildcard_match('hi%', 'hello'));
    }

    /**
     * Test standard_user_fields.
     *
     * @covers \local_deepler\local\services\utils::standard_user_fields
     * @return void
     * @throws \coding_exception
     */
    public function test_standard_user_fields_contains_expected_keys(): void {
        $fields = utils::standard_user_fields();
        $expectedkeys =
            ['username', 'email', 'firstname', 'lastname', 'city', 'country', 'institution', 'department', 'phone1', 'phone2',
                'address', 'idnumber'];
        foreach ($expectedkeys as $key) {
            $this->assertArrayHasKey($key, $fields);
        }
    }

    /**
     * Test get_plugin_root.
     *
     * @covers \local_deepler\local\services\utils::get_plugin_root
     * @return void
     */
    public function test_get_plugin_root_returns_valid_path(): void {
        $path = utils::get_plugin_root();
        $this->assertStringContainsString('local/deepler', $path);
        $this->assertDirectoryExists($path);
    }

    /**
     * Test get user fields.
     *
     * @covers \local_deepler\local\services\utils::all_user_fields
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_all_user_fields_returns_combined_fields(): void {
        global $DB;

        $this->resetAfterTest(true);

        // Create a test user context.
        $user = $this->getDataGenerator()->create_user();
        $context = context_user::instance($user->id);

        // Insert a mock custom profile field.
        $fielddata = (object) [
            'shortname' => 'customfield1',
            'name' => 'Custom Field One',
            'datatype' => 'text',
        ];
        $fielddata->id = $DB->insert_record('user_info_field', $fielddata);

        // Call the method.
        $fields = utils::all_user_fields($context);

        // Check standard fields.
        $this->assertArrayHasKey('username', $fields);
        $this->assertArrayHasKey('email', $fields);

        // Check custom field.
        $this->assertArrayHasKey('profile_field_customfield1', $fields);
        $this->assertEquals('Custom Field One', $fields['profile_field_customfield1']);
    }
}
