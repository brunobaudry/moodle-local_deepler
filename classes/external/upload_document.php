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

use context;
use context_course;
use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use DeepL\DeepLException;
use local_deepler\local\services\document_translator;
use local_deepler\local\services\file_scanner;
use local_deepler\local\services\lang_helper;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/local/deepler/classes/vendor/autoload.php');

/**
 * External function to upload a course document to DeepL for translation.
 *
 * @package    local_deepler
 * @copyright  2026 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upload_document extends external_api {
    use deeplapi_trait;

    /**
     * Parameter description for upload_document.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'fileid' => new external_value(PARAM_INT, 'Stored file ID'),
            'sourcelang' => new external_value(PARAM_RAW, 'Source language code', VALUE_DEFAULT, ''),
            'targetlang' => new external_value(PARAM_RAW, 'Target language code'),
            'options' => new external_single_structure([
                'formality' => new external_value(PARAM_RAW, 'Formality preference', VALUE_DEFAULT, ''),
                'glossary_id' => new external_value(PARAM_RAW, 'Glossary ID', VALUE_DEFAULT, ''),
                'enable_document_minification' => new external_value(PARAM_BOOL, 'Enable document minification', VALUE_DEFAULT, false),
            ], 'Translation options', VALUE_DEFAULT, []),
            'version' => new external_value(PARAM_TEXT, 'Plugin release version'),
        ]);
    }

    /**
     * Return structure for upload_document.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'jobid' => new external_value(PARAM_INT, 'Created job ID in local_deepler_files'),
            'status' => new external_value(PARAM_TEXT, 'Status of translation job'),
            'error' => new external_value(PARAM_TEXT, 'Error message if failed', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Execute document upload.
     *
     * @param int $fileid
     * @param string $sourcelang
     * @param string $targetlang
     * @param array $options
     * @param string $version
     * @return array
     */
    public static function execute(
        int $fileid,
        string $sourcelang,
        string $targetlang,
        array $options,
        string $version
    ): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'fileid' => $fileid,
            'sourcelang' => $sourcelang,
            'targetlang' => $targetlang,
            'options' => $options,
            'version' => $version,
        ]);

        $fs = get_file_storage();
        $file = $fs->get_file_by_id($params['fileid']);
        if (!$file) {
            return [
                'jobid' => 0,
                'status' => 'error',
                'error' => 'File not found.',
            ];
        }

        // Validate context and capability.
        $context = context::instance_by_id($file->get_contextid());
        $courseid = 0;
        $cmid = 0;
        if ($context->contextlevel == CONTEXT_COURSE) {
            $courseid = $context->instanceid;
            self::validate_context($context);
            require_capability('local/deepler:translatefiles', $context);
        } else if ($context->contextlevel == CONTEXT_MODULE) {
            $cmid = $context->instanceid;
            $cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
            $courseid = (int)$cm->course;
            $coursecontext = context_course::instance($courseid);
            self::validate_context($coursecontext);
            require_capability('local/deepler:translatefiles', $coursecontext);
        } else {
            return [
                'jobid' => 0,
                'status' => 'error',
                'error' => 'Invalid file context for course translation.',
            ];
        }

        // Check if supported.
        $configmax = (int)get_config('local_deepler', 'filemaxbytes') ?: 10485760;
        if (!file_scanner::is_supported($file, $configmax)) {
            $reason = file_scanner::get_unsupported_reason($file, $configmax);
            return [
                'jobid' => 0,
                'status' => 'error',
                'error' => 'File is not supported: ' . $reason,
            ];
        }

        // Initialize DeepL client.
        $client = self::setdeeplapikey($params['version']);
        if (!$client) {
            return [
                'jobid' => 0,
                'status' => 'error',
                'error' => 'Could not initialize DeepL client. Check API key configuration.',
            ];
        }

        // Check usage quota limit.
        try {
            $usage = $client->getUsage();
            if ($usage && $usage->anyLimitReached()) {
                return [
                    'jobid' => 0,
                    'status' => 'error',
                    'error' => 'DeepL API usage limit reached for this month.',
                ];
            }
        } catch (\Throwable $e) {
            // Ignore usage check exceptions if quota check fails.
        }

        $translator = new document_translator($client);
        $deeploptions = [];
        if (!empty($params['options']['formality'])) {
            $deeploptions['formality'] = $params['options']['formality'];
        }
        if (!empty($params['options']['glossary_id'])) {
            $deeploptions['glossary'] = $params['options']['glossary_id'];
        }
        if (!empty($params['options']['enable_document_minification'])) {
            $deeploptions['enable_document_minification'] = true;
        }

        $effective_source = !empty($params['sourcelang']) ? $params['sourcelang'] : null;

        try {
            $handle = $translator->upload($file, $effective_source, $params['targetlang'], $deeploptions);
        } catch (DeepLException $e) {
            return [
                'jobid' => 0,
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }

        // Record job in local_deepler_files.
        $now = time();
        $record = (object)[
            'courseid' => $courseid,
            'cmid' => $cmid,
            'fileid' => (int)$file->get_id(),
            'contenthash' => $file->get_contenthash(),
            'sourcelang' => $params['sourcelang'],
            'targetlang' => $params['targetlang'],
            'status' => 'translating',
            'documentid' => $handle->documentId,
            'documentkey' => $handle->documentKey,
            'newcmid' => 0,
            'newfileid' => 0,
            'billedcharacters' => 0,
            'errormessage' => null,
            'userid' => (int)$USER->id,
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        // Check if an existing row for this file + targetlang exists.
        $existing = $DB->get_record('local_deepler_files', [
            'fileid' => (int)$file->get_id(),
            'targetlang' => $params['targetlang'],
        ]);

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('local_deepler_files', $record);
            $jobid = $existing->id;
        } else {
            $jobid = $DB->insert_record('local_deepler_files', $record);
        }

        return [
            'jobid' => (int)$jobid,
            'status' => 'translating',
            'error' => '',
        ];
    }
}
