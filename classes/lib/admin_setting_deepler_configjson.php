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

namespace local_deepler\lib;

use admin_setting_configtextarea;
use core\notification;
use core_plugin_manager;
use JsonException;
use lang_string;
use xmldb_table;

/**
 * Admin setting textarea with JSON syntax and schema validation.
 *
 * Stores the additional_conf JSON in config_plugins so admins can edit it
 * through the Moodle UI without requiring server filesystem access.
 *
 * Validation is done entirely with PHP built-ins (json_decode) — no vendor
 * dependency required in this file.
 *
 * @package    local_deepler
 * @copyright  2026 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_deepler_configjson extends admin_setting_configtextarea {
    /**
     * Validate that the submitted value is either empty or valid JSON matching
     * the expected additional-conf schema.
     *
     * Empty is accepted — the plugin falls back to the bundled additional_conf.json.
     *
     * @param string $data
     * @return string|lang_string|true true if valid, error string if not
     */
    public function validate($data): string|lang_string|bool {
        if (empty(trim($data))) {
            return true;
        }

        // Pass 1: JSON syntax check.
        try {
            $config = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return get_string('additionalconf_parseerror', 'local_deepler', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);
        }

        // Pass 2: structural schema check.
        return $this->validateschema($config);
    }

    /**
     * Validate that the decoded config matches the expected structure:
     *   top-level object → plugin keys → table-definition objects → optional "fields" object.
     *
     * @param mixed $config decoded JSON (should be array after json_decode assoc=true)
     * @return true|string true on success, localised error string on failure
     */
    private function validateschema(mixed $config): true|string {
        $warnings = [];
        if (!is_array($config)) {
            return get_string('additionalconf_schema_root', 'local_deepler');
        }
        global $DB;
        $dbman = $DB->get_manager();
        foreach ($config as $pluginkey => $tables) {
            $pluginman = core_plugin_manager::instance();
            $info = $pluginman->get_plugin_info($pluginkey);

            if ($info === null || !$info->is_installed_and_upgraded()) {
                // Plugin is not installed.
                $warnings[] = get_string('additionalconf_err_plugnotfound', 'local_deepler', $pluginkey);
                continue;
            }

            if (!is_array($tables)) {
                return get_string('additionalconf_schema_plugin', 'local_deepler', $pluginkey);
            }
            foreach ($tables as $tablename => $tabledef) {
                $table = new xmldb_table($tablename);

                if (!$dbman->table_exists($table)) {
                    $warnings[] = get_string('additionalconf_err_tablenotfound', 'local_deepler', [
                        'name' => $tablename,
                        'plugin' => $pluginkey,
                    ]);
                    continue;
                }

                if (!is_array($tabledef)) {
                    return get_string('additionalconf_schema_table', 'local_deepler', $tablename);
                }
                $allowedkeys = ['id', 'fields'];

                $unknownkeys = array_diff(array_keys($tabledef), $allowedkeys);

                if (!empty($unknownkeys)) {
                    $warnings[] = get_string('additionalconf_warning_unknown_table_keys', 'local_deepler', [
                        'name' => $tablename,
                        'plugin' => $pluginkey,
                        'fields' => implode(', ', $allowedkeys),
                        'unknown' => implode(', ', $unknownkeys),
                    ]);
                }

                if (isset($tabledef['id']) && !is_string($tabledef['id'])) {
                    return get_string('additionalconf_schema_fields', 'local_deepler', $tablename);
                }
                if (isset($tabledef['fields'])) {
                    if (!is_array($tabledef['fields'])) {
                        return get_string('additionalconf_schema_fields', 'local_deepler', $tablename);
                    } else {
                        $fields = $tabledef['fields'];
                        $unknownfileds = [];
                        foreach ($fields as $fieldname => $fielddef) {
                            $allowedattributes = ['exclude', 'editable'];
                            if (!$dbman->field_exists($tablename, $fieldname)) {
                                $unknownfileds[] = $fieldname;
                            } else {
                                if (is_array($fielddef)) {
                                    $unknownattibutes = array_diff(array_keys($fielddef), $allowedattributes);
                                    if (!empty($unknownattibutes)) {
                                        $warnings[] = get_string(
                                            'additionalconf_warning_unknown_table_atributes',
                                            'local_deepler',
                                            [
                                                'name' => $tablename,
                                                'plugin' => $pluginkey,
                                                'fields' => implode(', ', $allowedattributes),
                                                'unknown' => implode(', ', $unknownattibutes),
                                            ]
                                        );
                                    }
                                }
                            }
                        }
                        if (!empty($unknownfileds)) {
                            $warnings[] = get_string('additionalconf_warning_unknown_field_table', 'local_deepler', [
                                'name' => $tablename,
                                'plugin' => $pluginkey,
                                'fields' => implode(', ', $unknownfileds),
                            ]);
                        }
                    }
                }
            }
        }
        if (!empty($warnings)) {
            array_unshift(
                $warnings,
                get_string('additionalconf_warning', 'local_deepler', get_string('additionalconf', 'local_deepler'))
            );
            notification::warning(implode('<br />', $warnings));
        }
        return true;
    }
}
