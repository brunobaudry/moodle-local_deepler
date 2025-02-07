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

use context_course;
use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\required_capability_exception;
use core_external\restricted_context_exception;
use dml_exception;
use dml_transaction_exception;
use invalid_parameter_exception;

/**
 * External service to update multilang2 translations and log a timestamp.
 *
 * @package local_deepler
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_translation extends external_api {
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
                                'tid' => new external_value(PARAM_INT, 'The id of the activity table'),
                                'table' => new external_value(PARAM_ALPHANUMEXT, 'The table name'),
                                'field' => new external_value(PARAM_ALPHANUMEXT, 'The field name'),
                                'text' => new external_value(PARAM_RAW, 'The new text content with multilang2 translations'),
                        ])
                ),
        ]);
    }

    /**
     *
     * @param $data
     * @return array
     * @throws \required_capability_exception
     *
     * public static function execute($data) {
     * global $DB;
     * $responses = [];
     *
     * try {
     * $params = self::validate_parameters(self::execute_parameters(), ['data' => $data]);
     * $transaction = $DB->start_delegated_transaction();
     * purge_all_caches();
     *
     * foreach ($params['data'] as $data) {
     * $responses[] = self::process_single_data($data, $DB);
     * }
     *
     * $transaction->allow_commit();
     * } catch (Exception $e) {
     * $responses[] = self::handle_exception($e);
     * }
     *
     * return $responses;
     * }
     *
     * private static function process_single_data($data, $DB) {
     * $response = self::initialize_response($data);
     *
     * try {
     * self::perform_security_checks($data);
     * self::update_records($data, $DB, $response);
     * } catch (Exception $e) {
     * $response['error'] = $e->debuginfo ?? $e->errorcode;
     * }
     *
     * return $response;
     * }
     *
     * private static function initialize_response($data) {
     * return [
     * 'keyid' => $data['table'] . '-' . $data['id'] . '-' . $data['field'],
     * 't_lastmodified' => 0,
     * 'text' => '',
     * 'error' => ''
     * ];
     * }
     *
     * private static function perform_security_checks($data) {
     * $context = context_course::instance($data['courseid']);
     * self::validate_context($context);
     * require_capability('local/deepler:edittranslations', $context);
     *
     * if (self::requires_activity_capability($data['table'])) {
     * require_capability('moodle/course:manageactivities', context_module::instance($data['id']));
     * }
     * }
     *
     * private static function requires_activity_capability($table) {
     * return $table !== 'course' && $table !== 'course_sections' &&
     * strpos($table, 'question') === false &&
     * strpos($table, 'qtype') === false;
     * }
     *
     * private static function update_records($data, $DB, &$response) {
     * $dataobject = ['id' => $data['id'], $data['field'] => $data['text']];
     * $DB->update_record($data['table'], (object) $dataobject);
     *
     * $timemodified = time();
     * $DB->update_record('local_deepler', ['id' => $data['tid'], 't_lastmodified' => $timemodified]);
     *
     * $response['t_lastmodified'] = $timemodified;
     * $response['text'] = $data['text'];
     * }
     *
     * private static function handle_exception($e) {
     * return [
     * 'error' => $e->debuginfo ?? $e->errorcode,
     * 'keyid' => '',
     * 't_lastmodified' => 0,
     * 'text' => ''
     * ];
     * }
     */
    /**
     * Actually performs the DB updates.
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
        global $DB;
        $responses = [];
        try {
            $params = self::validate_parameters(self::execute_parameters(), ['data' => $data]);
            $transaction = $DB->start_delegated_transaction();
            purge_all_caches();
            foreach ($params['data'] as $data) {
                $response = self::initialize_response($data);
                //$dataobject['id'] = $data['id'];
                //$dataobject[$data['field']] = $data['text'];
                /*$keyid = $data['table'] . '-' . $data['id'] . '-' . $data['field'];
                $response['keyid'] = $keyid;
                $response['t_lastmodified'] = 0;
                $response['text'] = '';
                $response['error'] = '';*/
                try {
                    // Security checks.
                    self::perform_security_checks($data);
                    self::update_records($data, $DB, $response);
                    /*$DB->update_record($data['table'], (object) $dataobject);
                    // Update t_lastmodified.
                    $timemodified = time();
                    $DB->update_record('local_deepler', ['id' => $data['tid'], 't_lastmodified' => $timemodified]);
                    $response['t_lastmodified'] = $timemodified;
                    $response['text'] = $data['text'];*/

                } catch (required_capability_exception $capex) {
                    $response['error'] = $capex->debuginfo ?? $capex->errorcode;
                } catch (restricted_context_exception $cex) {
                    $response['error'] = $capex->debuginfo ?? $capex->errorcode;
                } catch (dml_exception $dmlexception) {
                    $response['error'] = $dmlexception->debuginfo ?? $dmlexception->errorcode;
                }
                $responses[] = $response;
            }
            // Commit the transaction.
            $transaction->allow_commit();
        } catch (invalid_parameter_exception $i) {
            $responses[] = ['error' => $i->debuginfo ?? $i->errorcode, 'keyid' => '', 't_lastmodified' => 0, 'text' => ''];
        } catch (dml_transaction_exception $tex) {
            $responses[] = ['error' => $tex->debuginfo ?? $tex->errorcode, 'keyid' => '', 't_lastmodified' => 0, 'text' => ''];
        }
        return $responses;

    }

    private static function perform_security_checks($data) {
        $context = context_course::instance($data['courseid']);
        self::validate_context($context);
        require_capability('local/deepler:edittranslations', $context);
        // Check detailed activity capabilities.
        if ($data['table'] !== 'course' && $data['table'] !== 'course_sections' &&
                strpos($data['table'], 'question') === false &&
                strpos($data['table'], 'qtype') === false) {
            require_capability('moodle/course:manageactivities', context_module::instance($data['id']));
        }
    }

    private static function initialize_response($data) {
        return [
                'keyid' => $data['table'] . '-' . $data['id'] . '-' . $data['field'],
                't_lastmodified' => 0,
                'text' => '',
                'error' => ''
        ];
    }

    private static function update_records($data, $DB, &$response) {
        $dataobject = ['id' => $data['id'], $data['field'] => $data['text']];
        $DB->update_record($data['table'], (object) $dataobject);

        $timemodified = time();
        $DB->update_record('local_deepler', ['id' => $data['tid'], 't_lastmodified' => $timemodified]);

        $response['t_lastmodified'] = $timemodified;
        $response['text'] = $data['text'];
    }
    /**
     * Describes what the webservice yields.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
                new external_single_structure([
                        't_lastmodified' => new external_value(PARAM_INT, 'Timestamp the field was modified'),
                        'text' => new external_value(PARAM_RAW, 'The updated text content'),
                        'error' => new external_value(PARAM_RAW, 'An error message if any'),
                        'keyid' => new external_value(PARAM_ALPHANUMEXT, 'the key id of the field updated table-id-field'),
                ])
        );
    }
}
