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
use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use DeepL\DeepLException;
use DeepL\DocumentHandle;
use local_deepler\local\services\activity_duplicator;
use local_deepler\local\services\document_translator;
use moodle_url;

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
            return [
                'jobid' => $params['jobid'],
                'status' => 'error',
                'newcmid' => 0,
                'newcmurl' => '',
                'newname' => '',
                'restricted' => false,
                'warning' => '',
                'error' => 'Job record not found.',
            ];
        }

        // Validate course context and capability.
        $context = context_course::instance($job->courseid);
        self::validate_context($context);
        require_capability('local/deepler:translatefiles', $context);

        // If already completed and duplicated, return existing info.
        if ($job->status === 'done' && ($job->newcmid > 0 || $job->newfileid > 0)) {
            $url = '';
            $name = '';
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
                'restricted' => true,
                'warning' => '',
                'error' => '',
            ];
        }

        // DeepL client.
        $client = self::setdeeplapikey($params['version']);
        if (!$client) {
            return [
                'jobid' => (int)$job->id,
                'status' => 'error',
                'newcmid' => 0,
                'newcmurl' => '',
                'newname' => '',
                'restricted' => false,
                'warning' => '',
                'error' => 'Could not initialize DeepL client.',
            ];
        }

        $fs = get_file_storage();
        $originalfile = $fs->get_file_by_id($job->fileid);
        if (!$originalfile) {
            return [
                'jobid' => (int)$job->id,
                'status' => 'error',
                'newcmid' => 0,
                'newcmurl' => '',
                'newname' => '',
                'restricted' => false,
                'warning' => '',
                'error' => 'Original stored file not found in file storage.',
            ];
        }

        $translator = new document_translator($client);
        $handle = new DocumentHandle($job->documentid, $job->documentkey);

        $tempdir = make_request_directory();
        $temppath = $tempdir . '/' . $originalfile->get_filename();

        try {
            $translator->download($handle, $temppath);
        } catch (DeepLException $e) {
            $job->status = 'error';
            $job->errormessage = $e->getMessage();
            $job->timemodified = time();
            $DB->update_record('local_deepler_files', $job);

            return [
                'jobid' => (int)$job->id,
                'status' => 'error',
                'newcmid' => 0,
                'newcmurl' => '',
                'newname' => '',
                'restricted' => false,
                'warning' => '',
                'error' => 'Failed to download translated document: ' . $e->getMessage(),
            ];
        }

        $duplicator = new activity_duplicator($params['version']);
        $newcmid = 0;
        $newfileid = 0;
        $newcmurl = '';
        $newname = '';
        $restricted = false;
        $warning = '';

        try {
            if ($job->cmid > 0) {
                $modinfo = get_fast_modinfo($job->courseid);
                $cm = $modinfo->cms[$job->cmid] ?? null;
                if (!$cm) {
                    throw new \moodle_exception('Course module ' . $job->cmid . ' not found.');
                }

                // Check if this is a folder and another file in this folder was already duplicated for this targetlang.
                $existingfolderjob = $DB->get_record_select(
                    'local_deepler_files',
                    "courseid = ? AND cmid = ? AND targetlang = ? AND status = 'done' AND newcmid > 0 AND id != ?",
                    [$job->courseid, $job->cmid, $job->targetlang, $job->id]
                );

                if ($existingfolderjob && $cm->modname === 'folder') {
                    $newcmid = (int)$existingfolderjob->newcmid;
                    $dupcontext = context_module::instance($newcmid);

                    $newfilename = $duplicator->get_translated_filename($originalfile->get_filename(), $job->targetlang);

                    // Remove original file copy in the existing duplicated folder.
                    $dupfile = $fs->get_file(
                        $dupcontext->id,
                        $originalfile->get_component(),
                        $originalfile->get_filearea(),
                        $originalfile->get_itemid(),
                        $originalfile->get_filepath(),
                        $originalfile->get_filename()
                    );
                    if ($dupfile) {
                        $dupfile->delete();
                    }

                    $existingnewfile = $fs->get_file(
                        $dupcontext->id,
                        $originalfile->get_component(),
                        $originalfile->get_filearea(),
                        $originalfile->get_itemid(),
                        $originalfile->get_filepath(),
                        $newfilename
                    );
                    if ($existingnewfile) {
                        $existingnewfile->delete();
                    }

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
                    $newfileid = (int)$newstoredfile->get_id();

                    $refreshedmodinfo = get_fast_modinfo($job->courseid, true);
                    $newcminfo = $refreshedmodinfo->cms[$newcmid] ?? null;
                    $newname = $newcminfo ? $newcminfo->name : $cm->name;
                    $newcmurl = (new moodle_url('/mod/folder/view.php', ['id' => $newcmid]))->out(false);
                    $restricted = true;
                } else {
                    [$newcminfo, $newstoredfile, $restricted, $warning] = $duplicator->duplicate_with_file(
                        $cm,
                        $temppath,
                        $originalfile,
                        $job->targetlang,
                        $job->sourcelang
                    );
                    $newcmid = (int)$newcminfo->id;
                    $newfileid = (int)$newstoredfile->get_id();
                    $newname = $newcminfo->name;
                    $newcmurl = (new moodle_url('/mod/' . $newcminfo->modname . '/view.php', ['id' => $newcmid]))->out(false);
                }
            } else {
                // Course level file.
                $newstoredfile = $duplicator->store_course_file($originalfile, $temppath, $job->targetlang);
                $newfileid = (int)$newstoredfile->get_id();
                $newname = $newstoredfile->get_filename();
                $newcmurl = moodle_url::make_pluginfile_url(
                    $newstoredfile->get_contextid(),
                    $newstoredfile->get_component(),
                    $newstoredfile->get_filearea(),
                    $newstoredfile->get_itemid(),
                    $newstoredfile->get_filepath(),
                    $newstoredfile->get_filename()
                )->out(false);
            }
        } catch (\Throwable $e) {
            $job->status = 'error';
            $job->errormessage = $e->getMessage();
            $job->timemodified = time();
            $DB->update_record('local_deepler_files', $job);

            return [
                'jobid' => (int)$job->id,
                'status' => 'error',
                'newcmid' => 0,
                'newcmurl' => '',
                'newname' => '',
                'restricted' => false,
                'warning' => '',
                'error' => 'Duplication failed: ' . $e->getMessage(),
            ];
        } finally {
            if (file_exists($temppath)) {
                @unlink($temppath);
            }
        }

        // Update job record.
        $job->status = 'done';
        $job->newcmid = $newcmid;
        $job->newfileid = $newfileid;
        $job->timemodified = time();
        $DB->update_record('local_deepler_files', $job);

        return [
            'jobid' => (int)$job->id,
            'status' => 'done',
            'newcmid' => $newcmid,
            'newcmurl' => $newcmurl,
            'newname' => $newname,
            'restricted' => $restricted,
            'warning' => $warning,
            'error' => '',
        ];
    }
}
