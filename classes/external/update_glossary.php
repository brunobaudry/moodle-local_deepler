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

use core\exception\moodle_exception;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use dml_exception;
use Exception;

/**
 * External service to update glossaries usage timestamp.
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_glossary extends external_api {
    /**
     * Execute external call.
     *
     * @param array $glossaryids
     * @return array
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     * @throws \invalid_parameter_exception|\core\exception\moodle_exception
     */
    public static function execute(array $glossaryids): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), ['glossaryids' => $glossaryids]);

        $results = [];
        $now = time();
        $transaction = $DB->start_delegated_transaction();
        try {
            foreach ($params['glossaryids'] as $id) {
                $record = $DB->get_record('local_deepler_glossaries', ['glossaryid' => $id]);
                if ($record) {
                    $record->lastused = $now;
                    try {
                        $DB->update_record('local_deepler_glossaries', $record);
                        $results[] = [
                                'glossaryid' => $id,
                                'status' => 'success',
                                'message' => 'Timestamp updated',
                        ];
                    } catch (dml_exception $e) {
                        $results[] = [
                                'glossaryid' => $id,
                                'status' => 'error',
                                'message' => 'Could not update glossary last used',
                        ];
                    }

                } else {
                    $results[] = [
                            'glossaryid' => $id,
                            'status' => 'error',
                            'message' => 'Glossary ID not found',
                    ];
                }
            }

            $transaction->allow_commit();

        } catch (Exception $e) {
            // Transaction will auto-rollback here.
            throw new moodle_exception(
                    'transactionfailed',
                    'local_deepler', '', null, $e->getMessage());
        }
        return $results;
    }

    /**
     * Validate params.
     *
     * @return \core_external\external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(['glossaryids' => new external_multiple_structure(
                        new external_value(PARAM_TEXT, 'Glossary ID'),
                        'Array of glossary IDs'
        )]);
    }

    /**
     * Validates retruns.
     *
     * @return \core_external\external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
                new external_single_structure([
                        'glossaryid' => new external_value(PARAM_TEXT, 'Glossary ID'),
                        'status' => new external_value(PARAM_TEXT, 'Result status: success or error'),
                        'message' => new external_value(PARAM_TEXT, 'Detailed message'),
                ])
        );
    }
}
