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
use context_module;
use core_external\external_api;
use core_external\restricted_context_exception;
use invalid_parameter_exception;
use required_capability_exception;

/**
 * Security checker service for local_deepler.
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class security_checker {
    /**
     * Perform security checks.
     * Do the capability checks and skip when no context filter is provided.
     *
     * @param array $data
     * @param int $userid
     * @param int $courseid
     * @param string $cap
     * @return void
     * @throws \core_external\restricted_context_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    private static function perform_security_checks(array $data, int $userid, int $courseid, string $cap): void {
        $context = context_course::instance($courseid);
        external_api::validate_context($context);
        require_capability($cap, $context, $userid);
        if ($data['cmid'] != 0) {
            $contextmodule = context_module::instance($data['cmid']);
            require_capability('moodle/course:manageactivities', $contextmodule, $userid);
        }
    }

    /**
     * Perform security checks for translations.
     *
     * @param array $data
     * @param int $userid
     * @param int $courseid
     * @return void
     * @throws \core_external\restricted_context_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public function perform_security_checks_for_translations(array $data, int $userid, int $courseid): void {
        self::perform_security_checks($data, $userid, $courseid, 'local/deepler:updatetranslations');
    }

    /**
     * Perform security checks for removal.
     *
     * @param array $data
     * @param int $userid
     * @param int $courseid
     * @return void
     * @throws \core_external\restricted_context_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public function perform_security_checks_for_removal(array $data, int $userid, int $courseid): void {
        self::perform_security_checks($data, $userid, $courseid, 'local/deepler:deletetranslations');
    }
}
