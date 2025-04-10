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

namespace local_deepler\external;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/local/deepler/tests/external/base_external.php');

use core_external\external_function_parameters;
use core_external\external_multiple_structure;

/**
 * Test cases for update_translations external.
 *
 * @package    local_deepler
 * @copyright  2024 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/PHPUnit
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
        $this->assertInstanceOf(external_function_parameters::class, $params);
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
        $this->assertInstanceOf(external_multiple_structure::class, $returns);
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
     * @covers \local_deepler\external\update_translation::execute
     * @param string $newcoursename
     * @return void
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     */
    public function test_execute_success(string $newcoursename): void {
        global $DB;
        if ($this->is_below_four_one()) {
            return;
        }

        list($course, $user) = $this->create_test_course_and_user();
        $this->grant_capability($user, $course);
        $cid = $course->id;
        $deeplerrecord = (object) [
                't_id' => $cid,
                't_lang' => 'en',
                't_table' => 'course',
                't_field' => 'fullname',
                's_lastmodified' => time(),
                't_lastmodified' => time() - 3600, // 1 hour ago.
        ];
        $deeplerid = $DB->insert_record('local_deepler', $deeplerrecord);

        $data = [[
                'id' => $cid,
                'tid' => $deeplerid,
                'table' => 'course',
                'field' => 'fullname',
                'cmid' => 0,
                'text' => $newcoursename,
                'keyid' => "course[$cid][fullname][0]",
        ]];

        $result = update_translation::execute($data, $user->id, $course->id, 'update');

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('t_lastmodified', $result[0]);
        $this->assertArrayHasKey('text', $result[0]);
        $this->assertArrayHasKey('keyid', $result[0]);
        $this->assertEquals('course[' . $course->id . '][fullname][0]', $result[0]['keyid']);
        $this->assertEquals($data[0]['text'], $result[0]['text']);

        $updatedcourse = $DB->get_record('course', ['id' => $course->id]);
        $this->assertEquals($data[0]['text'], $updatedcourse->fullname);

        $updateddeepler = $DB->get_record('local_deepler', ['id' => $deeplerid]);
        $this->assertEquals($result[0]['t_lastmodified'], $updateddeepler->t_lastmodified);
    }
}
