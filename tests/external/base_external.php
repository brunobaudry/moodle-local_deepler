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
 * Base test case for local_deepler external functions.
 *
 * @package    local_deepler
 * @copyright  2024 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_deepler\external;

use advanced_testcase;
use context_course;

/**
 * Base test case for local_deepler external functions.
 */
abstract class base_external extends advanced_testcase {
    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * Create a test course and user.
     *
     * @return array
     */
    protected function create_test_course_and_user() {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'editingteacher');
        $this->setUser($user);
        return [$course, $user];
    }

    /**
     * Grant the necessary capability.
     *
     * @param object $user
     * @param object $course
     * @return void
     */
    protected function grant_capability($user, $course) {
        $context = context_course::instance($course->id);
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('local/deepler:edittranslations', CAP_ALLOW, $roleid, $context);
        role_assign($roleid, $user->id, $context);
    }

    /**
     * Check if Moodle is prior to 4.2.
     *
     * @return boolean
     */
    protected function is_below_four_one(): bool {
        global $CFG;
        if (version_compare($CFG->version, '2023042400', '<')) {
            $this->markTestSkipped('This test is only for Moodle 4.0.2 and above.');
            return true;
        }
        return false;
    }
}
