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
 * @throws \ddl_exception
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
    if ($oldversion < 2025043004) {

        // Define table local_deepler to be created.
        $table = new xmldb_table('local_deepler');
        // Clear the index to update the field.
        $langindex = new xmldb_index('t_lang_index', XMLDB_INDEX_NOTUNIQUE, ['t_lang']);
        if ($dbman->index_exists($table, $langindex)) {
            $dbman->drop_index($table, $langindex);
        }
        // Define fields to be added to local_deepler.
        $langfield = new xmldb_field('t_lang', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, '');

        // Change field length.
        $dbman->change_field_precision($table, $langfield);

        // Recreate the index.
        $dbman->add_index($table, $langindex);
        // Coursetranslator savepoint reached.
        upgrade_plugin_savepoint(true, 2025043004, 'local', 'deepler');
    }
    if ($oldversion < 2025070202) {

        // Define table local_deepler_tokens to be created.
        $table = new xmldb_table('local_deepler_tokens');

        // Add fields.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('attribute', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('valuefilter', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('token', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        // Add keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Add indexes.
        $table->add_index('token_attribute_index', XMLDB_INDEX_NOTUNIQUE, ['attribute']);
        $table->add_index('token_valuefilter_index', XMLDB_INDEX_NOTUNIQUE, ['valuefilter']);

        // Conditionally launch create table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Upgrade savepoint.
        upgrade_plugin_savepoint(true, 2025070202, 'local', 'deepler');
    }
    if ($oldversion < 2025080800) {

        // Define table local_deepler_glossaries.
        $table = new xmldb_table('local_deepler_glossaries');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('glossaryid', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sourcelang', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('targetlang', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('entrycount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('shared', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('tokenid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('lastused', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Create table if it doesn't exist.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_deepler_user_glossary.
        $table2 = new xmldb_table('local_deepler_user_glossary');

        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('userid', XMLDB_TYPE_INTEGER, '10', true, XMLDB_NOTNULL, null, null);
        $table2->add_field('glossaryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table2->add_field('isactive', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');

        $table2->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table2->add_key('course_glossary_idx', XMLDB_KEY_UNIQUE, ['userid', 'glossaryid']);

        // Create table if it doesn't exist.
        if (!$dbman->table_exists($table2)) {
            $dbman->create_table($table2);
        }

        // Upgrade savepoint.
        upgrade_plugin_savepoint(true, 2025080800, 'local', 'deepler');
    }
    return true;
}
