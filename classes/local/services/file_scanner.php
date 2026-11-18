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

namespace local_deepler\local\services;

use context_course;
use context_module;
use get_fast_modinfo;
use local_deepler\local\data\translatable_file;
use stdClass;
use stored_file;

/**
 * Service to discover and inspect translatable files in a Moodle course.
 *
 * @package    local_deepler
 * @copyright  2026 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class file_scanner {
    /** @var array Supported file extensions by DeepL Document API. */
    public const SUPPORTED_EXTENSIONS = [
        'docx',
        'doc',
        'pptx',
        'xlsx',
        'xlsm',
        'pdf',
        'htm',
        'html',
        'txt',
        'xlf',
        'xliff',
        'srt',
        'odt',
        'rtf',
        'jpeg',
        'jpg',
        'png',
        'idml',
        'mif',
    ];

    /** @var array File extensions subject to DeepL 50,000 characters billing minimum. */
    public const MINIMUM_BILLING_EXTENSIONS = [
        'docx',
        'doc',
        'pptx',
        'xlsx',
        'xlsm',
        'pdf',
        'odt',
        'rtf',
        'jpeg',
        'jpg',
        'png',
        'idml',
        'mif',
        'zip',
    ];

    /** @var stdClass Course object. */
    protected stdClass $course;

    /** @var int Max file size allowed in bytes. */
    protected int $maxbytes;

    /**
     * Constructor.
     *
     * @param stdClass $course
     * @param int $maxbytes Maximum bytes allowed (0 to use admin setting or default).
     */
    public function __construct(stdClass $course, int $maxbytes = 0) {
        $this->course = $course;
        if ($maxbytes <= 0) {
            $configmax = get_config('local_deepler', 'filemaxbytes');
            $this->maxbytes = $configmax ? (int)$configmax : 10485760; // 10 MB default.
        } else {
            $this->maxbytes = $maxbytes;
        }
    }

    /**
     * Check if a stored_file or filename is subject to DeepL 50k character billing minimum.
     *
     * @param stored_file|string $file Stored file object or filename.
     * @return bool
     */
    public static function has_billing_minimum(stored_file|string $file): bool {
        $filename = is_string($file) ? $file : $file->get_filename();
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, self::MINIMUM_BILLING_EXTENSIONS, true);
    }

    /**
     * Check if a stored_file has a supported extension and fits size limits.
     *
     * @param stored_file $file
     * @param int $maxbytes
     * @return bool
     */
    public static function is_supported(stored_file $file, int $maxbytes = 10485760): bool {
        if ($file->is_directory() || $file->get_filename() === '.') {
            return false;
        }
        $ext = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
        if (!in_array($ext, self::SUPPORTED_EXTENSIONS, true)) {
            return false;
        }
        if ($maxbytes > 0 && $file->get_filesize() > $maxbytes) {
            return false;
        }
        return true;
    }

    /**
     * Get unsupported reason for a stored_file.
     *
     * @param stored_file $file
     * @param int $maxbytes
     * @return string Empty if supported, 'unsupported_extension', or 'file_too_big'.
     */
    public static function get_unsupported_reason(stored_file $file, int $maxbytes = 10485760): string {
        if ($file->is_directory() || $file->get_filename() === '.') {
            return 'directory';
        }
        $ext = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
        if (!in_array($ext, self::SUPPORTED_EXTENSIONS, true)) {
            return 'unsupported_extension';
        }
        if ($maxbytes > 0 && $file->get_filesize() > $maxbytes) {
            return 'file_too_big';
        }
        return '';
    }

    /**
     * Fetch existing translation jobs grouped by file ID, and sets of translated IDs.
     *
     * @return array [$jobsbyfile, $translatedfileids, $translatedcmids]
     */
    private function fetch_course_translation_jobs(): array {
        global $DB;

        $jobsrecords = $DB->get_records('local_deepler_files', ['courseid' => $this->course->id], 'id ASC');
        $jobsbyfile = [];
        $translatedfileids = [];
        $translatedcmids = [];
        $fs = get_file_storage();

        foreach ($jobsrecords as $job) {
            $jobsbyfile[$job->fileid][] = $job;
            if ($job->newfileid > 0) {
                $translatedfileids[$job->newfileid] = true;
            }
            if ($job->newcmid > 0) {
                $translatedcmids[$job->newcmid] = true;
                // Add all files belonging to the duplicated course module to translatedfileids.
                $dupcontext = context_module::instance($job->newcmid, IGNORE_MISSING);
                if ($dupcontext) {
                    $dupfiles = $fs->get_area_files($dupcontext->id, false, false, false, 'id ASC');
                    foreach ($dupfiles as $df) {
                        if (!$df->is_directory() && $df->get_filename() !== '.') {
                            $translatedfileids[$df->get_id()] = true;
                        }
                    }
                }
            }

            // Also check if a translated file exists for this job in case newfileid was 0 or became out of sync.
            if ($job->fileid > 0) {
                $sourcefile = $fs->get_file_by_id($job->fileid);
                if ($sourcefile && !$sourcefile->is_directory() && $sourcefile->get_filename() !== '.') {
                    $expectedtargetname = activity_duplicator::get_translated_filename(
                        $sourcefile->get_filename(),
                        $job->targetlang
                    );

                    // Check in original context / area.
                    $targetfile = $fs->get_file(
                        $sourcefile->get_contextid(),
                        $sourcefile->get_component(),
                        $sourcefile->get_filearea(),
                        $sourcefile->get_itemid(),
                        $sourcefile->get_filepath(),
                        $expectedtargetname
                    );
                    if ($targetfile) {
                        $translatedfileids[$targetfile->get_id()] = true;
                        if (empty($job->newfileid) && $job->status === 'done') {
                            $job->newfileid = (int)$targetfile->get_id();
                            $DB->set_field('local_deepler_files', 'newfileid', $targetfile->get_id(), ['id' => $job->id]);
                        }
                    }

                    // Check in duplicated cm context if newcmid was set.
                    if ($job->newcmid > 0 && !empty($dupcontext)) {
                        $dupfile = $fs->get_file(
                            $dupcontext->id,
                            $sourcefile->get_component(),
                            $sourcefile->get_filearea(),
                            $sourcefile->get_itemid(),
                            $sourcefile->get_filepath(),
                            $expectedtargetname
                        );
                        if ($dupfile) {
                            $translatedfileids[$dupfile->get_id()] = true;
                            if (empty($job->newfileid) && $job->status === 'done') {
                                $job->newfileid = (int)$dupfile->get_id();
                                $DB->set_field('local_deepler_files', 'newfileid', $dupfile->get_id(), ['id' => $job->id]);
                            }
                        }
                    }
                }
            }
        }

        return [$jobsbyfile, $translatedfileids, $translatedcmids];
    }

    /**
     * Check if a stored file is a translated copy of another file in the same context and area.
     *
     * @param stored_file $file
     * @return bool
     */
    private function is_translated_copy_file(stored_file $file): bool {
        $filename = $file->get_filename();
        // Look for pattern basename_LANG.ext (e.g. handout_fr.docx, image_zh_cn.png).
        if (preg_match('/^(.+)_[a-zA-Z]{2,3}(?:_[a-zA-Z]{2,4})?\.([a-zA-Z0-9]+)$/', $filename, $matches)) {
            $basefilename = $matches[1] . '.' . $matches[2];
            $fs = get_file_storage();
            $basefile = $fs->get_file(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $basefilename
            );
            if ($basefile && !$basefile->is_directory() && $basefile->get_filename() !== '.') {
                return true;
            }
        }
        return false;
    }

    /**
     * Scan course-level files in summary, overviewfiles, legacy, and section areas.
     *
     * @param array $jobsbyfile
     * @param array $translatedfileids
     * @return translatable_file[]
     */
    private function scan_course_level_files(array $jobsbyfile, array $translatedfileids): array {
        global $DB;
        $fs = get_file_storage();
        $coursecontext = context_course::instance($this->course->id);
        $results = [];
        $select = 'contextid = :contextid AND filename != :dot AND filesize > 0 ';
        $select .= 'AND component = :component AND filearea NOT IN (\'draft\', \'backup\', \'automated\')';
        $filerecords = $DB->get_records_select(
            'files',
            $select,
            ['contextid' => $coursecontext->id, 'dot' => '.', 'component' => 'course'],
            'itemid ASC, filename ASC'
        );

        foreach ($filerecords as $filerecord) {
            $file = $fs->get_file_instance($filerecord);
            if (!$file || $file->is_directory() || $file->get_filename() === '.') {
                continue;
            }
            if (isset($translatedfileids[$file->get_id()]) || $this->is_translated_copy_file($file)) {
                continue;
            }

            $supported = self::is_supported($file, $this->maxbytes);
            $reason = self::get_unsupported_reason($file, $this->maxbytes);
            $filejobs = $jobsbyfile[$file->get_id()] ?? [];
            $hasbillingmin = self::has_billing_minimum($file);

            $results[] = new translatable_file(
                $file,
                null,
                $file->get_component(),
                $file->get_filearea(),
                $supported,
                $reason,
                $filejobs,
                0,
                0,
                get_string('files:courselevel', 'local_deepler'),
                $hasbillingmin
            );
        }

        return $results;
    }

    /**
     * Scan files for a single course module.
     *
     * @param cm_info|\stdClass $cm
     * @param int $sectionid
     * @param int $sectionnum
     * @param string $sectionname
     * @param array $jobsbyfile
     * @param array $translatedfileids
     * @return translatable_file[]
     */
    private function scan_module_files(
        $cm,
        int $sectionid,
        int $sectionnum,
        string $sectionname,
        array $jobsbyfile,
        array $translatedfileids
    ): array {
        global $DB;
        $fs = get_file_storage();
        $context = context_module::instance($cm->id);
        $results = [];
        $seenfileids = [];
        $select = 'contextid = :contextid AND filename != :dot AND filesize > 0 ';
        $select .= 'AND filearea NOT IN (\'draft\', \'backup\', \'automated\')';
        $filerecords = $DB->get_records_select(
            'files',
            $select,
            ['contextid' => $context->id, 'dot' => '.'],
            'itemid ASC, filename ASC'
        );

        foreach ($filerecords as $filerecord) {
            $file = $fs->get_file_instance($filerecord);
            if (!$file || $file->is_directory() || $file->get_filename() === '.') {
                continue;
            }
            if (
                isset($seenfileids[$file->get_id()]) ||
                isset($translatedfileids[$file->get_id()]) ||
                $this->is_translated_copy_file($file)
            ) {
                continue;
            }
            $seenfileids[$file->get_id()] = true;

            $supported = self::is_supported($file, $this->maxbytes);
            $reason = self::get_unsupported_reason($file, $this->maxbytes);
            $filejobs = $jobsbyfile[$file->get_id()] ?? [];
            $hasbillingmin = self::has_billing_minimum($file);

            $results[] = new translatable_file(
                $file,
                $cm,
                $file->get_component(),
                $file->get_filearea(),
                $supported,
                $reason,
                $filejobs,
                $sectionid,
                $sectionnum,
                $sectionname,
                $hasbillingmin
            );
        }

        return $results;
    }

    /**
     * Scan activity modules per section.
     *
     * @param array $jobsbyfile
     * @param array $translatedfileids
     * @param array $translatedcmids
     * @return translatable_file[]
     */
    private function scan_section_modules(
        array $jobsbyfile,
        array $translatedfileids,
        array $translatedcmids
    ): array {
        $modinfo = get_fast_modinfo($this->course);
        $sections = $modinfo->get_section_info_all();
        $results = [];

        foreach ($sections as $sectionnum => $sectioninfo) {
            if (empty($modinfo->sections[$sectionnum])) {
                continue;
            }

            $sectionname = get_section_name($this->course, $sectioninfo);
            $sectionid = (int)$sectioninfo->id;

            foreach ($modinfo->sections[$sectionnum] as $cmid) {
                if (isset($translatedcmids[$cmid])) {
                    continue;
                }
                $cm = $modinfo->cms[$cmid] ?? null;
                if (!$cm) {
                    continue;
                }

                $modfiles = $this->scan_module_files(
                    $cm,
                    $sectionid,
                    $sectionnum,
                    $sectionname,
                    $jobsbyfile,
                    $translatedfileids
                );
                array_push($results, ...$modfiles);
            }
        }

        return $results;
    }

    /**
     * Scan the course and return all translatable file DTOs grouped by section and module.
     *
     * @return translatable_file[]
     */
    public function get_course_files(): array {
        [$jobsbyfile, $translatedfileids, $translatedcmids] = $this->fetch_course_translation_jobs();

        $coursefiles = $this->scan_course_level_files($jobsbyfile, $translatedfileids);
        $sectionfiles = $this->scan_section_modules($jobsbyfile, $translatedfileids, $translatedcmids);

        return array_merge($coursefiles, $sectionfiles);
    }
}
