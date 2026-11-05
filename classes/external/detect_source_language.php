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
use DeepL\DeepLException;
use local_deepler\local\services\lang_helper;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/local/deepler/classes/vendor/autoload.php');

/**
 * Web service for detecting source language of a text excerpt.
 *
 * @package    local_deepler
 * @copyright  2026 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class detect_source_language extends external_api {
    use deeplapi_trait;

    /**
     * Parameter description for execute.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'text' => new external_value(PARAM_RAW, 'Text excerpt to detect language for'),
                'row_id' => new external_value(PARAM_RAW, 'Row identifier', VALUE_DEFAULT, ''),
                'version' => new external_value(PARAM_ALPHANUMEXT, 'Version string', VALUE_DEFAULT, 'v1'),
            ]
        );
    }

    /**
     * Executes the detection.
     *
     * @param string $text
     * @param string $row_id
     * @param string $version
     * @return array
     */
    public static function execute(string $text, string $row_id = '', string $version = 'v1'): array {
        global $USER;

        $params = self::validate_parameters(
            self::execute_parameters(),
            [
                'text' => $text,
                'row_id' => $row_id,
                'version' => $version,
            ]
        );

        // Check if admin has enabled auto-detection.
        $enabled = (bool)get_config('local_deepler', 'autodetect_sourcelang');
        if (!$enabled || trim($params['text']) === '') {
            return [
                'detected_lang' => '',
                'row_id' => $params['row_id'],
                'enabled' => $enabled,
                'error' => '',
            ];
        }

        $langhelper = new lang_helper();
        if (!$langhelper->initdeepl($USER, $params['version'])) {
            return [
                'detected_lang' => '',
                'row_id' => $params['row_id'],
                'enabled' => $enabled,
                'error' => 'API initialization failed',
            ];
        }

        $translator = $langhelper->get_translator();
        if (!$translator) {
            return [
                'detected_lang' => '',
                'row_id' => $params['row_id'],
                'enabled' => $enabled,
                'error' => 'DeepL translator unavailable',
            ];
        }

        // Limit excerpt to ~300 chars to save character quota.
        $excerpt = mb_substr(strip_tags($params['text']), 0, 300);

        try {
            // Translating to English or target without source_lang forces DeepL to detect source language.
            $result = $translator->translateText($excerpt, null, 'en-US');
            $detected = '';
            if (is_array($result) && !empty($result)) {
                $detected = $result[0]->detectedSourceLanguage ?? '';
            } else if (is_object($result)) {
                $detected = $result->detectedSourceLanguage ?? '';
            }

            return [
                'detected_lang' => strtolower($detected),
                'row_id' => $params['row_id'],
                'enabled' => $enabled,
                'error' => '',
            ];
        } catch (DeepLException $e) {
            return [
                'detected_lang' => '',
                'row_id' => $params['row_id'],
                'enabled' => $enabled,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Return description for execute.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            [
                'detected_lang' => new external_value(PARAM_RAW, 'Detected language code'),
                'row_id' => new external_value(PARAM_RAW, 'Row identifier'),
                'enabled' => new external_value(PARAM_BOOL, 'Whether auto-detection is enabled'),
                'error' => new external_value(PARAM_RAW, 'Error message if any'),
            ]
        );
    }
}
