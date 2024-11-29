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
 * Course Translator Upgrade
 *
 * Manage database migrations for local_deepler
 *
 * @package    local_deepler
 * @copyright  2022 Kaleb Heitzman <kaleb@jamfire.io>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/Upgrade_API
 */

/**
 * Course Translator Upgrade
 *
 * @param integer $oldversion
 * @return boolean
 */
function xmldb_local_deepler_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2022050100) {

        // Define table local_deepler to be created.
        $table = new xmldb_table('local_deepler');

        // Define fields to be added to local_deepler.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('t_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('t_lang', XMLDB_TYPE_CHAR, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('t_table', XMLDB_TYPE_CHAR, '55', null, XMLDB_NOTNULL, null, null);
        $table->add_field('t_field', XMLDB_TYPE_CHAR, '55', null, XMLDB_NOTNULL, null, null);
        $table->add_field('s_lastmodified', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('t_lastmodified', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);

        // Add keys to local_deepler.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Add indexes to local_deepler.
        $table->add_index('t_id_index', XMLDB_INDEX_NOTUNIQUE, ['t_id']);
        $table->add_index('t_lang_index', XMLDB_INDEX_NOTUNIQUE, ['t_lang']);

        // Conditionally launch create table for local_deepler.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Coursetranslator savepoint reached.
        upgrade_plugin_savepoint(true, 2022050100, 'local', 'deepler');
    }

    if ($oldversion < 2022050300) {

        // Coursetranslator savepoint reached.
        upgrade_plugin_savepoint(true, 2022050300, 'local', 'deepler');
    }

    return true;
}
