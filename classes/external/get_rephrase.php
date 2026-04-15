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
use Exception;
use local_deepler\local\translation\interfaces\rephrase_capable_interface;

defined('MOODLE_INTERNAL') || die();

/**
 * External service to improve / rephrase text via the configured translation provider.
 *
 * Only providers that implement rephrase_capable_interface support this feature.
 * If the active provider does not support rephrase, an error is returned.
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_rephrase extends external_api {
    use deeplapi_trait;

    /**
     * Executes text improvement requests.
     *
     * @param array  $rephrasings
     * @param array  $options
     * @param string $version
     * @return array
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     */
    public static function execute(array $rephrasings, array $options, string $version): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'rephrasings' => $rephrasings,
            'options'     => $options,
            'version'     => $version,
        ]);

        try {
            $provider = self::set_provider($params['version']);
        } catch (Exception $exception) {
            return [[
                'error'                    => 'Exception ' . $exception->getMessage(),
                'key'                      => '',
                'text'                     => '',
                'target_language'          => '',
                'detected_source_language' => '',
            ]];
        }

        if ($provider === null) {
            return [[
                'error'                    => 'Translation provider could not be initialised.',
                'key'                      => '',
                'text'                     => '',
                'target_language'          => '',
                'detected_source_language' => '',
            ]];
        }

        if (!($provider instanceof rephrase_capable_interface)) {
            return [[
                'error'                    => get_string('provider_no_rephrase', 'local_deepler'),
                'key'                      => '',
                'text'                     => '',
                'target_language'          => '',
                'detected_source_language' => '',
            ]];
        }

        // Parse tone / style from the combined "toneorstyle" parameter.
        $style = $tone = null;
        if ($params['options']['toneorstyle'] !== 'default') {
            $rephraseoptions = explode("\n", $params['options']['toneorstyle']);
            $tone  = $rephraseoptions[0] === 'tone'          ? $rephraseoptions[1] : null;
            $style = $rephraseoptions[0] === 'writing_style' ? $rephraseoptions[1] : null;
        }

        $validatedparams = $provider->build_rephrase_params($params['options']['target_lang'], $style, $tone);
        $targetlang      = $validatedparams['target_lang'];
        unset($validatedparams['target_lang']);

        $staticparts   = [$validatedparams, $targetlang];
        $chunks        = self::chunk_payload($params['rephrasings'], $staticparts);
        $improvedtexts = [];

        foreach ($chunks as $chunk) {
            $texts = array_map(fn($t) => $t['text'], $chunk);
            try {
                $results = $provider->rephrase($texts, $targetlang, $validatedparams);
                foreach ($results as $index => $result) {
                    $improvedtexts[] = [
                        'error'                    => '',
                        'key'                      => $chunk[$index]['key'],
                        'text'                     => $result->text,
                        'target_language'          => $result->target_language,
                        'detected_source_language' => $result->detected_source_language,
                    ];
                }
            } catch (Exception $e) {
                return [[
                    'error'                    => 'Exception ' . $e->getMessage(),
                    'key'                      => '',
                    'text'                     => '',
                    'target_language'          => '',
                    'detected_source_language' => '',
                ]];
            }
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
                new external_single_structure([
                    'text' => new external_value(PARAM_RAW, 'text to be translated'),
                    'key'  => new external_value(PARAM_RAW, 'UI identifier for the text'),
                ])
            ),
            'options' => new external_single_structure([
                'target_lang'  => new external_value(PARAM_RAW, 'target language'),
                'toneorstyle'  => new external_value(
                    PARAM_RAW,
                    'Tone or writing style of your improvements',
                    VALUE_OPTIONAL
                ),
            ]),
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
            new external_single_structure([
                'key'                      => new external_value(PARAM_RAW, 'UI identifier for the text'),
                'text'                     => new external_value(PARAM_RAW, 'Improved text.'),
                'target_language'          => new external_value(PARAM_RAW, 'The target language specified by the user.'),
                'detected_source_language' => new external_value(
                    PARAM_RAW,
                    'The detected source language of the text provided in the request.',
                    VALUE_OPTIONAL
                ),
                'error'                    => new external_value(PARAM_RAW, 'error message', VALUE_OPTIONAL),
            ])
        );
    }
}
