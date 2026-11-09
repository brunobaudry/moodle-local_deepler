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
        'pptx',
        'xlsx',
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
     * Scan the course and return all translatable file DTOs grouped by section and module.
     *
     * @return translatable_file[]
     */
    public function get_course_files(): array {
        global $DB;

        // Fetch all existing translation jobs for this course.
        $jobsrecords = $DB->get_records('local_deepler_files', ['courseid' => $this->course->id], 'id ASC');
        $jobsbyfile = [];
        $translatedfileids = [];
        $translatedcmids = [];
        foreach ($jobsrecords as $job) {
            $jobsbyfile[$job->fileid][] = $job;
            if ($job->newfileid > 0) {
                $translatedfileids[$job->newfileid] = true;
            }
            if ($job->newcmid > 0) {
                $translatedcmids[$job->newcmid] = true;
            }
        }

        $fs = get_file_storage();
        $results = [];

        // 1. Scan Course-level files (summary, overviewfiles, legacy).
        $coursecontext = context_course::instance($this->course->id);
        $courseareas = ['summary', 'overviewfiles', 'legacy'];
        foreach ($courseareas as $area) {
            $files = $fs->get_area_files($coursecontext->id, 'course', $area, 0, 'filename', false);
            foreach ($files as $file) {
                if ($file->is_directory() || $file->get_filename() === '.') {
                    continue;
                }
                // Skip files created as translated duplicates.
                if (isset($translatedfileids[$file->get_id()])) {
                    continue;
                }
                $supported = self::is_supported($file, $this->maxbytes);
                $reason = self::get_unsupported_reason($file, $this->maxbytes);
                $filejobs = $jobsbyfile[$file->get_id()] ?? [];
                $hasbillingmin = self::has_billing_minimum($file);

                $results[] = new translatable_file(
                    $file,
                    null,
                    'course',
                    $area,
                    $supported,
                    $reason,
                    $filejobs,
                    0,
                    0,
                    get_string('files:courselevel', 'local_deepler'),
                    $hasbillingmin
                );
            }
        }

        // 2. Scan activity modules per section.
        $modinfo = get_fast_modinfo($this->course);
        $sections = $modinfo->get_section_info_all();

        foreach ($sections as $sectionnum => $sectioninfo) {
            if (empty($modinfo->sections[$sectionnum])) {
                continue;
            }

            $sectionname = get_section_name($this->course, $sectioninfo);
            $sectionid = (int)$sectioninfo->id;

            foreach ($modinfo->sections[$sectionnum] as $cmid) {
                // Skip duplicated modules created by Deepler translation.
                if (isset($translatedcmids[$cmid])) {
                    continue;
                }
                $cm = $modinfo->cms[$cmid] ?? null;
                if (!$cm) {
                    continue;
                }

                $context = context_module::instance($cm->id);
                $modname = $cm->modname;

                // Determine fileareas to inspect for this module.
                $areas = [];
                if ($modname === 'resource' || $modname === 'folder') {
                    $areas[] = ['component' => 'mod_' . $modname, 'filearea' => 'content'];
                }
                $areas[] = ['component' => 'mod_' . $modname, 'filearea' => 'intro'];
                if ($modname === 'assign') {
                    $areas[] = ['component' => 'mod_assign', 'filearea' => 'introattachment'];
                }

                $seenfileids = [];
                foreach ($areas as $area) {
                    $files = $fs->get_area_files($context->id, $area['component'], $area['filearea'], false, 'filename', false);
                    foreach ($files as $file) {
                        if ($file->is_directory() || $file->get_filename() === '.') {
                            continue;
                        }
                        if (isset($seenfileids[$file->get_id()]) || isset($translatedfileids[$file->get_id()])) {
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
                            $area['component'],
                            $area['filearea'],
                            $supported,
                            $reason,
                            $filejobs,
                            $sectionid,
                            $sectionnum,
                            $sectionname,
                            $hasbillingmin
                        );
                    }
                }
            }
        }

        return $results;
    }
}
