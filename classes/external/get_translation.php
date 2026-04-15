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
use local_deepler\local\translation\interfaces\translation_provider_interface;

defined('MOODLE_INTERNAL') || die();

/**
 * External service to translate text via the configured translation provider.
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_translation extends external_api {
    use deeplapi_trait;

    /**
     * Executes translation requests, chunking them to respect payload limits.
     *
     * @param array  $translations  Array of translation requests.
     * @param array  $options       Translation options.
     * @param string $version       Plugin version.
     * @return array Translated results or error messages.
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     */
    public static function execute(array $translations, array $options, string $version): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'translations' => $translations,
            'options'      => $options,
            'version'      => $version,
        ]);

        try {
            $provider = self::set_provider($params['version']);
        } catch (Exception $exception) {
            return [[
                'error'          => 'Exception ' . $exception->getMessage(),
                'key'            => '',
                'translated_text' => '',
                'glossary_id'    => '',
            ]];
        }

        if ($provider === null) {
            return [[
                'error'          => 'Translation provider could not be initialised.',
                'key'            => '',
                'translated_text' => '',
                'glossary_id'    => '',
            ]];
        }

        $targetlang  = $params['options']['target_lang'];
        $glossaryid  = $params['options']['glossary_id'];

        // Build the options array to pass to the provider.
        // Remove meta-keys that are handled at this level.
        $provideroptions              = $params['options'];
        unset($provideroptions['target_lang']);
        unset($provideroptions['glossary_id']);
        $provideroptions['glossary']  = $glossaryid;

        // Group by source language so each chunk is homogeneous.
        $groupedtranslations = [];
        foreach ($params['translations'] as $translation) {
            $groupedtranslations[$translation['source_lang']][] = $translation;
        }

        $translatedtexts = [];
        foreach ($groupedtranslations as $sourcelang => $translationsgroup) {
            $staticparts = [$provideroptions, $sourcelang, $targetlang];
            $chunks      = self::chunk_payload($translationsgroup, $staticparts);
            foreach ($chunks as $chunk) {
                $translatedtexts = array_merge(
                    $translatedtexts,
                    self::process_chunk($provider, $chunk, $sourcelang, $targetlang, $provideroptions, $glossaryid)
                );
            }
        }
        return $translatedtexts;
    }

    /**
     * Processes a chunk of translations and returns translated results.
     *
     * @param translation_provider_interface $provider
     * @param array  $chunk
     * @param string $sourcelang
     * @param string $targetlang
     * @param array  $options
     * @param string $glossaryid
     * @return array
     */
    private static function process_chunk(
        translation_provider_interface $provider,
        array $chunk,
        string $sourcelang,
        string $targetlang,
        array $options,
        string $glossaryid
    ): array {
        $texts = array_map(fn($t) => $t['text'], $chunk);

        try {
            $results    = $provider->translate($texts, $sourcelang, $targetlang, $options);
            $translated = [];
            foreach ($results as $index => $result) {
                $translated[] = [
                    'key'            => $chunk[$index]['key'],
                    'translated_text' => $result->text,
                    'glossary_id'    => $glossaryid,
                    'error'          => '',
                ];
            }
            return $translated;
        } catch (Exception $e) {
            return [[
                'glossary_id'    => $glossaryid,
                'error'          => 'Exception ' . $e->getMessage(),
                'key'            => '',
                'translated_text' => '',
            ]];
        }
    }

    /**
     * Param validator.
     *
     * @return \core_external\external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'translations' => new external_multiple_structure(
                new external_single_structure([
                    'text'        => new external_value(PARAM_RAW, 'text to be translated'),
                    'source_lang' => new external_value(PARAM_ALPHA, 'source language'),
                    'key'         => new external_value(PARAM_RAW, 'UI identifier for the text'),
                ])
            ),
            'options' => new external_single_structure([
                'target_lang'          => new external_value(PARAM_RAW, 'target language'),
                'context'              => new external_value(PARAM_RAW, 'context of the text'),
                'tag_handling'         => new external_value(PARAM_ALPHA, 'html or xml'),
                'split_sentences'      => new external_value(PARAM_ALPHANUMEXT, '0,1 or nonewlines'),
                'preserve_formatting'  => new external_value(PARAM_BOOL, 'preserve formatting ?'),
                'formality'            => new external_value(
                    PARAM_ALPHAEXT,
                    'default, less, prefer_more, prefer_less or more'
                ),
                'outline_detection'    => new external_value(PARAM_BOOL, 'The automatic detection of the XML'),
                'non_splitting_tags'   => new external_value(
                    PARAM_RAW,
                    'Comma-separated list of XML tags which never split sentences'
                ),
                'splitting_tags'       => new external_value(
                    PARAM_RAW,
                    'Comma-separated list of XML tags which always cause splits.'
                ),
                'ignore_tags'          => new external_value(
                    PARAM_RAW,
                    'Comma-separated list of XML tags that indicate text not to be translated.'
                ),
                'glossary_id'          => new external_value(
                    PARAM_ALPHANUMEXT,
                    'Specify the glossary to use for the translation.'
                ),
                'model_type'           => new external_value(
                    PARAM_ALPHANUMEXT,
                    'Specifies which model should be used for translation.'
                ),
                'show_billed_characters' => new external_value(
                    PARAM_BOOL,
                    'Specifies whether the number of billed characters should be included in the response.'
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
                'key'            => new external_value(PARAM_RAW, 'UI identifier for the text'),
                'translated_text' => new external_value(PARAM_RAW, 'translated text'),
                'glossary_id'    => new external_value(PARAM_RAW, 'glossary id used'),
                'error'          => new external_value(PARAM_RAW, 'error message', VALUE_OPTIONAL),
            ])
        );
    }
}
