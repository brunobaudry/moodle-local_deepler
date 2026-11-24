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

namespace local_deepler;

use advanced_testcase;
use context_course;
use context_system;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/deepler/lib.php');

/**
 * Tests for lib.php callbacks.
 *
 * @package    local_deepler
 * @copyright  2026 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::local_deepler_pluginfile
 */
final class lib_test extends advanced_testcase {
    /**
     * The callback accepts the arguments core passes (null course and cm) and refuses what it must not serve.
     *
     * @return void
     */
    public function test_pluginfile_refusals(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $system = context_system::instance();

        $this->setAdminUser();
        // Wrong context level.
        $coursecontext = context_course::instance($course->id);
        $this->assertFalse(local_deepler_pluginfile($course, null, $coursecontext, 'custom_docs', [0, 'a.txt'], false));
        // File area not whitelisted.
        $this->assertFalse(local_deepler_pluginfile(null, null, $system, 'other', [0, 'a.txt'], false));
        // Allowed area but no such file.
        $this->assertFalse(local_deepler_pluginfile(null, null, $system, 'custom_docs', [0, 'a.txt'], false));

        // A user without the capability.
        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertFalse(local_deepler_pluginfile(null, null, $system, 'custom_docs', [0, 'a.txt'], false));
    }
}
