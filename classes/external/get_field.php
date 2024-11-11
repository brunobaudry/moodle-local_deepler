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

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External service to fetch textual field data for further translation updates.
 *
 * @package local_deepler
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_field extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
                'data' => new external_multiple_structure(
                        new external_single_structure([
                                'courseid' => new external_value(PARAM_INT, 'The course to fetch the field from'),
                                'id' => new external_value(PARAM_INT, 'The id of the course field'),
                                'table' => new external_value(PARAM_ALPHANUMEXT, 'The id of the activity table'),
                                'field' => new external_value(PARAM_ALPHANUMEXT, 'The id of the activity table field'),
                        ])
                ),
        ]);
    }

    /**
     * Fetch activity field info for further translation text update.
     *
     * @param array $data
     * @return array
     * @throws \core_external\restricted_context_exception
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public static function execute($data) {
        global $CFG, $DB;
        $params = self::validate_parameters(self::execute_parameters(), ['data' => $data]);
        $transaction = $DB->start_delegated_transaction();
        $response = [];
        foreach ($params['data'] as $data) {
            // Security checks.
            $context = \context_course::instance($data['courseid']);
            self::validate_context($context);
            require_capability('local/deepler:edittranslations', $context);
            // Check detailed activity capabilities.
            if ($data['table'] !== 'course' && $data['table'] !== 'course_sections') {
                require_capability('moodle/course:manageactivities', \context_module::instance($data['id']));
            }
            // Get the original record.
            $record = (array) $DB->get_record($data['table'], ['id' => $data['id']]);
            $text = $record[$data['field']];

            $response[] = ['text' => $text];
        }
        // Commit the transaction.
        $transaction->allow_commit();
        return $response;
    }

    /**
     * Describes what the webservice yields.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure (
                new external_single_structure(['text' => new external_value(PARAM_RAW, 'Fields text content')])
        );
    }
}
