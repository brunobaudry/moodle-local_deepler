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
use DeepL\AppInfo;
use DeepL\DeepLClient;
use DeepL\DeepLException;
use DeepL\RephraseTextOptions;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/local/deepler/classes/vendor/autoload.php');

/**
 * External service to call DeepL's text improvement API.
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_rephrase extends external_api {
    use deeplapi_trait;

    /**
     * Parse row and global options to determine effective tone/style and DeepL rephrase options.
     *
     * @param array $rephrasing
     * @param string $globaltoneorstyle
     * @return array [bool $iscorrect, array $rephraseoptions]
     */
    private static function parse_rephrase_options(array $rephrasing, string $globaltoneorstyle): array {
        $rowoverrides = array_filter($rephrasing['options'] ?? [], static fn($v) => !is_null($v));
        $effectivetoneorstyle = $rowoverrides['toneorstyle'] ?? $globaltoneorstyle;

        $iscorrect = ($effectivetoneorstyle === 'correct');
        $style = null;
        $tone = null;

        if (!$iscorrect && $effectivetoneorstyle !== 'default' && !empty($effectivetoneorstyle)) {
            $parts = explode('|', $effectivetoneorstyle);
            if (count($parts) === 2) {
                $tone = ($parts[0] === 'tone') ? $parts[1] : null;
                $style = ($parts[0] === 'writing_style') ? $parts[1] : null;
            }
        }

        $rephraseoptions = [];
        if ($style !== null) {
            $rephraseoptions[RephraseTextOptions::WRITING_STYLE] = $style;
        }
        if ($tone !== null) {
            $rephraseoptions[RephraseTextOptions::TONE] = $tone;
        }

        return [$iscorrect, $rephraseoptions];
    }

    /**
     * Group rephrasings by target language and rephrase options.
     *
     * @param array $rephrasings
     * @param string $targetlang
     * @param string $globaltoneorstyle
     * @return array
     */
    private static function group_rephrasings(array $rephrasings, string $targetlang, string $globaltoneorstyle): array {
        $grouped = [];
        foreach ($rephrasings as $rephrasing) {
            [$iscorrect, $rephraseoptions] = self::parse_rephrase_options($rephrasing, $globaltoneorstyle);
            $md5 = md5((string)json_encode($rephraseoptions));
            $groupkey = ($iscorrect ? 'correct_' : 'rephrase_') . $targetlang . '_' . $md5;

            if (!isset($grouped[$groupkey])) {
                $grouped[$groupkey] = [
                    'targetlang' => $targetlang,
                    'options' => $rephraseoptions,
                    'iscorrect' => $iscorrect,
                    'rephrasings' => [],
                ];
            }
            $grouped[$groupkey]['rephrasings'][] = $rephrasing;
        }
        return $grouped;
    }

    /**
     * Process a chunk of texts through DeepL client.
     *
     * @param DeepLClient $improver
     * @param array $chunk
     * @param string $targetlang
     * @param array $options
     * @param bool $iscorrect
     * @return array
     * @throws DeepLException
     */
    private static function process_chunk(
        DeepLClient $improver,
        array $chunk,
        string $targetlang,
        array $options,
        bool $iscorrect
    ): array {
        $texts = array_map(static fn($t) => $t['text'], $chunk);
        $results = $iscorrect
            ? $improver->correctText($texts, $targetlang)
            : $improver->rephraseText($texts, $targetlang, $options);

        $chunkresults = [];
        foreach ($results as $index => $result) {
            $chunkresults[] = [
                'error' => '',
                'key' => $chunk[$index]['key'],
                'text' => $result->text,
                'target_language' => $result->targetLanguage,
                'detected_source_language' => $result->detectedSourceLanguage,
            ];
        }
        return $chunkresults;
    }

    /**
     * External service to call DeepL's improve API.
     *
     * @param array $rephrasings
     * @param array $options
     * @param string $version
     * @return array
     * @throws \DeepL\DeepLException
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     */
    public static function execute(array $rephrasings, array $options, string $version): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'rephrasings' => $rephrasings,
            'options' => $options,
            'version' => $version,
        ]);

        try {
            $improver = self::setdeeplapikey($params['version']);
        } catch (DeepLException $exception) {
            return [[
                'error' => 'Exception ' . $exception->getMessage(),
                'key' => '',
                'text' => '',
                'target_language' => '',
                'detected_source_language' => '',
            ]];
        }

        $targetlang = $params['options']['target_lang'];
        $globaltoneorstyle = $params['options']['toneorstyle'] ?? 'default';
        $groupedrephrasings = self::group_rephrasings($params['rephrasings'], $targetlang, $globaltoneorstyle);

        $improvedtexts = [];
        foreach ($groupedrephrasings as $group) {
            $staticparts = [$group['options'], $group['targetlang']];
            $chunks = self::chunk_payload($group['rephrasings'], $staticparts);

            foreach ($chunks as $chunk) {
                try {
                    $chunkresults = self::process_chunk(
                        $improver,
                        $chunk,
                        $group['targetlang'],
                        $group['options'],
                        $group['iscorrect']
                    );
                    array_push($improvedtexts, ...$chunkresults);
                } catch (DeepLException $e) {
                    return [[
                        'error' => 'Exception ' . $e->getMessage(),
                        'key' => '',
                        'text' => '',
                        'target_language' => '',
                        'detected_source_language' => '',
                    ]];
                }
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
                    new external_single_structure(
                        [
                                'text' => new external_value(PARAM_RAW, 'text to be translated'),
                                'key' => new external_value(PARAM_RAW, 'UI identifier for the text'),
                                'options' => new external_single_structure(
                                    [
                                        'toneorstyle' => new external_value(
                                            PARAM_RAW,
                                            'Tone or writing style of your improvements',
                                            VALUE_DEFAULT,
                                            null
                                        ),
                                    ],
                                    'Optional row-level option overrides',
                                    VALUE_DEFAULT,
                                    []
                                ),
                        ]
                    )
                ),
                'options' => new external_single_structure(
                    [
                        'target_lang' => new external_value(PARAM_RAW, 'target language'),
                        'toneorstyle' => new external_value(
                            PARAM_RAW,
                            'Tone or writing style of your improvements',
                            VALUE_DEFAULT,
                            'default'
                        ),
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
                    'detected_source_language' => new external_value(
                        PARAM_RAW,
                        'The detected source language of the text provided in the request.',
                        VALUE_OPTIONAL
                    ),
                    'error' => new external_value(PARAM_RAW, 'error message', VALUE_OPTIONAL),
                ]
            )
        );
    }
}
