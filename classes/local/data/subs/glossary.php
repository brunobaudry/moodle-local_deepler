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
 * Sub for Glossary activities.
 *
 * @package local_deepler
 * @copyright 2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class glossary extends subbase {
    /**
     * Glossary wrapper.
     *
     * @return array
     */
    public function getfields(): array {
        global $DB;
        $fields = [];
        $table = 'glossary_entries';
        $modcontext = context_module::instance($this->cm->id); // Var $cmid is the course module ID.
        require_capability('mod/glossary:manageentries', $modcontext);
        $entries = $DB->get_records($table, ['glossaryid' => $this->cm->instance]);
        foreach ($entries as $entry) {
            $fields[] = new field(
                $entry->id,
                $entry->concept,
                0,
                'concept',
                $table,
                $this->cm->id
            );
            if ($entry->definition) {
                $fields[] = new field(
                    $entry->id,
                    $entry->definition,
                    1,
                    'definition',
                    $table,
                    $this->cm->id
                );
            }
        }
        return $fields;
    }
}
