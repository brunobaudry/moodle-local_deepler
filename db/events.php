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
 * Course Translator Observers
 *
 * Watch for course, course section, and mod updates
 *
 * @package    local_deepler
 * @copyright  2022 Kaleb Heitzman <kaleb@jamfire.io>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/Events_API
 */

defined('MOODLE_INTERNAL') || die();

// Event observer for local_deepler.
$observers = [
        [
                'eventname' => '\core\event\course_updated',
                'callback' => 'local_deepler_observer::course_updated',
        ],
        [
                'eventname' => '\core\event\course_section_updated',
                'callback' => 'local_deepler_observer::course_section_updated',
        ],
        [
                'eventname' => '\core\event\course_module_updated',
                'callback' => 'local_deepler_observer::course_module_updated',
        ],
];
