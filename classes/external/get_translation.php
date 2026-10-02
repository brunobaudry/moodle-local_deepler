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
use DeepL\DeepLClient;
use DeepL\DeepLException;
use Exception;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/local/deepler/classes/vendor/autoload.php');

/**
 * External service to call DeepL's translation API.
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_translation extends external_api {
    use deeplapi_trait;

    /** @var string Opening tag of the single root element sent to DeepL. */
    private const WRAP_OPEN = '<div data-deepler-wrap="">';
    /** @var string Closing tag of the single root element sent to DeepL. */
    private const WRAP_CLOSE = '</div>';

    /**
     * Executes translation requests to DeepL, chunking them to respect payload limits.
     *
     * @param array $translations Array of translation requests.
     * @param array $options Translation options.
     * @param string $version Plugin version.
     * @return array Translated results or error messages.
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     */
    public static function execute(array $translations, array $options, string $version): array {
        $params = self::validate_parameters(self::execute_parameters(), [
                'translations' => $translations,
                'options' => $options,
                'version' => $version,
        ]);

        try {
            $translator = self::setdeeplapikey($params['version']);
        } catch (DeepLException $exception) {
            return [
                    [
                            'error' => 'Exception ' . $exception->getMessage(),
                            'key' => '',
                            'translated_text' => '',
                    ],
            ];
        }
        $targetlang = $params['options']['target_lang'];
        unset($params['options']['target_lang']);
        $glossaryid = $params['options']['glossary_id'];
        unset($params['options']['glossary_id']);
        $params['options']['glossary'] = $glossaryid;
        $ishtml = ($params['options']['tag_handling'] ?? '') === 'html';
        $groupedtranslations = [];
        foreach ($params['translations'] as $translation) {
            if ($ishtml) {
                $translation['text'] = self::wrap_html($translation['text']);
            }
            $groupedtranslations[$translation['source_lang']][] = $translation;
        }
        $translatedtexts = [];
        foreach ($groupedtranslations as $sourcelang => $translationsgroup) {
            $staticparts = [$params['options'], $sourcelang, $targetlang];
            $chunks = self::chunk_payload($translationsgroup, $staticparts);
            foreach ($chunks as $chunk) {
                $translatedtexts = array_merge(
                    $translatedtexts,
                    self::process_chunk($translator, $chunk, $sourcelang, $targetlang, $params['options'], $glossaryid)
                );
            }
        }
        return $translatedtexts;
    }

    /**
     * Processes a chunk of translations and returns translated results.
     *
     * @param DeepLClient $translator The DeepL client instance.
     * @param array $chunk The chunk of translations, HTML texts already wrapped by wrap_html().
     * @param string $sourcelang The source language.
     * @param string $targetlang The target language.
     * @param array $options Translation options.
     * @param string $glossaryid The glossary ID.
     * @return array Translated results or error.
     */
    private static function process_chunk(
        DeepLClient $translator,
        array $chunk,
        string $sourcelang,
        string $targetlang,
        array $options,
        string $glossaryid
    ): array {

        $ishtml = ($options['tag_handling'] ?? '') === 'html';
        $texts = array_map(function ($t) {
            return $t['text'];
        }, $chunk);

        try {
            $results = $translator->translateText($texts, $sourcelang, $targetlang, $options);
            $translated = [];

            foreach ($results as $index => $result) {
                $translated[] = [
                        'key' => $chunk[$index]['key'],
                        'translated_text' => $ishtml ? self::unwrap_html($result->text) : $result->text,
                        'glossary_id' => $glossaryid,
                        'error' => '',
                ];
            }

            return $translated;
        } catch (DeepLException $e) {
            // One malformed text fails the whole request, so retry one by one to isolate it.
            if (count($chunk) > 1 && self::is_bad_request($e)) {
                $translated = [];
                foreach ($chunk as $item) {
                    $translated = array_merge(
                        $translated,
                        self::process_chunk($translator, [$item], $sourcelang, $targetlang, $options, $glossaryid)
                    );
                }
                return $translated;
            }
            $single = count($chunk) === 1;
            return [
                    [
                            'glossary_id' => $glossaryid,
                            'error' => 'Deepl exception ' . $e->getMessage() . ($single ? ' [' . $chunk[0]['key'] . ']' : ''),
                            'key' => $single ? $chunk[0]['key'] : '',
                            'translated_text' => '',
                    ],
            ];
        } catch (Exception $e) {
            return [
                    [
                            'error' => 'Exception ' . $e->getMessage(),
                            'key' => '',
                            'translated_text' => '',
                            'glossary_id' => $glossaryid,
                    ],
            ];
        }
    }

    /**
     * Wraps a text in a single root element.
     *
     * DeepL's HTML tag handling v2 rejects texts with several top-level nodes
     * ("text without parent", "multiple roots").
     *
     * @param string $text
     * @return string
     */
    protected static function wrap_html(string $text): string {
        return self::WRAP_OPEN . $text . self::WRAP_CLOSE;
    }

    /**
     * Removes the root element added by wrap_html().
     *
     * @param string $text
     * @return string
     */
    protected static function unwrap_html(string $text): string {
        if (preg_match('#^\s*<div data-deepler-wrap(?:="")?>(.*)</div>\s*$#s', $text, $matches)) {
            return $matches[1];
        }
        return $text;
    }

    /**
     * Whether DeepL rejected the request content (HTTP 400), as opposed to auth, quota or network errors.
     *
     * @param DeepLException $e
     * @return bool
     */
    protected static function is_bad_request(DeepLException $e): bool {
        return get_class($e) === DeepLException::class && str_starts_with($e->getMessage(), 'Bad request');
    }

    /**
     * Param validator.
     *
     * @return \core_external\external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
                'translations' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'text' => new external_value(PARAM_RAW, 'text to be translated'),
                            'source_lang' => new external_value(PARAM_ALPHA, 'source language'),
                            'key' => new external_value(PARAM_RAW, 'UI identifier for the text'),
                        ]
                    )
                ),
                'options' => new external_single_structure(
                    [
                        'target_lang' => new external_value(PARAM_RAW, 'target language'),
                        'context' => new external_value(PARAM_RAW, 'context of the text'),
                        'tag_handling' => new external_value(PARAM_ALPHA, 'html or xml'),
                        'split_sentences' => new external_value(PARAM_ALPHANUMEXT, '0,1 or nonewlines'),
                        'preserve_formatting' => new external_value(PARAM_BOOL, 'preserve formatting ?'),
                        'formality' => new external_value(
                            PARAM_ALPHAEXT,
                            'default, less, prefer_more, prefer_less or more'
                        ),
                        'outline_detection' => new external_value(PARAM_BOOL, 'The automatic detection of the XML'),
                        'non_splitting_tags' => new external_value(
                            PARAM_RAW,
                            'Comma-separated list of XML tags which never split sentences'
                        ),
                        'splitting_tags' => new external_value(
                            PARAM_RAW,
                            'Comma-separated list of XML tags which always cause splits.'
                        ),
                        'ignore_tags' => new external_value(
                            PARAM_RAW,
                            'Comma-separated list of XML tags that indicate text not to be translated.'
                        ),
                        'glossary_id' => new external_value(
                            PARAM_ALPHANUMEXT,
                            'Specify the glossary to use for the translation.'
                        ),
                        'model_type' => new external_value(
                            PARAM_ALPHANUMEXT,
                            'Specifies which DeepL model should be used for translation.'
                        ),
                        'show_billed_characters' => new external_value(
                            PARAM_BOOL,
                            'Specifies whether the number of billed characters should be included in the response.'
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
                    'translated_text' => new external_value(PARAM_RAW, 'translated text'),
                    'glossary_id' => new external_value(PARAM_RAW, 'glossary id used'),
                    'error' => new external_value(PARAM_RAW, 'error message', VALUE_OPTIONAL),
                ]
            )
        );
    }
}
