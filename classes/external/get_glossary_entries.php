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

use core_external\external_single_structure;
use core_external\external_value;
use DeepL\DeepLClient;
use DeepL\DeepLException;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/local/deepler/classes/vendor/autoload.php');

/**
 * Simple service to fetch glossary's entries.
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_glossary_entries extends external_api {
    use deeplapi_trait;

    /**
     * Execute.
     *
     * @param string $glossaryid
     * @param string $version
     * @return array
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     */
    public static function execute(string $glossaryid, string $version): array {

        $params = self::validate_parameters(self::execute_parameters(), [
                'glossaryid' => $glossaryid,
                'version' => $version,
        ]);
        try {
            $translator = self::setdeeplapikey($params['version']);
            $glossaryid = $params['glossaryid'];
            $glo = $translator->getGlossary($glossaryid);
            $sourcelang = $glo->sourceLang;
            $targetlang = $glo->targetLang;
            $ge = $translator->getGlossaryEntries($glossaryid);
            return [
                    'glossaryid' => $glossaryid,
                    'entries' => json_encode($ge->getEntries(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'status' => 'success',
                    'message' => 'Shared value updated',
                    'source' => $sourcelang,
                    'target' => $targetlang,
            ];
        } catch (DeepLException $exception) {
            return [
                    'glossaryid' => $glossaryid,
                    'entries' => '',
                    'status' => 'error',
                    'source' => $sourcelang ?? 'source',
                    'target' => $targetlang ?? 'target',
                    'message' => $exception->getMessage(),
            ];
        }

    }

    /**
     * Validate params.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
                'glossaryid' => new external_value(PARAM_TEXT, 'Glossary ID'),
                'version' => new external_value(PARAM_TEXT, 'Plugin\'s release'),
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
                'entries' => new external_value(PARAM_RAW, 'JSON-encoded array key entries'),
                'status' => new external_value(PARAM_TEXT, 'Result status'),
                'message' => new external_value(PARAM_TEXT, 'Detailed message'),
                'source' => new external_value(PARAM_TEXT, 'source'),
                'target' => new external_value(PARAM_TEXT, 'target'),
        ]);
    }
}
