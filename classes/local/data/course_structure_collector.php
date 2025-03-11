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

use core_courseformat\base;
use stdClass;

/**
 * Entry point class that collects need data from courses.
 * And map it to our needs building sections of activities containing textfields.
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class course_structure_collector {
    /** @var section[] of sections titles (and id / order) for display */
    private array $sections;
    /**
     * @var \stdClass
     */
    private stdClass $course;
    /** @var \core_courseformat\base */
    private base $courseformat;

    public function __construct(stdClass $course) {
        global $DB;
        $this->sections = [];
        $this->course = $course;
        $this->courseformat = course_get_format($course); // Get the course format for default string.
        $courseinfo = get_fast_modinfo($course);

        //$coursesectionsinfo = $courseinfo->get_section_info_all();
        foreach ($courseinfo->get_section_info_all() as $section_info) {
            $this->sections[$section_info->sectionnum] = new section($section_info, $this->courseformat);
        }
        //$class = get_class($coursesectionsinfo);
        // ($courseinfo);
        // var_dump($this->sections);
        //var_dump($coursesectionsinfo['26']);
    }

    public function getcoursefileds(): array {
        $cf = [];
        $table = 'course';
        $coursedetails = $this->course->get_course();
        if ($coursedetails->fullname) {
            $cf[] = new field(
                    $this->course->id,
                    $this->course->fullname,
                    0,
                    'fullname',
                    $table);
        }
        if ($coursedetails->shortname) {
            $cf[] = new field($coursedetails->id, $coursedetails->shortname, 0, 'shortname', $table);
        }
        if ($this->course->summary) {
            $cf[] = new field($coursedetails->id, $coursedetails->summary, 1, 'summary', $table);
        }
        return $cf;
    }

    public function getformat(): base {
        return $this->courseformat;
    }

}
