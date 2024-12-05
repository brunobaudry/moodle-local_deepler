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
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/PHPUnit
 */

namespace local_deepler\external;

use advanced_testcase;

/**
 * Test cases for update_translations external.
 */
final class updatetranslation_test extends advanced_testcase {
    /**
     * Set up.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
    }

    /**
     * Tests execute params.
     *
     * @covers \local_deepler\external\update_translation::execute_parameters
     * @return void
     */
    public function test_execute_parameters(): void {
        global $CFG;
        $this->resetAfterTest(true);
        // Skip this test for Moodle versions lower than 4.0.2.
        if (version_compare($CFG->version, '2023042400', '<')) {
            $this->markTestSkipped('This test is only for Moodle 4.0.2 and above.');
            return;
        }
        $params = update_translation::execute_parameters();
        $this->assertInstanceOf(\core_external\external_function_parameters::class, $params);
    }

    /**
     *  Tests execute returns.
     *
     * @covers \local_deepler\external\update_translation::execute_returns
     * @return void
     */
    public function test_execute_returns(): void {
        global $CFG;
        $this->resetAfterTest(true);
        // Skip this test for Moodle versions lower than 4.0.2.
        if (version_compare($CFG->version, '2023042400', '<')) {
            $this->markTestSkipped('This test is only for Moodle 4.0.2 and above.');
            return;
        }
        $returns = update_translation::execute_returns();
        $this->assertInstanceOf(\core_external\external_multiple_structure::class, $returns);
    }

    /**
     * Tests update_translation successs.
     *
     * @covers \local_deepler\external\update_translation::execute
     * @return void
     * @throws \coding_exception
     * @throws \core_external\restricted_context_exception
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public function test_execute_success(): void {
        global $DB, $CFG;
        $this->resetAfterTest(true);
        // Skip this test for Moodle versions lower than 4.0.2.
        if (version_compare($CFG->version, '2023042400', '<')) {
            $this->markTestSkipped('This test is only for Moodle 4.0.2 and above.');
            return;
        }
        // Create a test course.
        $course = $this->getDataGenerator()->create_course();

        // Create a test user with necessary capabilities.
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'editingteacher');
        $this->setUser($user);

        // Grant the necessary capability.
        $context = \context_course::instance($course->id);
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('local/deepler:edittranslations', CAP_ALLOW, $roleid, $context);
        role_assign($roleid, $user->id, $context);

        // Insert a test record in local_deepler table.
        $deeplerrecord = (object) [
                't_id' => $course->id,
                't_lang' => 'en',
                't_table' => 'course',
                't_field' => 'fullname',
                's_lastmodified' => time(),
                't_lastmodified' => time() - 3600, // 1 hour ago.
        ];
        $deeplerid = $DB->insert_record('local_deepler', $deeplerrecord);

        // Prepare test data.
        $data = [[
                'courseid' => $course->id,
                'id' => $course->id,
                'tid' => $deeplerid,
                'table' => 'course',
                'field' => 'fullname',
                'text' => '{mlang en}New Course Name{mlang}{mlang de}Neuer Kursname}',
        ],
        ];

        // Execute the external function.
        $result = update_translation::execute($data);

        // Assert the results.
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('t_lastmodified', $result[0]);
        $this->assertArrayHasKey('text', $result[0]);
        $this->assertArrayHasKey('keyid', $result[0]);
        $this->assertEquals('course-' . $course->id . '-fullname', $result[0]['keyid']);
        $this->assertEquals($data[0]['text'], $result[0]['text']);

        // Check if the course record was updated.
        $updatedcourse = $DB->get_record('course', ['id' => $course->id]);
        $this->assertEquals($data[0]['text'], $updatedcourse->fullname);

        // Check if the local_deepler record was updated.
        $updateddeepler = $DB->get_record('local_deepler', ['id' => $deeplerid]);
        $this->assertEquals($result[0]['t_lastmodified'], $updateddeepler->t_lastmodified);
    }

    /**
     *  Tests update_translation failing for wrong cap.
     *
     * @covers \local_deepler\external\update_translation::execute
     * @return void
     * @throws \core_external\restricted_context_exception
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public function test_execute_without_capability(): void {
        global $CFG;
        $this->resetAfterTest(true);
        // Skip this test for Moodle versions lower than 4.0.2.
        if (version_compare($CFG->version, '2023042400', '<')) {
            $this->markTestSkipped('This test is only for Moodle 4.0.2 and above.');
            return;
        }
        // Create a test course.
        $course = $this->getDataGenerator()->create_course();

        // Create a user without the necessary capability.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'editingteacher');
        // Prepare test data.
        $data = [
                [
                        'courseid' => $course->id,
                        'id' => $course->id,
                        'tid' => 1,
                        'table' => 'course',
                        'field' => 'fullname',
                        'text' => 'New Course Name',
                ],
        ];

        // Execute the external function and expect an exception.
        $this->expectException(\required_capability_exception::class);
        $result = update_translation::execute($data);
        $this->assertArrayHasKey('error', $result[0]);
        $this->assertStringContainsString('required_capability_exception', $result[0]['error']);
    }
}
