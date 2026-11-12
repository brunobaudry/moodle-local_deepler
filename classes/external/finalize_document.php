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

use cm_info;
use context_course;
use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use DeepL\DeepLException;
use DeepL\DocumentHandle;
use local_deepler\local\services\activity_duplicator;
use local_deepler\local\services\document_translator;
use moodle_exception;
use moodle_url;
use stdClass;
use stored_file;
use Throwable;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/local/deepler/classes/vendor/autoload.php');

/**
 * External function to finalize a translated document by downloading it and creating duplicated activities.
 *
 * @package    local_deepler
 * @copyright  2026 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class finalize_document extends external_api {
    use deeplapi_trait;

    /**
     * Parameter description for finalize_document.
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
     * Return structure for finalize_document.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'jobid' => new external_value(PARAM_INT, 'Job ID'),
            'status' => new external_value(PARAM_TEXT, 'Final status (done, error)'),
            'newcmid' => new external_value(PARAM_INT, 'Created course module ID (0 for course files)', VALUE_DEFAULT, 0),
            'newcmurl' => new external_value(PARAM_TEXT, 'URL to the duplicated activity or file', VALUE_DEFAULT, ''),
            'newname' => new external_value(PARAM_TEXT, 'Translated name', VALUE_DEFAULT, ''),
            'filename' => new external_value(PARAM_TEXT, 'Translated filename', VALUE_DEFAULT, ''),
            'restricted' => new external_value(PARAM_BOOL, 'Whether language restriction was applied', VALUE_DEFAULT, false),
            'warning' => new external_value(PARAM_TEXT, 'Warning message if applicable', VALUE_DEFAULT, ''),
            'error' => new external_value(PARAM_TEXT, 'Error message if failed', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Execute finalization.
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
            return self::build_error_response($params['jobid'], 'Job record not found.');
        }

        // Validate course context and capability.
        $context = context_course::instance($job->courseid);
        self::validate_context($context);
        require_capability('local/deepler:translatefiles', $context);

        // If already completed and duplicated, return existing info.
        if (self::is_job_already_completed($job)) {
            return self::build_completed_job_response($job);
        }

        // DeepL client.
        $client = self::setdeeplapikey($params['version']);
        if (!$client) {
            return self::build_error_response((int)$job->id, 'Could not initialize DeepL client.');
        }

        $fs = get_file_storage();
        $originalfile = $fs->get_file_by_id($job->fileid);
        if (!$originalfile) {
            return self::build_error_response((int)$job->id, 'Original stored file not found in file storage.');
        }

        $translator = new document_translator($client);
        $tempdir = make_request_directory();
        $temppath = $tempdir . '/' . $originalfile->get_filename();

        $downloaderror = self::download_translated_document($translator, $job, $temppath);
        if ($downloaderror !== null) {
            return self::build_error_response((int)$job->id, 'Failed to download translated document: ' . $downloaderror);
        }

        try {
            $result = self::duplicate_job_resource($job, $originalfile, $temppath, $params['version']);
        } catch (Throwable $e) {
            return self::record_and_build_duplication_error($job, $e->getMessage());
        } finally {
            self::cleanup_temp_file($temppath);
        }

        self::mark_job_done($job, $result['newcmid'], $result['newfileid']);

        return [
            'jobid' => (int)$job->id,
            'status' => 'done',
            'newcmid' => $result['newcmid'],
            'newcmurl' => $result['newcmurl'],
            'newname' => $result['newname'],
            'filename' => $result['filename'],
            'restricted' => $result['restricted'],
            'warning' => $result['warning'],
            'error' => '',
        ];
    }

    /**
     * Build standard error response array.
     *
     * @param int $jobid
     * @param string $error
     * @return array
     */
    private static function build_error_response(int $jobid, string $error): array {
        return [
            'jobid' => $jobid,
            'status' => 'error',
            'newcmid' => 0,
            'newcmurl' => '',
            'newname' => '',
            'filename' => '',
            'restricted' => false,
            'warning' => '',
            'error' => $error,
        ];
    }

    /**
     * Check if the job has already been completed.
     *
     * @param stdClass $job
     * @return bool
     */
    private static function is_job_already_completed(stdClass $job): bool {
        return $job->status === 'done' && ($job->newcmid > 0 || $job->newfileid > 0);
    }

    /**
     * Build response for an already completed job.
     *
     * @param stdClass $job
     * @return array
     */
    private static function build_completed_job_response(stdClass $job): array {
        $url = '';
        $name = '';
        $filename = '';

        if ($job->newcmid > 0) {
            $modinfo = get_fast_modinfo($job->courseid);
            $newcm = $modinfo->cms[$job->newcmid] ?? null;
            if ($newcm) {
                $name = $newcm->name;
                $url = (new moodle_url('/mod/' . $newcm->modname . '/view.php', ['id' => $newcm->id]))->out(false);
            }
        } else if ($job->newfileid > 0) {
            $fs = get_file_storage();
            $newfile = $fs->get_file_by_id($job->newfileid);
            if ($newfile) {
                $name = $newfile->get_filename();
                $filename = $newfile->get_filename();
                $url = moodle_url::make_pluginfile_url(
                    $newfile->get_contextid(),
                    $newfile->get_component(),
                    $newfile->get_filearea(),
                    $newfile->get_itemid(),
                    $newfile->get_filepath(),
                    $newfile->get_filename()
                )->out(false);
            }
        }

        return [
            'jobid' => (int)$job->id,
            'status' => 'done',
            'newcmid' => (int)$job->newcmid,
            'newcmurl' => $url,
            'newname' => $name,
            'filename' => $filename,
            'restricted' => true,
            'warning' => '',
            'error' => '',
        ];
    }

    /**
     * Download the translated document from DeepL into the specified temporary path.
     *
     * @param document_translator $translator
     * @param stdClass $job
     * @param string $temppath
     * @return string|null Error message on failure, or null on success
     */
    private static function download_translated_document(
        document_translator $translator,
        stdClass $job,
        string $temppath
    ): ?string {
        global $DB;

        $handle = new DocumentHandle($job->documentid, $job->documentkey);
        try {
            $translator->download($handle, $temppath);
            return null;
        } catch (DeepLException $e) {
            $job->status = 'error';
            $job->errormessage = $e->getMessage();
            $job->timemodified = time();
            $DB->update_record('local_deepler_files', $job);
            return $e->getMessage();
        }
    }

    /**
     * Duplicate either module-level or course-level resource for the given job.
     *
     * @param stdClass $job
     * @param stored_file $originalfile
     * @param string $temppath
     * @param string $version
     * @return array
     */
    private static function duplicate_job_resource(
        stdClass $job,
        stored_file $originalfile,
        string $temppath,
        string $version
    ): array {
        $duplicator = new activity_duplicator($version);
        if ($job->cmid > 0) {
            return self::duplicate_module_resource($job, $originalfile, $temppath, $duplicator);
        }
        return self::duplicate_course_resource($job, $originalfile, $temppath, $duplicator);
    }

    /**
     * Duplicate a course-level file.
     *
     * @param stdClass $job
     * @param stored_file $originalfile
     * @param string $temppath
     * @param activity_duplicator $duplicator
     * @return array
     */
    private static function duplicate_course_resource(
        stdClass $job,
        stored_file $originalfile,
        string $temppath,
        activity_duplicator $duplicator
    ): array {
        $newstoredfile = $duplicator->store_course_file($originalfile, $temppath, $job->targetlang);
        $newcmurl = moodle_url::make_pluginfile_url(
            $newstoredfile->get_contextid(),
            $newstoredfile->get_component(),
            $newstoredfile->get_filearea(),
            $newstoredfile->get_itemid(),
            $newstoredfile->get_filepath(),
            $newstoredfile->get_filename()
        )->out(false);

        return [
            'newcmid' => 0,
            'newfileid' => (int)$newstoredfile->get_id(),
            'newcmurl' => $newcmurl,
            'newname' => $newstoredfile->get_filename(),
            'filename' => $newstoredfile->get_filename(),
            'restricted' => false,
            'warning' => '',
        ];
    }

    /**
     * Duplicate a course module resource.
     *
     * @param stdClass $job
     * @param stored_file $originalfile
     * @param string $temppath
     * @param activity_duplicator $duplicator
     * @return array
     */
    private static function duplicate_module_resource(
        stdClass $job,
        stored_file $originalfile,
        string $temppath,
        activity_duplicator $duplicator
    ): array {
        global $DB;

        $modinfo = get_fast_modinfo($job->courseid);
        $cm = $modinfo->cms[$job->cmid] ?? null;
        if (!$cm) {
            throw new moodle_exception('Course module ' . $job->cmid . ' not found.');
        }

        $existingfolderjob = $DB->get_record_select(
            'local_deepler_files',
            "courseid = ? AND cmid = ? AND targetlang = ? AND status = 'done' AND newcmid > 0 AND id != ?",
            [$job->courseid, $job->cmid, $job->targetlang, $job->id]
        );

        if ($existingfolderjob && $cm->modname === 'folder') {
            return self::duplicate_folder_file(
                $job,
                $originalfile,
                $temppath,
                $duplicator,
                $cm,
                (int)$existingfolderjob->newcmid
            );
        }

        [$newcminfo, $newstoredfile, $restricted, $warning] = $duplicator->duplicate_with_file(
            $cm,
            $temppath,
            $originalfile,
            $job->targetlang,
            $job->sourcelang
        );

        return [
            'newcmid' => (int)$newcminfo->id,
            'newfileid' => (int)$newstoredfile->get_id(),
            'newcmurl' => (new moodle_url('/mod/' . $newcminfo->modname . '/view.php', ['id' => $newcminfo->id]))->out(false),
            'newname' => $newcminfo->name,
            'filename' => $newstoredfile->get_filename(),
            'restricted' => $restricted,
            'warning' => $warning,
        ];
    }

    /**
     * Add translated file into an existing duplicated folder activity.
     *
     * @param stdClass $job
     * @param stored_file $originalfile
     * @param string $temppath
     * @param activity_duplicator $duplicator
     * @param cm_info $cm
     * @param int $newcmid
     * @return array
     */
    private static function duplicate_folder_file(
        stdClass $job,
        stored_file $originalfile,
        string $temppath,
        activity_duplicator $duplicator,
        cm_info $cm,
        int $newcmid
    ): array {
        $fs = get_file_storage();
        $dupcontext = context_module::instance($newcmid);
        $newfilename = $duplicator->get_translated_filename($originalfile->get_filename(), $job->targetlang);

        self::delete_folder_file_if_exists($fs, $dupcontext->id, $originalfile, $originalfile->get_filename());
        self::delete_folder_file_if_exists($fs, $dupcontext->id, $originalfile, $newfilename);

        $filerecord = [
            'contextid' => $dupcontext->id,
            'component' => $originalfile->get_component(),
            'filearea' => $originalfile->get_filearea(),
            'itemid' => $originalfile->get_itemid(),
            'filepath' => $originalfile->get_filepath(),
            'filename' => $newfilename,
            'userid' => $originalfile->get_userid(),
        ];
        $newstoredfile = $fs->create_file_from_pathname($filerecord, $temppath);

        $refreshedmodinfo = get_fast_modinfo($job->courseid, true);
        $newcminfo = $refreshedmodinfo->cms[$newcmid] ?? null;
        $newname = $newcminfo ? $newcminfo->name : $cm->name;

        return [
            'newcmid' => $newcmid,
            'newfileid' => (int)$newstoredfile->get_id(),
            'newcmurl' => (new moodle_url('/mod/folder/view.php', ['id' => $newcmid]))->out(false),
            'newname' => $newname,
            'filename' => $newstoredfile->get_filename(),
            'restricted' => true,
            'warning' => '',
        ];
    }

    /**
     * Delete file in duplicated folder context if it already exists.
     *
     * @param \file_storage $fs
     * @param int $contextid
     * @param stored_file $originalfile
     * @param string $filename
     */
    private static function delete_folder_file_if_exists(
        \file_storage $fs,
        int $contextid,
        stored_file $originalfile,
        string $filename
    ): void {
        $file = $fs->get_file(
            $contextid,
            $originalfile->get_component(),
            $originalfile->get_filearea(),
            $originalfile->get_itemid(),
            $originalfile->get_filepath(),
            $filename
        );
        if ($file) {
            $file->delete();
        }
    }

    /**
     * Record failure and return duplication error response.
     *
     * @param stdClass $job
     * @param string $message
     * @return array
     */
    private static function record_and_build_duplication_error(stdClass $job, string $message): array {
        global $DB;

        $job->status = 'error';
        $job->errormessage = $message;
        $job->timemodified = time();
        $DB->update_record('local_deepler_files', $job);

        return self::build_error_response((int)$job->id, 'Duplication failed: ' . $message);
    }

    /**
     * Mark job as done and update DB record.
     *
     * @param stdClass $job
     * @param int $newcmid
     * @param int $newfileid
     */
    private static function mark_job_done(stdClass $job, int $newcmid, int $newfileid): void {
        global $DB;

        $job->status = 'done';
        $job->newcmid = $newcmid;
        $job->newfileid = $newfileid;
        $job->timemodified = time();
        $DB->update_record('local_deepler_files', $job);
    }

    /**
     * Remove temporary file safely.
     *
     * @param string $temppath
     */
    private static function cleanup_temp_file(string $temppath): void {
        if (file_exists($temppath)) {
            @unlink($temppath);
        }
    }
}
