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

/**
 * Test cases for get_translations external.
 *
 * @package    local_deepler
 * @copyright  2024 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/PHPUnit
 */

namespace local_deepler\external;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/local/deepler/tests/external/base_external.php');

/**
 * Test cases for get_translations external.
 */
final class getfieldexternal_test extends base_external {

    /**
     * Tests the execution of the get_field external.
     *
     * @dataProvider execute_provider
     * @covers       \local_deepler\external\get_field::execute
     * @return void
     * @throws \core_external\restricted_context_exception
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     * @throws \coding_exception
     */
    public function test_execute($field): void {
        if ($this->is_below_four_one()) {
            return;
        }

        list($course, $user) = $this->create_test_course_and_user();
        $this->grant_capability($user, $course);

        // Create a test activity (page).
        $page = $this->getDataGenerator()->create_module('page', [
                'course' => $course->id,
                'name' => 'Test Page',
                'content' => 'Test content',
        ]);

        // Prepare test data.
        $data = [
                [
                        'courseid' => $course->id,
                        'id' => $course->id,
                        'table' => 'course',
                        'field' => $field,
                ],
        ];

        // Execute the external function.
        $result = get_field::execute($data);

        // Assert the results.
        $this->assertCount(1, $result);
        $this->assertEquals($course->{$field}, $result[0]['text']);
    }

    /**
     * Data provider for test_execute.
     *
     * @return array
     */
    public static function execute_provider(): array {
        return [
                ['fullname'],
                ['shortname'],
        ];
    }

    /**
     * Data provider for test_execute_without_capability.
     *
     * @return array
     */
    public static function execute_without_capability_provider(): array {
        return [
                ['fullname'],
                ['shortname'],
        ];
    }

    /**
     * Test if an error is thrown when capability is not granted.
     *
     * @dataProvider execute_without_capability_provider
     * @covers       \local_deepler\external\get_field::execute
     * @return void
     * @throws \core_external\restricted_context_exception
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public function test_execute_without_capability($field): void {
        if ($this->is_below_four_one()) {
            return;
        }

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'editingteacher');

        $data = [
                [
                        'courseid' => $course->id,
                        'id' => $course->id,
                        'table' => 'course',
                        'field' => $field,
                ],
        ];

        $this->expectException(\required_capability_exception::class);
        get_field::execute($data);
    }

    /**
     * Tests execute parameters.
     *
     * @covers \local_deepler\external\get_field::execute_parameters
     * @return void
     */
    public function test_execute_parameters(): void {
        if ($this->is_below_four_one()) {
            return;
        }
        $params = get_field::execute_parameters();
        $this->assertInstanceOf(\core_external\external_function_parameters::class, $params);
    }

    /**
     * Tests execute returns.
     *
     * @covers \local_deepler\external\get_field::execute_returns
     * @return void
     */
    public function test_execute_returns(): void {
        if ($this->is_below_four_one()) {
            return;
        }
        $returns = get_field::execute_returns();
        $this->assertInstanceOf(\core_external\external_multiple_structure::class, $returns);
    }
}
