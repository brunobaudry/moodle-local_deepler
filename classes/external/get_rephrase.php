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

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use DeepL\AppInfo;
use DeepL\DeepLClient;
use DeepL\DeepLException;
use external_api;

/**
 * External service to call DeepL's text improvement API.
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_rephrase extends external_api {
    /** @var deeplapi_trait $this */
    use deeplapi_trait;

    /** @var string */
    private static string $apikey;

    /** @var \DeepL\AppInfo */
    private static AppInfo $appinfo;

    /**
     * @throws \dml_exception
     * @throws \DeepL\DeepLException|\invalid_parameter_exception
     */
    public static function execute(array $rephrasings, array $options, string $version): array {
        // Set the api with env so that it can be unit tested.
        self::setdeeplapi($version);
        if (empty(self::$apikey)) {
            throw new DeepLException('authKey must be a non-empty string');
        }
        $params = self::validate_parameters(self::execute_parameters(),
                ['rephrasings' => $rephrasings, 'options' => $options, 'version' => $version]);
        $improver = new DeepLClient(
                self::$apikey,
                [
                        'send_platform_info' => true,
                        'app_info' => self::$appinfo,
                ]
        );
        $improver->buildRephraseBodyParams($params['options']);
        $improvedtexts = [];
        $rephrases = $params['rephrasings'];
        $texts = array_map(function($t) {
            return $t['text'];
        }, $rephrases);
        try {
            $results = $improver->rephraseText($texts);
            foreach ($results as $index => $result) {
                $improvedtexts[] = [
                        'error' => '',
                        'key' => $rephrases[$index]['key'],
                        'text' => $result->text,
                        'target_language' => $result->target_language,
                        'detected_source_language' => $result->detected_source_language,
                ];
            }
        } catch (DeepLException $e) {
            return
                    [['error' => 'Exception ' . $e->getMessage(),
                            'key' => '',
                            'text' => '',
                            'target_language' => '',
                            'detected_source_language' => '',
                    ]];
        }
        return $improvedtexts;
    }

    /**
     * Param validator.
     *
     * @return \core_external\external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
                'rephrasings' => new external_multiple_structure(
                        new external_single_structure(
                                [
                                        'text' => new external_value(PARAM_RAW, 'text to be translated'),
                                        'key' => new external_value(PARAM_RAW, 'UI identifier for the text'),
                                ])),
                'options' => new external_single_structure(
                        [
                                'target_lang' => new external_value(PARAM_RAW, 'target language'),
                                'writing_style' => new external_value(PARAM_RAW, 'writing style of your improvements',
                                        VALUE_OPTIONAL),
                                'tone' => new external_value(PARAM_ALPHA, 'Changes the tone of your improvements.', VALUE_OPTIONAL),
                        ]
                ),
                'version' => new external_value(PARAM_RAW, 'the plugin version id'),
        ]);
    }

    /**
     * Return validator.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
                new external_single_structure(
                        [
                                'key' => new external_value(PARAM_RAW, 'UI identifier for the text'),
                                'text' => new external_value(PARAM_RAW, 'Improved text.'),
                                'target_language' => new external_value(PARAM_RAW, 'The target language specified by the user.'),
                                'detected_source_language' => new external_value(PARAM_RAW,
                                        'The detected source language of the text provided in the request.', VALUE_OPTIONAL),
                                'error' => new external_value(PARAM_RAW, 'error message', VALUE_OPTIONAL),
                        ]
                )
        );
    }
}
