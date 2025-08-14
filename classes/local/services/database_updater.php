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

namespace local_deepler\local\services;

use dml_exception;

/**
 * Database updater service for local_deepler.
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class database_updater {
    /**
     * Update records in the database.
     *
     * @param array $data
     * @param array $response
     * @return void
     * @throws dml_exception
     */
    public static function update_records(array $data, array &$response): void {
        global $DB;

        $dataobject = ['id' => $data['id'], $data['field'] => $data['text']];
        try {
            $DB->update_record($data['table'], (object) $dataobject);
            $timemodified = time();
            $DB->update_record('local_deepler', ['id' => $data['tid'], 't_lastmodified' => $timemodified]);

            $response['t_lastmodified'] = $timemodified; // Translation last modified time.
            $response['text'] = $data['text'];
        } catch (dml_exception $e) {
            $errortoolong = get_string('errortoolong', 'local_deepler');
            $textlength = strlen($data['text']);
            $columns = $DB->get_columns($data['table']);
            $maxlength = $columns[$data['field']]->max_length;
            if ($textlength > $maxlength) {
                $response['error'] = "$textlength max=$maxlength $errortoolong";
            } else {
                $response['error'] = $e->getMessage();
            }
        }

    }

    /**
     * Get the text field from a record in the database.
     *
     * @param string $table
     * @param string $field
     * @param int $id
     * @return string
     * @throws \dml_exception
     */
    public static function get_textfield(string $table, string $field, int $id): string {
        global $DB;
        $record = $DB->get_record($table, ['id' => $id], '*', MUST_EXIST);
        return $record->{$field};
    }
}
