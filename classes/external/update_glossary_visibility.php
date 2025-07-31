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

use core\exception\moodle_exception;
use core_external\external_api;
use core_external\external_function_parameters;

use core_external\external_single_structure;
use core_external\external_value;

/**
 * Simple service to update glossaries' visibilty.
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_glossary_visibility extends external_api {
    /**
     * Execute.
     *
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public static function execute(string $glossaryid, int $shared): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
                'glossaryid' => $glossaryid,
                'shared' => $shared
        ]);

        if (!in_array($params['shared'], [0, 1, 2], true)) {
            throw new moodle_exception('invalidsharedvalue', 'local_deepler', '', null, 'Shared must be 0, 1, or 2');
        }

        $record = $DB->get_record('local_deepler_glossaries', ['glossaryid' => $params['glossaryid']]);

        if (!$record) {
            throw new moodle_exception('glossarynotfound', 'local_yourplugin', '', null, 'Glossary ID not found');
        }

        $record->shared = $params['shared'];
        $DB->update_record('local_deepler_glossaries', $record);

        return [
                'glossaryid' => $params['glossaryid'],
                'status' => 'success',
                'message' => 'Shared value updated'
        ];
    }

    /**
     * Validate params.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
                'glossaryid' => new external_value(PARAM_TEXT, 'Glossary ID'),
                'shared' => new external_value(PARAM_INT, 'Shared value (0, 1, or 2)')
        ]);
    }

    /**
     * Validate return.
     *
     * @return \external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
                'glossaryid' => new external_value(PARAM_TEXT, 'Glossary ID'),
                'status' => new external_value(PARAM_TEXT, 'Result status'),
                'message' => new external_value(PARAM_TEXT, 'Detailed message')
        ]);
    }
}
