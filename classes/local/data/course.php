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
use local_deepler\local\services\utils;
use moodle_exception;
use moodle_url;
use stdClass;
use Symfony\Component\Yaml\Yaml;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../vendor/autoload.php');

/**
 * Class course wraps a course object and provides a way to access its fields.
 *
 * @package local_deepler
 * @copyright 2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course implements interfaces\editable_interface, interfaces\translatable_interface {
    /**
     * @var \course_modinfo|null
     */
    private ?course_modinfo $course;
    /**
     * @var \moodle_url
     */
    private moodle_url $link;
    /**
     * @var \core_courseformat\base
     */
    private base $format;


    /** @var section[] of sections titles (and id / order) for display */
    private array $sections;
    /**
     * @var \section_info[]
     */
    private array $sectioninfoall;


    /** @var int */
    private int $loadedsectionid;
    /** @var int */
    private int $loadeddmoduleid;
    /** @var int */
    private int $loadedsectionnum;

    /**
     * Getter for the session info.
     *
     * @return \section_info[]
     */
    public function get_sectioninfoall(): array {
        return $this->sectioninfoall;
    }

    /**
     * Getter for the current format.
     *
     * @return \core_courseformat\base
     */
    public function get_format(): base {
        return $this->format;
    }
    /**
     * Getter for the session rank.
     *
     * @return int
     */
    public function get_loadedsectionnum(): int {
        return $this->loadedsectionnum;
    }

    /**
     * Constructor.
     *
     * @param \stdClass $course
     * @param int $lodadedsection
     * @param int $loadeddmodule
     * @throws \core\exception\moodle_exception
     * @throws \moodle_exception
     */
    public function __construct(stdClass $course, int $lodadedsection = -99, int $loadeddmodule = -99) {
        global $CFG;
        $this->loadeddmodule = $loadeddmodule;
        $this->loadedsectionnum = $this->loadedsectionid = $lodadedsection;
        // Load yaml config of known field definitions.
        if (empty(field::$additionals)) {
            $configfile = utils::get_plugin_root() . '/additional_conf.yaml';
            field::$additionals = Yaml::parseFile($configfile);
        }
        $this->sections = [];
        $this->course = get_fast_modinfo($course);
        $this->format = course_get_format($course);
        $this->link = new moodle_url($CFG->wwwroot . "/course/edit.php", ['id' => $this->course->get_course_id()]);
        try {
            $this->populatesections();
        } catch (moodle_exception $ex) {
            debugging($ex);
        }

    }

    /**
     * Basic getter for course_modinfo
     *
     * @return course_modinfo
     */
    public function getinfo(): course_modinfo {
        return $this->course;
    }

    /**
     * Link for edit.
     *
     * @return string
     */
    public function getlink(): string {
        return $this->link->out();
    }

    /**
     * Get the translatable fields of the course.
     *
     * @return array
     */
    public function getfields(): array {
        $info = $this->course->get_course();
        $table = 'course';
        $collumns = ['fullname' => [], 'shortname' => [], 'summary' => []];

        return field::getfieldsfromcolumns($info, $table, $collumns);
    }

    /**
     * Get the sections of the course.
     *
     * @return array|\local_deepler\local\data\section[]
     */
    public function getsections(): array {
        return $this->sections;
    }

    /**
     * Populate the sections of the course.
     *
     * @return void
     * @throws \core\exception\moodle_exception
     */
    private function populatesections(): void {
        $this->sectioninfoall = $this->course->get_section_info_all();
        /** @var section_info $sectioninfo */
        foreach ($this->sectioninfoall as $sectioninfo) {
            // If selected section is -1 then load all, if -99 none else load single.
            if ($this->loadedsectionid !== -1 && $sectioninfo->id != $this->loadedsectionid) {
                continue;
            }
            $this->sections[$sectioninfo->sectionnum] = new section($sectioninfo, $this->format, $this->loadeddmodule);
            if ($this->loadedsectionid >= 0) {
                $this->loadedsectionnum = $sectioninfo->sectionnum;
            }
        }
    }

    /**
     * Getter for loadedsection.
     *
     * @return int
     */
    public function get_loadedsection(): int {
        return $this->loadedsectionid;
    }
}
