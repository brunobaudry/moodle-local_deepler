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

namespace local_deepler\local\data;

use cm_info;
use stored_file;

/**
 * Data Transfer Object representing a translatable file in a course.
 *
 * @package    local_deepler
 * @copyright  2026 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class translatable_file {
    /** @var stored_file The underlying Moodle stored file. */
    public stored_file $file;

    /** @var cm_info|null Course module info if associated with an activity, or null for course-level files. */
    public ?cm_info $cm;

    /** @var string Component name (e.g. mod_resource, mod_folder, course). */
    public string $component;

    /** @var string File area (e.g. content, intro, overviewfiles). */
    public string $filearea;

    /** @var bool Whether the file format and size are supported by DeepL. */
    public bool $supported;

    /** @var string Reason why file is unsupported if applicable ('', 'unsupported_extension', 'file_too_big'). */
    public string $reason;

    /** @var array List of existing translation job records (from local_deepler_files) for this file. */
    public array $jobs;

    /** @var int Section ID (or 0 for course-level files). */
    public int $sectionid;

    /** @var int Section number. */
    public int $sectionnum;

    /** @var string Section name / title. */
    public string $sectionname;

    /** @var bool Whether this file type is subject to DeepL 50,000 characters billing minimum. */
    public bool $hasbillingminimum;

    /**
     * Constructor.
     *
     * @param stored_file $file
     * @param cm_info|null $cm
     * @param string $component
     * @param string $filearea
     * @param bool $supported
     * @param string $reason
     * @param array $jobs
     * @param int $sectionid
     * @param int $sectionnum
     * @param string $sectionname
     * @param bool $hasbillingminimum
     */
    public function __construct(
        stored_file $file,
        ?cm_info $cm = null,
        string $component = '',
        string $filearea = '',
        bool $supported = true,
        string $reason = '',
        array $jobs = [],
        int $sectionid = 0,
        int $sectionnum = 0,
        string $sectionname = '',
        bool $hasbillingminimum = false
    ) {
        $this->file = $file;
        $this->cm = $cm;
        $this->component = $component;
        $this->filearea = $filearea;
        $this->supported = $supported;
        $this->reason = $reason;
        $this->jobs = $jobs;
        $this->sectionid = $sectionid;
        $this->sectionnum = $sectionnum;
        $this->sectionname = $sectionname;
        $this->hasbillingminimum = $hasbillingminimum;
    }
}
