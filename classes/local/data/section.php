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
use section_info;

/**
 * Fields collections matching Moodle's sections including the course title.
 * This in order to organise the display.
 *
 * @package    local_deepler
 * @copyright  2025 bruno.baudry@bfh.ch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section {
    /** @var \section_info */
    private section_info $si;
    private base $courseformat;

    /** @var module[] array of module */
    private array $modules;
    /**
     * @var \cm_info[]
     */
    private array $cms;

    public function __construct(section_info $section_info, base $courseformat) {
        $this->si = $section_info;
        $this->courseformat = $courseformat;
        $this->getmodules();
    }

    public function isvisible(): bool {
        return $this->si->visible == true;
    }

    public function getsectionname(): string {
        $defaultname = $this->si->name ?? '';
        if ($defaultname === '') {
            $defaultname = $this->courseformat->get_default_section_name($this->si);
        }
        return $defaultname;
    }

    public function getorder(): int {
        return $this->si->sectionnum;
    }

    public function getsectionfields(): array {
        $infos = [];
        $table = 'course_sections';
        $collumns = ['name', 'summary'];
        return field::getfields($infos, $table, $collumns);
    }

    /**
     * @return void
     */
    public function getmodules(): void {
        foreach ($this->si->get_sequence_cm_infos() as $cmid => $coursemodule) {
            $this->modules[$cmid] = new module($coursemodule);
        }
    }
}
