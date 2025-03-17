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
use course_modinfo;
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
    private course_modinfo $courseinfo;

    public function __construct(stdClass $course) {
        global $DB;
        module::$mintxtfieldsize = get_config('local_deepler', 'scannedfieldsize');
        $this->sections = [];
        $this->course = $course;
        $this->courseformat = course_get_format($course); // Get the course format for default string.
        $this->courseinfo = get_fast_modinfo($course);
        $coursefileds = $this->getcoursefields();
        $this->getsections();
        foreach ($this->sections as $section) {
            $f = $section->getsectionfields();
            $f;
        }

        //$coursesectionsinfo = $courseinfo->get_section_info_all();

        //$class = get_class($coursesectionsinfo);
        // ($courseinfo);
        // var_dump($this->sections);
        //var_dump($coursesectionsinfo['26']);
    }

    public function getcoursefields(): array {
        $info = $this->courseinfo->get_course();
        $table = 'course';
        $collumns = ['fullname', 'shortname', 'summary'];

        return field::getfields($info, $table, $collumns);
    }

    public function getsections() {
        foreach ($this->courseinfo->get_section_info_all() as $section_info) {
            $this->sections[$section_info->sectionnum] = new section($section_info, $this->courseformat);
        }
    }

    public function getformat(): base {
        return $this->courseformat;
    }

}
