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
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/local/deepler/classes/vendor/autoload.php');

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\required_capability_exception;
use core_external\restricted_context_exception;
use dml_transaction_exception;
use Exception;
use invalid_parameter_exception;
use local_deepler\local\data\field;
use local_deepler\local\data\multilanger;
use local_deepler\local\services\database_updater;
use local_deepler\local\services\lang_helper;
use local_deepler\local\services\security_checker;
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
     * @var string
     */
    private static string $action;

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
                'data' => new external_multiple_structure(
                        new external_single_structure([
                                'tid' => new external_value(PARAM_INT, 'The id of the in the deepler table to trsck'),
                                'text' => new external_value(PARAM_RAW, 'The new text content translated or updated'),
                                'keyid' => new external_value(PARAM_RAW, 'The field ui identifier'),
                                'mainsourcecode' => new external_value(PARAM_ALPHANUMEXT, 'The main source code'),
                                'sourcecode' => new external_value(PARAM_RAW, 'The source code of the translation'),
                                'targetcode' => new external_value(PARAM_RAW, 'The targe code to save to'),
                                'sourcetext' => new external_value(PARAM_RAW, 'The sourcetext to save if needed'),
                        ])
                ),
                'userid' => new external_value(PARAM_ALPHANUM, 'the user id'),
                'courseid' => new external_value(PARAM_ALPHANUM, 'the course id'),
                'action' => new external_value(PARAM_ALPHANUM, 'type of action to perform'),
        ]);
    }

    /**
     * Actually performs the DB updates.
     *
     * @param array $data
     * @param string $userid
     * @param int $courseid
     * @param string $action
     * @return array
     */
    public static function execute(array $data, string $userid, int $courseid, string $action): array {
        global $DB;
        self::$action = $action;
        $responses = [];
        try {
            $params = self::validate_parameters(self::execute_parameters(),
                    ['data' => $data, 'userid' => $userid, 'courseid' => $courseid, 'action' => $action]);
            $transaction = $DB->start_delegated_transaction();
            purge_all_caches();
            foreach ($params['data'] as $d) {
                $response = self::initialize_response($d);
                $response = self::process_data($d, $params, $response);
                $responses[] = $response;
            }
            // Commit the transaction.
            $transaction->allow_commit();
        } catch (invalid_parameter_exception $i) {
            $responses[] = self::handle_exception($i, 'INVALID PARAM MAIN');
        } catch (dml_transaction_exception $tex) {
            $responses[] = self::handle_exception($tex, 'DML MAIN');
        }
        return $responses;
    }
    /**
     * Process each data item.
     *
     * @param array $data
     * @param array $params
     * @param array $response
     * @return array
     */
    private static function process_data(array $data, array $params, array $response): array {
        try {
            // Parse the key and setup the text.
            // We need to do it here caus' we need the $cmid to check security.
            self::preparedata($data);
            // Perform different security checks depending on the action.
            switch (self::$action) {
                case 'update':
                    security_checker::perform_security_checks_for_translations($data, $params['userid'], $params['courseid']);
                    break;
                case 'remove':
                    security_checker::perform_security_checks_for_removal($data, $params['userid'], $params['courseid']);
                    break;
                default:
                    throw new invalid_parameter_exception('Invalid action');
            }
            // Fetch the current text field content.
            $fieldtext = database_updater::get_textfield($data['table'], $data['field'], $data['id']);
            self::preparetext($data, $fieldtext);
            database_updater::update_records($data, $response);
        } catch (invalid_parameter_exception $i) {
            $response['error'] = "INVALID PARAM " . $i->getMessage();
        } catch (required_capability_exception $rc) {
            $response['error'] = "CAPABILITY " . $rc->getMessage();
        } catch (restricted_context_exception $rce) {
            $response['error'] = "CONTEXT " . $rce->getMessage();
        } catch (Exception $e) {
            $response['error'] = "Unexpected error: " . $e->getMessage();
        } catch (Throwable $t) {
            $response['error'] = "Critical error: " . $t->getMessage();
        }
        return $response;
    }

    /**
     * Prepare the text for saving.
     * Keeping the source text in OTHER or the main source code, or without mlang if rephrase.
     * And adding the target code if not rephrased.
     *
     * @param array $data
     * @return void
     * @throws \coding_exception
     */
    public static function preparedata(array &$data): void {
        $datafromkey = field::generatedatfromkey($data['keyid']);
        $data['table'] = $datafromkey['table'];
        $data['field'] = $datafromkey['field'];
        $data['id'] = $datafromkey['id'];
        $data['cmid'] = $datafromkey['cmid'];
    }
    /**
     * Handle exceptions and prepare response.
     *
     * @param Exception $exception
     * @param string $prefix
     * @return array
     */
    private static function handle_exception($exception, string $prefix): array {
        return [
                'error' => $prefix . " " . ($exception->debuginfo ?? $exception->errorcode),
                'keyid' => '',
                't_lastmodified' => 0,
                'text' => '',
        ];
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

    /**
     * Manipulate the text and mlangs.
     *
     * @param array $data
     * @param string $fieldtext
     * @return void
     * @throws \coding_exception
     */
    public static function preparetext(array &$data, string $fieldtext): void {
        $mlanger = new multilanger($fieldtext);
        if (str_contains($data['sourcecode'], lang_helper::REPHRASESYMBOL)) {
            // Rephrasing.
            $sourcecode = str_replace(lang_helper::REPHRASESYMBOL, '', $data['sourcecode']);
            if ($mlanger->has_multilangs()) {
                if ($sourcecode === $data['mainsourcecode']) {
                    $mlanger->replacemlang($sourcecode, $data['sourcetext'], $data['text']);
                } else {
                    $mlanger->update_or_add_mlang($data['targetcode'], $data['text']);
                }
                $data['text'] = $mlanger->get_text();
            }
            // Rephrasing, no mlang then no need to manipulate the text: Save rephrassed.
        } else {
            // Translation.
            if (!$mlanger->has_multilangs()) {
                $mlanger->wrapmlang('other');
            } else {
                if ($data['sourcecode'] !== $data['mainsourcecode']) {
                    $mlanger->update_or_add_mlang($data['sourcecode'], $data['sourcetext']);
                }
            }
            $mlanger->update_or_add_mlang($data['targetcode'], $data['text']);
            $data['text'] = $mlanger->get_text();
        }
    }
}
