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

namespace local_deepler\local\data\subs\questions;

use local_deepler\local\data\field;

/**
 * Matching question type wrapper.
 *
 * @package    local_deepler
 * @copyright  2025  <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_match extends qbase {
    /**
     * Get the fields to be translated.
     *
     * @return array
     * @throws \ddl_exception
     * @throws \dml_exception
     */
    protected function getsubs(): array {
        global $DB;
        $fields = [];
        $substablename = $this->qtype . '_subquestions';
        if ($this->dbmanager->table_exists($substablename)) {
            if ($DB->record_exists($substablename, [$this->qidcolname => $this->question->id])) {
                $submatches = $DB->get_records($substablename, [$this->qidcolname => $this->question->id]);
                foreach ($submatches as $submatch) {
                    $subrecord = $DB->get_record($substablename, ['id' => $submatch->id]);
                    $subtablefileds = field::filterdbtextfields($substablename);
                    foreach ($subtablefileds as $field) {
                        if ($subrecord->{$field} !== null && trim($subrecord->{$field}) !== '') {
                            $fields[] = new field(
                                    $subrecord->id,
                                    $subrecord->{$field},
                                    isset($subrecord->{$field . 'format'}) ?? 0,
                                    $field,
                                    $substablename,
                                    $this->cmid
                            );
                        }
                    }
                }
            }
        }
        return $fields;
    }
}
