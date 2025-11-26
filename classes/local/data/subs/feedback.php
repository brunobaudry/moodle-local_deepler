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
 * Sub for Feedbacks activities.
 *
 * @package local_deepler
 * @copyright 2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class feedback extends subbase {
    /**
     * Feedback wrapper.
     *
     * @return array
     * @throws \dml_exception
     * @throws \required_capability_exception
     */
    public function getfields(): array {
        global $DB;
        $fields = [];
        $table = 'feedback_item';
        $modcontext = context_module::instance($this->cm->id);
        require_capability('mod/feedback:addinstance', $modcontext);
        $entries = $DB->get_records($table, ['feedback' => $this->cm->instance]);
        foreach ($entries as $entry) {
            $fields[] = new field(
                $entry->id,
                $entry->name,
                0,
                'name',
                $table,
                $this->cm->id
            );
            if ($entry->definition) {
                $fields[] = new field(
                    $entry->id,
                    $entry->label,
                    1,
                    'label',
                    $table,
                    $this->cm->id
                );
            }
        }
        return $fields;
    }
}
