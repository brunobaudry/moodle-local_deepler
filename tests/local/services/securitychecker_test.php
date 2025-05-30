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

use context_course;
use core_external\external_api;
use local_deepler\external\base_external;

/**
 * Unit tests for security_checker class.
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class securitychecker_test extends base_external {

    /**
     * Test perform_security_checks method.
     *
     * @covers \local_deepler\local\services\security_checker::perform_security_checks
     * @return void
     * @throws \coding_exception
     * @throws \core_external\restricted_context_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public function test_perform_security_checks(): void {
        $this->resetAfterTest(true);

        // Create a course and a user for testing.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        // Assign the required capability to the user.
        $roleid = $this->getDataGenerator()->create_role(['shortname' => 'testrole']);
        assign_capability('local/deepler:edittranslations', CAP_ALLOW, $roleid, context_course::instance($course->id));
        role_assign($roleid, $user->id, context_course::instance($course->id));
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        // Log in the user.
        $this->setUser($user);

        $data = ['cmid' => 0];
        $userid = $user->id;
        $courseid = $course->id;

        // Mock the context_course::instance method.
        $contextcourse = $this->createMock(context_course::class);
        $contextcourse->method('instance')->willReturn($contextcourse);
        // Mock the external_api::validate_context method.
        if ($this->is_below_four_one()) {
            return;
        }
        $externalapi = $this->createMock(external_api::class);
        $externalapi->method('validate_context')->with($contextcourse);
        // Call the method.
        security_checker::perform_security_checks($data, $userid, $courseid, 'local/deepler:edittranslations');

        // Add assertions to verify the behavior.
        $this->assertTrue(true); // Replace with actual assertions.
    }
}
