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

/**
 * Course Translator Observers.
 *
 * @package    local_deepler
 * @copyright  2022 Kaleb Heitzman <kaleb@jamfire.io>
 * @copyright 2025 bruno baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/Events_API
 */

namespace local_deepler;
use core\event\base;
use core\event\course_module_updated;

/**
 * Course Translator Observers.
 *
 * Watch for course, course section, and mod updates.
 *
 * @package    local_deepler
 */
class observer {
    /**
     * Common Observer for subitems lil^ke forum posts, book chapters etc...
     * See db/events.php to know which event triggers it.
     *
     * @param \core\event\base $event
     * @return void
     * @throws \dml_exception
     */
    public static function subitems_update(base $event): void {
        self::updatedb($event);
    }

    /**
     * Event wrapper as module activity are in 'other'.
     *
     * @param \core\event\course_module_updated $event
     * @return void
     * @throws \dml_exception
     */
    public static function course_module_updated(course_module_updated $event): void {
        global $DB;
        // Get matching records for module which infos are in the other object.
        $records = $DB->get_recordset(
            'local_deepler',
            ['t_id' => $event->other['instanceid'], 't_table' => $event->other['modulename']],
            '',
            '*'
        );

        // Update s_lastmodified time.
        self::update_lastmodifiedtime($records);
    }
    /**
     *
     *
     * @param \moodle_recordset $records
     * @return void
     * @throws \dml_exception
     */
    private static function update_lastmodifiedtime(\moodle_recordset $records): void {
        global $DB;
        // Set timemodified.
        $timemodified = time();

        foreach ($records as $record) {
            $DB->update_record(
                'local_deepler',
                ['id' => $record->id, 's_lastmodified' => $timemodified]
            );
        }
        $records->close();
    }


    /**
     * DB wrapper, get the fields first.
     *
     * @param \core\event\base $event
     * @return void
     * @throws \dml_exception
     */
    private static function updatedb(base $event): void {
        global $DB;

        // Get matching records.
        $records = $DB->get_recordset(
            'local_deepler',
            ['t_id' => $event->objectid, 't_table' => $event->objecttable],
            '',
            '*'
        );

        // Update s_lastmodified time.
        self::update_lastmodifiedtime($records);
    }
}
