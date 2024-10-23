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

require_once($CFG->libdir . '/externallib.php');

/**
 * Local Course Translator Web Service.
 *
 * Adds a webservice available via ajax for the Translate Content page.
 *
 * @package    local_deepler
 * @copyright  2022 Kaleb Heitzman <kaleb@jamfire.io>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/External_functions_API
 */
class local_deepler_external extends external_api {

    /**
     * Get field parameters.
     *
     * Adds validation parameters for getting db fields.
     *
     * @return external_function_parameters
     */
    public static function get_field_parameters() {
        return new external_function_parameters(
                ['data' => new external_multiple_structure(
                        new external_single_structure(
                                ['courseid' => new external_value(PARAM_INT,
                                        'course id'), 'id' => new external_value(PARAM_INT, 'id of table record'),
                                        'table' => new external_value(PARAM_RAW, 'table to update text'),
                                        'field' => new external_value(PARAM_RAW, 'table field to update'),
                                ]))]);
    }

    /**
     * Get DB Field
     *
     * Dynamically get db field to allow simultaenous editing.
     *
     * @param object $data
     * @return array
     */
    public static function get_field($data) {
        global $CFG, $DB;

        $params = self::validate_parameters(self::get_field_parameters(), ['data' => $data]);
        $transaction = $DB->start_delegated_transaction();
        $response = [];

        foreach ($params['data'] as $data) {
            // Check for null values and throw errors.

            // Security checks.
            $context = context_course::instance($data['courseid']);
            self::validate_context($context);
            require_capability('local/deepler:edittranslations', $context);

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
     * Returns field data to the user from web service.
     *
     * @return external_multiple_structure
     */
    public static function get_field_returns() {
        return new external_multiple_structure(new external_single_structure(['text' => new external_value(PARAM_RAW,
                'updated text of field')]));
    }

    /**
     * Update Translation Parameters.
     *
     * Adds validation parameters for translations.
     *
     * @return external_function_parameters
     */
    public static function update_translation_parameters() {
        return new external_function_parameters(
                ['data' => new external_multiple_structure(
                        new external_single_structure(
                                ['courseid' => new external_value(PARAM_INT,
                                        'course id'), 'id' => new external_value(PARAM_INT, 'id of table record'),
                                        'tid' => new external_value(PARAM_INT, 'tid of local_deepler record'),
                                        'table' => new external_value(PARAM_RAW, 'table to update text'),
                                        'field' => new external_value(PARAM_RAW, 'table field to update'),
                                        'text' => new external_value(PARAM_RAW, 'text to be upserted'),
                                ]))]);
    }

    /**
     * Update Translation.
     *
     * Dynamically update table and column name for item submitted.
     *
     * @param object $data
     * @return array
     */
    public static function update_translation($data) {
        global $CFG, $DB;

        $params = self::validate_parameters(self::update_translation_parameters(), ['data' => $data]);

        $transaction = $DB->start_delegated_transaction();

        $response = [];

        foreach ($params['data'] as $data) {

            purge_all_caches();

            // Check for null values and throw errors.

            // Security checks.
            $context = context_course::instance($data['courseid']);
            self::validate_context($context);
            require_capability('local/deepler:edittranslations', $context);

            // Update the record.
            $dataobject = [];
            $dataobject['id'] = $data['id'];
            $dataobject[$data['field']] = $data['text'];
            $DB->update_record($data['table'], (object) $dataobject);

            // Update t_lastmodified.
            $timemodified = time();
            $DB->update_record('local_deepler', ['id' => $data['tid'], 't_lastmodified' => $timemodified]);

            $response[] = ['t_lastmodified' => $timemodified, 'text' => $data['text']];
        }

        // Commit the transaction.
        $transaction->allow_commit();

        return $response;
    }

    /**
     * Return Translation.
     *
     * Returns updated translation to the user from web service.
     *
     * @return external_multiple_structure
     */
    public static function update_translation_returns() {
        return new external_multiple_structure(new external_single_structure(['t_lastmodified' => new external_value(PARAM_INT,
                'translation last modified time'), 'text' => new external_value(PARAM_RAW, 'text of field'),
        ]));
    }

}
