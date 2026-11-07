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

use local_deepler\local\data\multilanger;
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

    /** @var mixed Multilang filter. */
    protected mixed $mlangfilter;

    /**
     * Constructor.
     *
     * @param stdClass $course
     * @param array $files
     * @param lang_helper $langpack
     * @param string $version
     * @param mixed $mlangfilter
     */
    public function __construct(
        stdClass $course,
        array $files,
        lang_helper $langpack,
        string $version,
        mixed $mlangfilter = null
    ) {
        $this->course = $course;
        $this->files = $files;
        $this->langpack = $langpack;
        $this->version = $version;
        $this->mlangfilter = $mlangfilter;
    }

    /**
     * Filter text through multilang filter and strip raw {mlang} tags if any remain.
     *
     * @param string $text
     * @return string
     */
    public function filter_multilang_text(string $text): string {
        if ($this->mlangfilter && method_exists($this->mlangfilter, 'filter')) {
            $text = $this->mlangfilter->filter($text);
        }

        // If {mlang} tags still present, parse with multilanger and extract matching or fallback string.
        if (str_contains($text, '{mlang')) {
            $ml = new multilanger($text);
            $langs = $ml->findmlangs_withouttags();
            $currlang = $this->langpack->getcurrentlang(true);
            if (isset($langs[$currlang])) {
                $text = $langs[$currlang];
            } else if (isset($langs['other'])) {
                $text = $langs['other'];
            } else if (!empty($langs)) {
                $text = reset($langs);
            }
        }

        return $text;
    }

    /**
     * Export data for Mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        global $PAGE;

        $data = new stdClass();
        $data->courseid = (int)$this->course->id;
        $data->coursename = $this->filter_multilang_text(format_string($this->course->fullname));
        $data->hasfiles = !empty($this->files);
        $data->version = $this->version;

        $data->str_empty = get_string('files:empty', 'local_deepler');
        $data->str_selectall = get_string('files:selectall', 'local_deepler');
        $data->str_translateselected = get_string('files:translateselected', 'local_deepler');
        $data->str_title = get_string('files:title', 'local_deepler');
        $data->str_customsettings = get_string('files:customsettings', 'local_deepler');
        $data->str_minification = get_string('files:minification', 'local_deepler');
        $data->str_minification_desc = get_string('files:minification_desc', 'local_deepler');

        // 1. Source languages options.
        $sourcelanguages = $this->langpack->preparesourcesoptionlangs();
        $globalsource = $this->langpack->get_deeplsourcelang() ?: $this->langpack->getcurrentlang(true);
        $sourcelangsformatted = [];
        foreach ($sourcelanguages as $slang) {
            $rawcode = $slang['code'];
            $cleancode = str_replace(lang_helper::REPHRASESYMBOL, '', $rawcode);
            $code = strtolower($cleancode);
            $sourcelangsformatted[] = [
                'code' => $code,
                'name' => $slang['verbose'] . ' (' . strtoupper($cleancode) . ')',
                'selected' => ($code === strtolower($globalsource)),
            ];
        }
        $data->sourcelangs = $sourcelangsformatted;
        $data->globalsource = strtolower($globalsource);

        // 2. Target languages options (limited to installed Moodle languages).
        $targetlanguages = $this->langpack->preparetargetsoptionlangs();
        $targetlangsformatted = [];
        $defaulttarget = '';
        foreach ($targetlanguages as $idx => $tlang) {
            $rawcode = $tlang['code'];
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

        // 3. Glossaries data & rendered selector.
        $glorenderer = $PAGE->get_renderer('local_deepler', 'glossary');
        $glossaries = $this->langpack->getusersglossaries() ?? [];
        $poolglossaries = $this->langpack->getpoolglossaries($glossaries) ?? [];
        $publicglossaries = $this->langpack->getpublicglossaries() ?? [];
        $allglossaries = array_merge($glossaries, $publicglossaries, $poolglossaries);

        $data->glossaryselector = $glorenderer->glossay_selector_deepl(
            $allglossaries,
            $this->langpack->getcurrentlang(true),
            $defaulttarget
        );

        $glossarydata = [];
        foreach ($allglossaries as $g) {
            $nameadd = ' (' . get_string('glossary:visibility:private', 'local_deepler') . ')';
            if ($g->shared === 1) {
                $nameadd = ' (' . get_string('glossary:visibility:pool', 'local_deepler') . ')';
            } else if ($g->shared === 2) {
                $nameadd = ' (' . get_string('glossary:visibility:public', 'local_deepler') . ')';
            }
            $glossarydata[] = [
                'id' => $g->glossaryid,
                'name' => $g->name . $nameadd,
                'sourcelang' => strtolower($g->sourcelang),
                'targetlang' => strtolower($g->targetlang),
            ];
        }
        $data->glossariesjson = json_encode($glossarydata);

        // 4. Group files by section.
        $sections = [];
        foreach ($this->files as $tfile) {
            $secid = $tfile->sectionid;
            if (!isset($sections[$secid])) {
                $secname = $tfile->sectionname ?: get_string('files:courselevel', 'local_deepler');
                $sections[$secid] = [
                    'sectionid' => $secid,
                    'sectionnum' => $tfile->sectionnum,
                    'sectionname' => $this->filter_multilang_text(format_string($secname)),
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

            $rawactivityname = $cm ? $cm->name : get_string('files:courselevel', 'local_deepler');
            $activityname = $this->filter_multilang_text(format_string($rawactivityname));
            $activityicon = $cm ? $output->pix_icon('monologo', $cm->modname, 'mod_' . $cm->modname, ['class' => 'icon mr-1']) : '';

            $rawfilename = $file->get_filename();
            $filename = $this->filter_multilang_text($rawfilename);

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

            // Format existing jobs / translations for grouped display.
            $translations = [];
            $hascompletedduplicate = false;
            $newcmurl = '';
            foreach ($tfile->jobs as $job) {
                $jobcmurl = '';
                if ($job->newcmid > 0) {
                    $jobcmurl = (new moodle_url('/mod/' . ($cm ? $cm->modname : 'resource') . '/view.php', ['id' => $job->newcmid]))->out(false);
                    $hascompletedduplicate = true;
                    $newcmurl = $jobcmurl;
                }

                $statusbadge = 'badge-secondary';
                $statusstr = get_string('files:' . $job->status, 'local_deepler');
                if ($job->status === 'done') {
                    $statusbadge = 'badge-success';
                } else if ($job->status === 'translating') {
                    $statusbadge = 'badge-warning';
                } else if ($job->status === 'error') {
                    $statusbadge = 'badge-danger';
                }

                $translations[] = [
                    'jobid' => (int)$job->id,
                    'targetlang' => strtoupper($job->targetlang),
                    'status' => $job->status,
                    'statusstr' => $statusstr,
                    'statusbadge' => $statusbadge,
                    'newcmid' => (int)$job->newcmid,
                    'newcmurl' => $jobcmurl,
                    'billedchars' => (int)$job->billedcharacters,
                    'str_billedchars' => get_string('files:billedchars', 'local_deepler', (int)$job->billedcharacters),
                    'timecreated' => userdate($job->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
                    'errormessage' => $job->errormessage ?: '',
                ];
            }

            // Per-row source language options (with selected state matching global default).
            $rowsourcelangs = [];
            foreach ($sourcelangsformatted as $sl) {
                $rowsourcelangs[] = [
                    'code' => $sl['code'],
                    'name' => $sl['name'],
                    'selected' => ($sl['code'] === strtolower($globalsource)),
                ];
            }

            // Per-row target language options (with selected state matching default target).
            $rowtargetlangs = [];
            foreach ($targetlangsformatted as $tl) {
                $rowtargetlangs[] = [
                    'code' => $tl['code'],
                    'name' => $tl['name'],
                    'selected' => ($tl['code'] === $defaulttarget),
                ];
            }

            $sections[$secid]['rows'][] = [
                'fileid' => (int)$file->get_id(),
                'cmid' => $cm ? (int)$cm->id : 0,
                'filename' => $filename,
                'rawfilename' => $rawfilename,
                'filesize' => display_size($file->get_filesize()),
                'fileurl' => $fileurl,
                'fileicon' => $icon,
                'activityname' => $activityname,
                'activityicon' => $activityicon,
                'supported' => $tfile->supported,
                'reason' => $reasondesc,
                'sourcelangs' => $rowsourcelangs,
                'targetlangs' => $rowtargetlangs,
                'hastranslations' => !empty($translations),
                'translationscount' => count($translations),
                'str_translations' => get_string('files:translations', 'local_deepler', count($translations)),
                'translations' => $translations,
                'hascompletedduplicate' => $hascompletedduplicate,
                'newcmurl' => $newcmurl,
                'str_openactivity' => get_string('files:openactivity', 'local_deepler'),
            ];
        }

        $data->sections = array_values($sections);
        return $data;
    }
}
