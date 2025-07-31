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
use DeepL\DeepLClient;
use DeepL\DeepLException;
use Exception;


/**
 * External service to call DeepL's translation API.
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_translation extends external_api {
    use deeplapi_trait;

    /**
     * External service to call DeepL's API.
     *
     * @param array $translations
     * @param array $options
     * @param string $version
     * @return array
     * @throws \DeepL\DeepLException
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     */
    public static function execute(array $translations, array $options, string $version): array {
        // Set the api with env so that it can be unit tested.
        $key = self::setdeeplapikey();
        $appinfo = self::setdeeplappinfo($version);
        if (empty($key)) {
            throw new DeepLException('authKey must be a non-empty string');
        }
        $params = self::validate_parameters(self::execute_parameters(),
                ['translations' => $translations, 'options' => $options, 'version' => $version]);
        try {
            $translator = new DeepLClient(
                    $key,
                    [
                            'send_platform_info' => true,
                            'app_info' => $appinfo,
                    ]
            );
        } catch (DeepLException $exception) {
            return [['error' => 'Exception ' . $exception->getMessage(),
                    'key' => '',
                    'translated_text' => '',
            ]];
        }

        $tragetlang = $params['options']['target_lang'];
        unset($params['options']['target_lang']);

        $groupedtranslations = [];
        foreach ($params['translations'] as $t) {
            $groupedtranslations[$t['source_lang']][] = $t;
        }

        $translatedtexts = [];
        foreach ($groupedtranslations as $sourcelang => $translations) {
            $texts = array_map(function($t) {
                return $t['text'];
            }, $translations);

            try {
                $results = $translator->translateText($texts, $sourcelang, $tragetlang, $params['options']);
                foreach ($results as $index => $result) {
                    $translatedtexts[] = [
                            'key' => $translations[$index]['key'],
                            'translated_text' => $result->text,
                            'glossary_id' => $params['options']['glossary_id'],
                            'error' => '',
                    ];
                }
            } catch (DeepLException $e) {
                return [[
                        'error' => 'Deepl exception ' . $e->getMessage(),
                        'key' => '',
                        'translated_text' => '',
                ]];
            } catch (Exception $e) {
                return [['error' => 'Exception ' . $e->getMessage(),
                        'key' => '',
                        'translated_text' => '',
                ]];
            }
        }
        return $translatedtexts;
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
                                ])),
                'options' => new external_single_structure(
                        [
                                'target_lang' => new external_value(PARAM_RAW, 'target language'),
                                'context' => new external_value(PARAM_RAW, 'context of the text'),
                                'tag_handling' => new external_value(PARAM_ALPHA, 'html or xml'),
                                'split_sentences' => new external_value(PARAM_ALPHANUMEXT, '0,1 or nonewlines'),
                                'preserve_formatting' => new external_value(PARAM_BOOL, 'preserve formatting ?'),
                                'formality' => new external_value(PARAM_ALPHAEXT,
                                        'default, less, prefer_more, prefer_less or more'),
                                'outline_detection' => new external_value(PARAM_BOOL, 'The automatic detection of the XML'),
                                'non_splitting_tags' => new external_value(PARAM_RAW,
                                        'Comma-separated list of XML tags which never split sentences'),
                                'splitting_tags' => new external_value(PARAM_RAW,
                                        'Comma-separated list of XML tags which always cause splits.'),
                                'ignore_tags' => new external_value(PARAM_RAW,
                                        'Comma-separated list of XML tags that indicate text not to be translated.'),
                                'glossary_id' => new external_value(PARAM_ALPHANUMEXT,
                                        'Specify the glossary to use for the translation.'),
                                'model_type' => new external_value(PARAM_ALPHANUMEXT,
                                        'Specifies which DeepL model should be used for translation.'),
                                'show_billed_characters' => new external_value(PARAM_BOOL,
                                        'Specifies whether the number of billed characters should be included in the response.'),
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
