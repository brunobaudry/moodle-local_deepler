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
use local_deepler\local\data\interfaces\editable_interface;
use local_deepler\local\data\interfaces\translatable_interface;
use moodle_url;
use section_info;

/**
 * Class section wraps a section_info object and provides a way to access its fields.
 *
 * @package    local_deepler
 * @copyright  2025 bruno.baudry@bfh.ch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section implements translatable_interface, editable_interface {
    /** @var \section_info */
    private section_info $si;
    /** @var \core_courseformat\base */
    private base $courseformat;

    /** @var module[] array of module */
    private array $modules;
    /**
     * @var \cm_info[]
     */
    private array $cms;
    /**
     * @var \moodle_url
     */
    private ?moodle_url $link;

    /**
     * Constructor
     *
     * @param \section_info $sectioninfo
     * @param \core_courseformat\base $courseformat
     */
    public function __construct(section_info $sectioninfo, base $courseformat) {
        global $CFG;
        $this->si = $sectioninfo;
        $this->link = new moodle_url($CFG->wwwroot . "/course/editsection.php", ['id' => $this->si->id]);
        $this->courseformat = $courseformat;
        $this->modules = [];
        $this->getmodules();
    }

    /**
     * This method is used to check if the section is visible.
     *
     * @return bool
     */
    public function isvisible(): bool {
        return $this->si->visible == true;
    }

    /**
     * This method is used to get the section name.
     * Will return the default name if the section name is empty.
     *
     * @return string
     */
    public function getsectionname(): string {
        $defaultname = $this->si->name ?? '';
        if ($defaultname === '') {
            $defaultname = $this->courseformat->get_default_section_name($this->si);
        }
        return $defaultname;
    }

    /**
     * This method is used to get the section order.
     *
     * @return int
     */
    public function getorder(): int {
        return $this->si->sectionnum;
    }

    /**
     * Fields of the section.
     *
     * @return array
     */
    public function getfields(): array {
        $infos = [];
        $table = 'course_sections';
        $collumns = ['name', 'summary'];
        return field::getfieldsfromcolumns($infos, $table, $collumns);
    }

    /**
     * Get the modules of the section.
     *
     * @return array
     */
    public function getmodules(): array {
        foreach ($this->si->get_sequence_cm_infos() as $cmid => $coursemodule) {
            $this->modules[$cmid] = new module($coursemodule);
        }
        return $this->modules;
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
     * Get the id of the section.
     *
     * @return int
     */
    public function getid() {
        return $this->si->id;
    }
}
