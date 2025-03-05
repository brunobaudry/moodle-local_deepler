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
use Exception;
use invalid_parameter_exception;
use Throwable;

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
                                'id' => new external_value(PARAM_INT, 'The id of the course field'),
                                'tid' => new external_value(PARAM_INT, 'The id of the activity table'),
                                'table' => new external_value(PARAM_ALPHANUMEXT, 'The table name'),
                                'field' => new external_value(PARAM_ALPHANUMEXT, 'The field name'),
                                'cmid' => new external_value(PARAM_ALPHANUMEXT, 'The course module id'),
                                'text' => new external_value(PARAM_RAW, 'The new text content with multilang2 translations'),
                                'keyid' => new external_value(PARAM_RAW, 'The field ui identifier'),
                        ])
                ),
                'userid' => new external_value(PARAM_ALPHANUM, 'the user id'),
                'courseid' => new external_value(PARAM_ALPHANUM, 'the course id'),
        ]);
    }

    /**
     * Actually performs the DB updates.
     *
     * @param array $data
     * @param string $userid
     * @param int $courseid
     * @return array
     */
    public static function execute(array $data, string $userid, int $courseid): array {
        global $DB;
        $responses = [];
        try {
            $params = self::validate_parameters(self::execute_parameters(),
                    ['data' => $data, 'userid' => $userid, 'courseid' => $courseid]);
            $transaction = $DB->start_delegated_transaction();
            purge_all_caches();
            foreach ($params['data'] as $d) {
                $response = self::initialize_response($d);
                try {
                    // Security checks.
                    self::perform_security_checks($d, $params['userid'], $params['courseid']);
                    self::update_records($d, $response);
                } catch (invalid_parameter_exception $i) {
                    $response['error'] = "INVALID PARAM " . $i->getMessage();
                } catch (required_capability_exception $rc) {
                    $response['error'] = "CAPABILITY " . $rc->getMessage();
                } catch (restricted_context_exception $rce) {
                    $response['error'] = "CONTEXT " . $rce->getMessage();
                } catch (dml_exception $dmlexception) {
                    $response['error'] = $dmlexception->getMessage();
                } catch (Exception $e) {
                    $response['error'] = "Unexpected error: " . $e->getMessage();
                } catch (Throwable $t) {
                    $response['error'] = "Critical error: " . $t->getMessage();
                }

                $responses[] = $response;
            }
            // Commit the transaction.
            $transaction->allow_commit();
        } catch (invalid_parameter_exception $i) {
            $responses[] = ['error' => "INVALID PARAM MAIN " . $i->debuginfo ?? $i->errorcode, 'keyid' => '', 't_lastmodified' => 0,
                    'text' =>
                            ''];
        } catch (dml_transaction_exception $tex) {
            $responses[] = ['error' => "DML MAIN " . $tex->debuginfo ?? $tex->errorcode, 'keyid' => '', 't_lastmodified' => 0,
                    'text' => ''];
        }
        return $responses;
    }

    /**
     * Do the capability checks and skip when no context filter is provided.
     *
     * @param array $data
     * @param int $userid
     * @param int $courseid
     * @return void
     * @throws \core_external\restricted_context_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    private static function perform_security_checks(array $data, int $userid, int $courseid): void {
        $context = context_course::instance($courseid);
        self::validate_context($context);
        require_capability('local/deepler:edittranslations', $context, $userid);
        // Check detailed activity capabilities.
        if ($data['cmid'] != 0) {
            $contextmodule = context_module::instance($data['cmid']);
            require_capability('moodle/course:manageactivities', $contextmodule, $userid);
        }
    }

    /**
     * Prepare response object.
     *
     * @param array $data
     * @return array
     */
    private static function initialize_response(array $data): array {
        return [
                'keyid' => $data['keyid'],
                't_lastmodified' => 0,
                'text' => '',
                'error' => '',
        ];
    }

    /**
     * Perform the DB entry and update the response item.
     *
     * @param array $data
     * @param array $response
     * @return void
     * @throws \dml_exception
     */
    private static function update_records(array $data, array &$response): void {
        global $DB;
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
                        'keyid' => new external_value(PARAM_RAW, 'the key id of the field updated table-id-field'),
                ])
        );
    }
}
