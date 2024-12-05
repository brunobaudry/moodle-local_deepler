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
 * Test cases for update_translations external.
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
 * Test cases for update_translations external.
 */
final class updatetranslation_test extends base_external {

    /**
     * Tests execute parameters.
     *
     * @covers \local_deepler\external\update_translation::execute_parameters
     * @return void
     */
    public function test_execute_parameters(): void {
        if ($this->is_below_four_one()) {
            return;
        }
        $params = update_translation::execute_parameters();
        $this->assertInstanceOf(\core_external\external_function_parameters::class, $params);
    }

    /**
     * Tests execute returns.
     *
     * @covers \local_deepler\external\update_translation::execute_returns
     * @return void
     */
    public function test_execute_returns(): void {
        if ($this->is_below_four_one()) {
            return;
        }
        $returns = update_translation::execute_returns();
        $this->assertInstanceOf(\core_external\external_multiple_structure::class, $returns);
    }

    /**
     * Data provider for test_execute_success.
     *
     * @return array
     */
    public static function execute_success_provider(): array {
        return [
                ['{mlang en}New Course Name{mlang}{mlang de}Neuer Kursname}'],
                ['{mlang en}Updated Course Name{mlang}{mlang fr}Nom du cours mis à jour}'],
        ];
    }

    /**
     * Tests update_translation success.
     *
     * @dataProvider execute_success_provider
     * @covers       \local_deepler\external\update_translation::execute
     * @param string $newcoursename
     * @return void
     * @throws \coding_exception
     * @throws \core_external\restricted_context_exception
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public function test_execute_success($newcoursename): void {
        global $DB;
        if ($this->is_below_four_one()) {
            return;
        }

        list($course, $user) = $this->create_test_course_and_user();
        $this->grant_capability($user, $course);

        $deeplerrecord = (object) [
                't_id' => $course->id,
                't_lang' => 'en',
                't_table' => 'course',
                't_field' => 'fullname',
                's_lastmodified' => time(),
                't_lastmodified' => time() - 3600, // 1 hour ago.
        ];
        $deeplerid = $DB->insert_record('local_deepler', $deeplerrecord);

        $data = [[
                'courseid' => $course->id,
                'id' => $course->id,
                'tid' => $deeplerid,
                'table' => 'course',
                'field' => 'fullname',
                'text' => $newcoursename,
        ]];

        $result = update_translation::execute($data);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('t_lastmodified', $result[0]);
        $this->assertArrayHasKey('text', $result[0]);
        $this->assertArrayHasKey('keyid', $result[0]);
        $this->assertEquals('course-' . $course->id . '-fullname', $result[0]['keyid']);
        $this->assertEquals($data[0]['text'], $result[0]['text']);

        $updatedcourse = $DB->get_record('course', ['id' => $course->id]);
        $this->assertEquals($data[0]['text'], $updatedcourse->fullname);

        $updateddeepler = $DB->get_record('local_deepler', ['id' => $deeplerid]);
        $this->assertEquals($result[0]['t_lastmodified'], $updateddeepler->t_lastmodified);
    }

    /**
     * Data provider for test_execute_without_capability.
     *
     * @return array
     */
    public static function execute_without_capability_provider(): array {
        return [
                ['New Course Name'],
                ['Updated Course Name'],
        ];
    }

    /**
     * Tests update_translation failing for wrong capability.
     *
     * @dataProvider execute_without_capability_provider
     * @covers       \local_deepler\external\update_translation::execute
     * @param string $newcoursename
     * @return void
     * @throws \core_external\restricted_context_exception
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public function test_execute_without_capability($newcoursename): void {
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
                        'tid' => 1,
                        'table' => 'course',
                        'field' => 'fullname',
                        'text' => $newcoursename,
                ],
        ];

        $this->expectException(\required_capability_exception::class);
        update_translation::execute($data);
    }
}
