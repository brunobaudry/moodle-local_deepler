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

use context_course;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use DeepL\DeepLException;
use DeepL\DocumentHandle;
use local_deepler\local\services\document_translator;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/local/deepler/classes/vendor/autoload.php');

/**
 * External function to get the status of a DeepL document translation job.
 *
 * @package    local_deepler
 * @copyright  2026 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_document_status extends external_api {
    use deeplapi_trait;

    /**
     * Parameter description for get_document_status.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'jobid' => new external_value(PARAM_INT, 'Job ID in local_deepler_files'),
            'version' => new external_value(PARAM_TEXT, 'Plugin release version'),
        ]);
    }

    /**
     * Return structure for get_document_status.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'jobid' => new external_value(PARAM_INT, 'Job ID'),
            'status' => new external_value(PARAM_TEXT, 'Status (queued, translating, done, error)'),
            'secondsremaining' => new external_value(PARAM_INT, 'Estimated seconds remaining', VALUE_DEFAULT, 0),
            'billedcharacters' => new external_value(PARAM_INT, 'Billed characters from DeepL', VALUE_DEFAULT, 0),
            'error' => new external_value(PARAM_TEXT, 'Error message if failed', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Execute status check.
     *
     * @param int $jobid
     * @param string $version
     * @return array
     */
    public static function execute(int $jobid, string $version): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'jobid' => $jobid,
            'version' => $version,
        ]);

        $job = $DB->get_record('local_deepler_files', ['id' => $params['jobid']]);
        if (!$job) {
            return [
                'jobid' => $params['jobid'],
                'status' => 'error',
                'secondsremaining' => 0,
                'billedcharacters' => 0,
                'error' => 'Translation job record not found.',
            ];
        }

        // Validate course context and capability.
        $context = context_course::instance($job->courseid);
        self::validate_context($context);
        require_capability('local/deepler:translatefiles', $context);

        if ($job->status === 'done') {
            return [
                'jobid' => (int)$job->id,
                'status' => 'done',
                'secondsremaining' => 0,
                'billedcharacters' => (int)$job->billedcharacters,
                'error' => '',
            ];
        }

        // DeepL client.
        $client = self::setdeeplapikey($params['version']);
        if (!$client) {
            return [
                'jobid' => (int)$job->id,
                'status' => 'error',
                'secondsremaining' => 0,
                'billedcharacters' => 0,
                'error' => 'Could not initialize DeepL client.',
            ];
        }

        $translator = new document_translator($client);
        $handle = new DocumentHandle($job->documentid, $job->documentkey);

        try {
            $docstatus = $translator->status($handle);
        } catch (DeepLException $e) {
            $job->status = 'error';
            $job->errormessage = $e->getMessage();
            $job->timemodified = time();
            $DB->update_record('local_deepler_files', $job);

            return [
                'jobid' => (int)$job->id,
                'status' => 'error',
                'secondsremaining' => 0,
                'billedcharacters' => 0,
                'error' => $e->getMessage(),
            ];
        }

        if ($docstatus->done()) {
            $job->status = 'done';
            $job->billedcharacters = $docstatus->billedCharacters ?? 0;
            $job->timemodified = time();
            $DB->update_record('local_deepler_files', $job);

            return [
                'jobid' => (int)$job->id,
                'status' => 'done',
                'secondsremaining' => 0,
                'billedcharacters' => (int)$job->billedcharacters,
                'error' => '',
            ];
        }

        if (!$docstatus->ok()) {
            $errmsg = $docstatus->errorMessage ?? 'Document translation failed in DeepL.';
            $job->status = 'error';
            $job->errormessage = $errmsg;
            $job->timemodified = time();
            $DB->update_record('local_deepler_files', $job);

            return [
                'jobid' => (int)$job->id,
                'status' => 'error',
                'secondsremaining' => 0,
                'billedcharacters' => 0,
                'error' => $errmsg,
            ];
        }

        return [
            'jobid' => (int)$job->id,
            'status' => 'translating',
            'secondsremaining' => (int)($docstatus->secondsRemaining ?? 0),
            'billedcharacters' => 0,
            'error' => '',
        ];
    }
}
