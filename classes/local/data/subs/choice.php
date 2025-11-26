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

namespace local_deepler\local\data\subs;

use context_module;
use local_deepler\local\data\field;

/**
 * Sub for Choice activities.
 *
 * @package local_deepler
 * @copyright 2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class choice extends subbase {
    /**
     * Choice wrapper.
     *
     * @return array
     */
    public function getfields(): array {
        global $DB;
        $fields = [];
        $table = 'choice_options';
        $modcontext = context_module::instance($this->cm->id); // Var $cmid is the course module ID.
        require_capability('mod/choice:addinstance', $modcontext);
        $choices = $DB->get_records($table, ['choiceid' => $this->cm->instance]);
        foreach ($choices as $choice) {
            $fields[] = new field(
                $choice->id,
                $choice->text,
                0,
                'text',
                $table,
                $this->cm->id
            );
        }
        return $fields;
    }
}
