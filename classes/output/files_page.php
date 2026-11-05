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

namespace local_deepler\output;

use local_deepler\local\services\file_scanner;
use local_deepler\local\services\lang_helper;
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Renderable for the Deepler Files page.
 *
 * @package    local_deepler
 * @copyright  2026 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class files_page implements renderable, templatable {
    /** @var stdClass Course record. */
    protected stdClass $course;

    /** @var array Array of translatable_file DTOs. */
    protected array $files;

    /** @var lang_helper Language helper service. */
    protected lang_helper $langpack;

    /** @var string Plugin release version. */
    protected string $version;

    /**
     * Constructor.
     *
     * @param stdClass $course
     * @param array $files
     * @param lang_helper $langpack
     * @param string $version
     */
    public function __construct(stdClass $course, array $files, lang_helper $langpack, string $version) {
        $this->course = $course;
        $this->files = $files;
        $this->langpack = $langpack;
        $this->version = $version;
    }

    /**
     * Export data for Mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = new stdClass();
        $data->courseid = (int)$this->course->id;
        $data->coursename = format_string($this->course->fullname);
        $data->hasfiles = !empty($this->files);
        $data->str_empty = get_string('files:empty', 'local_deepler');
        $data->str_selectall = get_string('files:selectall', 'local_deepler');
        $data->str_translateselected = get_string('files:translateselected', 'local_deepler');
        $data->str_title = get_string('files:title', 'local_deepler');

        // Target languages options (limited to installed Moodle languages).
        $targetlanguages = $this->langpack->preparetargetsoptionlangs();
        $targetlangsformatted = [];
        $defaulttarget = '';
        foreach ($targetlanguages as $idx => $tlang) {
            $rawcode = $tlang['code'];
            // Clean rephrase symbol if present.
            $cleancode = str_replace(lang_helper::REPHRASESYMBOL, '', $rawcode);
            $code = strtolower($cleancode);
            if ($defaulttarget === '') {
                $defaulttarget = $code;
            }
            $targetlangsformatted[] = [
                'code' => $code,
                'name' => $tlang['verbose'] . ' (' . strtoupper($cleancode) . ')',
                'selected' => ($idx === 0),
            ];
        }
        $data->targetlangs = $targetlangsformatted;
        $data->defaulttarget = $defaulttarget;

        // Group files by section.
        $sections = [];
        foreach ($this->files as $tfile) {
            $secid = $tfile->sectionid;
            if (!isset($sections[$secid])) {
                $sections[$secid] = [
                    'sectionid' => $secid,
                    'sectionnum' => $tfile->sectionnum,
                    'sectionname' => $tfile->sectionname ?: get_string('files:courselevel', 'local_deepler'),
                    'rows' => [],
                ];
            }

            // Prepare row data.
            $file = $tfile->file;
            $cm = $tfile->cm;
            $fileurl = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            )->out(false);

            $icon = $output->pix_icon(file_file_icon($file), $file->get_filename(), 'moodle', ['class' => 'icon']);

            $activityname = $cm ? format_string($cm->name) : get_string('files:courselevel', 'local_deepler');
            $activityicon = $cm ? $output->pix_icon('monologo', $cm->modname, 'mod_' . $cm->modname, ['class' => 'icon mr-1']) : '';

            // Format reason if unsupported.
            $reasondesc = '';
            if (!$tfile->supported) {
                if ($tfile->reason === 'unsupported_extension') {
                    $ext = pathinfo($file->get_filename(), PATHINFO_EXTENSION);
                    $reasondesc = get_string('files:reason_extension', 'local_deepler', $ext);
                } else if ($tfile->reason === 'file_too_big') {
                    $reasondesc = get_string('files:reason_filesize', 'local_deepler', display_size($file->get_filesize()));
                }
            }

            // Format existing jobs / translations.
            $existingtranslations = [];
            $hascompletedduplicate = false;
            $newcmurl = '';
            foreach ($tfile->jobs as $job) {
                $existingtranslations[] = [
                    'targetlang' => strtoupper($job->targetlang),
                    'status' => $job->status,
                    'newcmid' => (int)$job->newcmid,
                    'newfileid' => (int)$job->newfileid,
                ];
                if ($job->status === 'done' && $job->newcmid > 0) {
                    $hascompletedduplicate = true;
                    $newcmurl = (new moodle_url('/mod/' . ($cm ? $cm->modname : 'resource') . '/view.php', ['id' => $job->newcmid]))->out(false);
                }
            }

            $sourcelang = $this->langpack->getcurrentlang(true);

            $sections[$secid]['rows'][] = [
                'fileid' => (int)$file->get_id(),
                'cmid' => $cm ? (int)$cm->id : 0,
                'filename' => $file->get_filename(),
                'filesize' => display_size($file->get_filesize()),
                'fileurl' => $fileurl,
                'fileicon' => $icon,
                'activityname' => $activityname,
                'activityicon' => $activityicon,
                'supported' => $tfile->supported,
                'reason' => $reasondesc,
                'sourcelang' => strtoupper($sourcelang),
                'existingtranslations' => $existingtranslations,
                'hascompletedduplicate' => $hascompletedduplicate,
                'newcmurl' => $newcmurl,
                'str_openactivity' => get_string('files:openactivity', 'local_deepler'),
            ];
        }

        $data->sections = array_values($sections);
        return $data;
    }
}
