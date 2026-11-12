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
     * Prepare source languages list for template.
     *
     * @param string $globalsource
     * @return array
     */
    private function prepare_sourcelangs_data(string $globalsource): array {
        $sourcelanguages = $this->langpack->preparesourcesoptionlangs();
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
        return $sourcelangsformatted;
    }

    /**
     * Prepare target languages list for template.
     *
     * @return array [array $targetlangsformatted, string $defaulttarget]
     */
    private function prepare_targetlangs_data(): array {
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
        return [$targetlangsformatted, $defaulttarget];
    }

    /**
     * Prepare glossaries data and selector.
     *
     * @param string $defaulttarget
     * @return array [string $glossaryselector, array $glossarydata]
     */
    private function prepare_glossary_data(string $defaulttarget): array {
        global $PAGE;

        $glorenderer = $PAGE->get_renderer('local_deepler', 'glossary');
        $glossaries = $this->langpack->getusersglossaries() ?? [];
        $poolglossaries = $this->langpack->getpoolglossaries($glossaries) ?? [];
        $publicglossaries = $this->langpack->getpublicglossaries() ?? [];
        $allglossaries = array_merge($glossaries, $publicglossaries, $poolglossaries);

        $glossaryselector = $glorenderer->glossay_selector_deepl(
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

        return [$glossaryselector, $glossarydata];
    }

    /**
     * Get human-readable description if a file is unsupported.
     *
     * @param \local_deepler\local\data\translatable_file $tfile
     * @return string
     */
    private function get_unsupported_reason_desc(\local_deepler\local\data\translatable_file $tfile): string {
        if ($tfile->supported) {
            return '';
        }
        if ($tfile->reason === 'unsupported_extension') {
            $ext = pathinfo($tfile->file->get_filename(), PATHINFO_EXTENSION);
            return get_string('files:reason_extension', 'local_deepler', $ext);
        }
        if ($tfile->reason === 'file_too_big') {
            return get_string('files:reason_filesize', 'local_deepler', display_size($tfile->file->get_filesize()));
        }
        return '';
    }

    /**
     * Format a single translation job data for template.
     *
     * @param stdClass $job
     * @param string $rawfilename
     * @param string|null $modname
     * @return array [array $jobdata, bool $iscompletedduplicate, string $cmurl]
     */
    private function format_translation_job(stdClass $job, string $rawfilename, ?string $modname): array {
        $jobcmurl = '';
        $jobfilename = '';
        $iscompletedduplicate = false;

        if ($job->newcmid > 0) {
            $modtype = $modname ?: 'resource';
            $jobcmurl = (new moodle_url('/mod/' . $modtype . '/view.php', ['id' => $job->newcmid]))->out(false);
            if ($job->status === 'done') {
                $iscompletedduplicate = true;
            }
        }
        if ($job->newfileid > 0) {
            $fs = get_file_storage();
            $newfile = $fs->get_file_by_id($job->newfileid);
            if ($newfile) {
                $jobfilename = $newfile->get_filename();
            }
        }
        if ($jobfilename === '') {
            $pathinfo = pathinfo($rawfilename);
            $basename = $pathinfo['filename'] ?? 'file';
            $ext = !empty($pathinfo['extension']) ? ('.' . $pathinfo['extension']) : '';
            $jobfilename = $basename . '_' . strtolower($job->targetlang) . $ext;
        }

        $badegmap = [
            'done' => 'badge-success',
            'translating' => 'badge-warning',
            'error' => 'badge-danger',
        ];
        $statusbadge = $badegmap[$job->status] ?? 'badge-secondary';

        $jobdata = [
            'jobid' => (int)$job->id,
            'targetlang' => strtoupper($job->targetlang),
            'filename' => $this->filter_multilang_text($jobfilename),
            'status' => $job->status,
            'statusstr' => get_string('files:' . $job->status, 'local_deepler'),
            'statusbadge' => $statusbadge,
            'newcmid' => (int)$job->newcmid,
            'newcmurl' => $jobcmurl,
            'billedchars' => (int)$job->billedcharacters,
            'str_billedchars' => get_string('files:billedchars', 'local_deepler', (int)$job->billedcharacters),
            'timecreated' => userdate($job->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
            'errormessage' => $job->errormessage ?: '',
        ];

        return [$jobdata, $iscompletedduplicate, $jobcmurl];
    }

    /**
     * Format row language dropdown options.
     *
     * @param array $sourcelangsformatted
     * @param array $targetlangsformatted
     * @param string $globalsource
     * @param string $defaulttarget
     * @return array [array $rowsourcelangs, array $rowtargetlangs]
     */
    private function prepare_row_language_options(
        array $sourcelangsformatted,
        array $targetlangsformatted,
        string $globalsource,
        string $defaulttarget
    ): array {
        $rowsourcelangs = [];
        foreach ($sourcelangsformatted as $sl) {
            $rowsourcelangs[] = [
                'code' => $sl['code'],
                'name' => $sl['name'],
                'selected' => ($sl['code'] === strtolower($globalsource)),
            ];
        }

        $rowtargetlangs = [];
        foreach ($targetlangsformatted as $tl) {
            $rowtargetlangs[] = [
                'code' => $tl['code'],
                'name' => $tl['name'],
                'selected' => ($tl['code'] === $defaulttarget),
            ];
        }

        return [$rowsourcelangs, $rowtargetlangs];
    }

    /**
     * Build template row data for a translatable file.
     *
     * @param \local_deepler\local\data\translatable_file $tfile
     * @param renderer_base $output
     * @param array $sourcelangsformatted
     * @param array $targetlangsformatted
     * @param string $globalsource
     * @param string $defaulttarget
     * @return array
     */
    private function build_row_data(
        \local_deepler\local\data\translatable_file $tfile,
        renderer_base $output,
        array $sourcelangsformatted,
        array $targetlangsformatted,
        string $globalsource,
        string $defaulttarget
    ): array {
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
        $reasondesc = $this->get_unsupported_reason_desc($tfile);

        $translations = [];
        $hascompletedduplicate = false;
        $newcmurl = '';

        foreach ($tfile->jobs as $job) {
            [$jobdata, $iscompleteddup, $jobcmurl] = $this->format_translation_job(
                $job,
                $rawfilename,
                $cm ? $cm->modname : null
            );
            $translations[] = $jobdata;
            if ($iscompleteddup) {
                $hascompletedduplicate = true;
                $newcmurl = $jobcmurl;
            }
        }

        [$rowsourcelangs, $rowtargetlangs] = $this->prepare_row_language_options(
            $sourcelangsformatted,
            $targetlangsformatted,
            $globalsource,
            $defaulttarget
        );

        return [
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
            'hasbillingminimum' => $tfile->hasbillingminimum,
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

    /**
     * Group files by course section for template output.
     *
     * @param renderer_base $output
     * @param array $sourcelangsformatted
     * @param array $targetlangsformatted
     * @param string $globalsource
     * @param string $defaulttarget
     * @return array
     */
    private function group_files_by_section(
        renderer_base $output,
        array $sourcelangsformatted,
        array $targetlangsformatted,
        string $globalsource,
        string $defaulttarget
    ): array {
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

            $sections[$secid]['rows'][] = $this->build_row_data(
                $tfile,
                $output,
                $sourcelangsformatted,
                $targetlangsformatted,
                $globalsource,
                $defaulttarget
            );
        }

        return array_values($sections);
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

        $globalsource = $this->langpack->get_deeplsourcelang() ?: $this->langpack->getcurrentlang(true);
        $sourcelangsformatted = $this->prepare_sourcelangs_data($globalsource);
        $data->sourcelangs = $sourcelangsformatted;
        $data->globalsource = strtolower($globalsource);

        [$targetlangsformatted, $defaulttarget] = $this->prepare_targetlangs_data();
        $data->targetlangs = $targetlangsformatted;
        $data->defaulttarget = $defaulttarget;

        [$glossaryselector, $glossarydata] = $this->prepare_glossary_data($defaulttarget);
        $data->glossaryselector = $glossaryselector;
        $data->glossaries = $glossarydata;
        $data->glossariesjson = json_encode($glossarydata);

        $data->sections = $this->group_files_by_section(
            $output,
            $sourcelangsformatted,
            $targetlangsformatted,
            $globalsource,
            $defaulttarget
        );

        return $data;
    }
}
