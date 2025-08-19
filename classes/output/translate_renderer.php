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

use core_filters\text_filter;
use local_deepler\local\data\field;
use local_deepler\local\data\interfaces\translatable_interface;
use local_deepler\local\data\multilanger;
use local_deepler\local\services\lang_helper;
use local_deepler\local\services\utils;
use renderer_base;
use filter_multilang2\text_filter as Multilang2TextFilter;
/**
 * Sub renderer for translate page stuff.
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class translate_renderer extends renderer_base {
    /**
     * Render field row.
     *
     * @param \local_deepler\output\row_data $data
     * @return bool|string
     * @throws \core\exception\moodle_exception
     */
    public function makefieldrow(row_data $data): bool|string {
        return $this->render_from_template('local_deepler/translate_field', $data->export_for_template($this));
    }

    /**
     * @param \local_deepler\output\child_data $data
     * @return bool|string
     * @throws \core\exception\moodle_exception
     */
    public function makechild(child_data $data): bool|string {
        return $this->render_from_template('local_deepler/translate_child', $data->export_for_template($this));
    }

    /**
     * @param \local_deepler\output\module_data $data
     * @return bool|string
     * @throws \core\exception\moodle_exception
     */
    public function makemodule(module_data $data): bool|string {
        return $this->render_from_template('local_deepler/translate_module', $data->export_for_template($this));
    }

    /**
     * @param \local_deepler\output\section_data $data
     * @return bool|string
     * @throws \core\exception\moodle_exception
     */
    public function makesection(section_data $data): bool|string {
        return $this->render_from_template('local_deepler/translate_section', $data->export_for_template($this));
    }

    /**
     * @param \local_deepler\output\coursesettings_data $data
     * @return bool|string
     * @throws \core\exception\moodle_exception
     */
    public function makecoursesetting(coursesettings_data $data): bool|string {
        return $this->render_from_template('local_deepler/translate_coursesettings', $data->export_for_template($this));
    }
}
