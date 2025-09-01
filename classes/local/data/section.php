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
use core_courseformat\base;
use local_deepler\local\data\interfaces\editable_interface;
use local_deepler\local\data\interfaces\translatable_interface;
use local_deepler\local\data\interfaces\visibility_interface;
use moodle_url;
use section_info;

/**
 * Class section wraps a section_info object and provides a way to access its fields.
 *
 * @package    local_deepler
 * @copyright  2025 bruno.baudry@bfh.ch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section implements translatable_interface, editable_interface, visibility_interface {
    /** @var \section_info */
    private section_info $si;
    /** @var \core_courseformat\base */
    private base $courseformat;

    /** @var module[] array of module */
    private array $modules;
    /**
     * @var \cm_info[]
     */
    private array $sectioncms;

    /**
     * Getter for sessions' activity cms.
     *
     * @return \cm_info[]
     */
    public function get_sectioncms(): array {
        return $this->sectioncms;
    }

    /**
     * Getter for modules.
     *
     * @return array|\local_deepler\local\data\module[]
     */
    public function get_modules(): array {
        return $this->modules;
    }
    /**
     * @var \moodle_url
     */
    private moodle_url $link;
    /** @var int */
    private int $loadeddmoduleid;

    /**
     * Getter.
     *
     * @return int
     */
    public function get_loadeddmoduleid(): int {
        return $this->loadeddmoduleid;
    }

    /**
     * Constructor
     *
     * @param \section_info $sectioninfo
     * @param \core_courseformat\base $courseformat
     * @param int $loadeddmodule
     * @throws \coding_exception
     * @throws \core\exception\moodle_exception
     */
    public function __construct(section_info $sectioninfo, base $courseformat, int $loadeddmodule) {
        global $CFG;
        $this->loadeddmoduleid = $loadeddmodule;
        $this->si = $sectioninfo;
        $this->link = new moodle_url($CFG->wwwroot . "/course/editsection.php", ['id' => $this->si->id]);
        $this->courseformat = $courseformat;
        $this->modules = [];
        $this->populatemodules();
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
     * Tells if section is leer.
     *
     * @return bool
     */
    public function is_empty(): bool {
        return empty ($this->get_modules()) && empty($this->getfields());
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
        // Return the default name if the section name is empty.
        // As get_default_section_name can return null, we need to check if it is not null.
        return $defaultname ?? '';
    }

    /**
     * Map the names of section's module to ids.
     *
     * @return array
     */
    public function get_modules_id_name(): array {
        $idnames = [];
        /** @var \local_deepler\local\data\module $module */
        foreach ($this->modules as $module) {
            $cm = $module->get_cm();
            $idnames[] =
                    [
                            'id' => $cm->id,
                            'name' => $cm->name,
                            'selected' => $cm->id == $this->loadeddmoduleid,
                    ];
        }
        return $idnames;
    }

    /**
     * Fields of the section.
     *
     * @return array
     */
    public function getfields(): array {
        $table = 'course_sections';
        $collumns = ['name' => [], 'summary' => []];
        return field::getfieldsfromcolumns($this->si, $table, $collumns);
    }

    /**
     * Get the modules of the section.
     *
     * @return array
     * @throws \coding_exception|\dml_exception
     */
    public function populatemodules(): array {
        if (method_exists($this->si, 'get_sequence_cm_infos')) {
            // Moodle 405.
            $this->sectioncms = $this->si->get_sequence_cm_infos();
        } else {
            // Moodle 401 to 404.
            $this->sectioncms = self::get_sequence_cm_infos($this->si);
        }
        foreach ($this->sectioncms as $cmid => $coursemodule) {
            // Filter modules to load.
            if ($this->loadeddmoduleid != -1 && $this->loadeddmoduleid != $coursemodule->id) {
                continue;
            }
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

    /**
     * Returns the course modules in this section.
     * Should be deprecated after 405 is the minimal supported version.
     *
     * @param \section_info $si
     * @return cm_info[]
     */
    private static function get_sequence_cm_infos(section_info $si): array {
        $sequence = $si->modinfo->sections[$si->section] ?? [];
        $cms = $si->modinfo->get_cms();
        $result = [];
        foreach ($sequence as $cmid) {
            if (isset($cms[$cmid])) {
                $result[] = $cms[$cmid];
            }
        }
        return $result;
    }

}
