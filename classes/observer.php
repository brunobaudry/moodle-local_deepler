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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/Events_API
 */

namespace local_deepler;
/**
 * Course Translator Observers.
 *
 * Watch for course, course section, and mod updates.
 *
 * @package    local_deepler
 */
class local_deepler_observer {

    /**
     * Observer for course_updated event.
     *
     * @param \core\event\course_updated $event
     * @return void
     */
    public static function course_updated(\core\event\course_updated $event) {
        global $DB;

        // Get params.
        $objectid = $event->objectid;
        $objecttable = $event->objecttable;

        // Set timemodified.
        $timemodified = time();

        // Get matching records.
        $records = $DB->get_recordset(
                'local_deepler',
                ['t_id' => $objectid, 't_table' => $objecttable],
                '',
                'id'
        );

        // Update s_lastmodified time.
        foreach ($records as $record) {
            $DB->update_record(
                    'local_deepler',
                    ['id' => $record->id, 's_lastmodified' => $timemodified]
            );
        }
        $records->close();
    }

    /**
     * Observer for course_section_updated event.
     *
     * @param \core\event\course_section_updated $event
     * @return void
     */
    public static function course_section_updated(\core\event\course_section_updated $event) {
        global $DB;

        // Get params.
        $objectid = $event->objectid;
        $objecttable = $event->objecttable;

        // Set timemodified.
        $timemodified = time();

        // Get matching records.
        $records = $DB->get_recordset(
                'local_deepler',
                ['t_id' => $objectid, 't_table' => $objecttable],
                '',
                'id'
        );

        // Update s_lastmodified time.
        foreach ($records as $record) {
            $DB->update_record(
                    'local_deepler',
                    ['id' => $record->id, 's_lastmodified' => $timemodified]
            );
        }
        $records->close();
    }

    /**
     * Observer for course_module_updated event.
     *
     * @param \core\event\course_module_updated $event
     * @return void
     */
    public static function course_module_updated(\core\event\course_module_updated $event) {
        global $DB;

        // Get params.
        $objectid = $event->other['instanceid'];
        $objecttable = $event->other['modulename'];

        // Set timemodified.
        $timemodified = time();

        // Get matching records.
        $records = $DB->get_recordset(
                'local_deepler',
                ['t_id' => $objectid, 't_table' => $objecttable],
                '',
                '*'
        );

        // Update s_lastmodified time.
        foreach ($records as $record) {
            $DB->update_record(
                    'local_deepler',
                    ['id' => $record->id, 's_lastmodified' => $timemodified]
            );
        }
        $records->close();
    }

}
