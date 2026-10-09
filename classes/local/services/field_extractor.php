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

use cm_info;
use coding_exception;
use local_deepler\local\data\field;
use moodle_database;
use stdClass;

/**
 * Service for extracting, reflecting, and instantiating translatable fields from database records.
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class field_extractor {
    /** @var \moodle_database */
    private moodle_database $db;

    /** @var array Cached additional db field configuration */
    private array $additionals = [];

    /** @var array Cached filtered table text columns */
    private array $filteredtablefields = [];

    /** @var array Cached column max lengths by table and field */
    private array $fieldlengths = [];

    /** @var int Minimum text field size threshold */
    private int $mintxtfieldsize;

    /** @var array Custom columns to skip */
    private array $usercolstoskip = [];

    /**
     * Constructor for field_extractor service.
     *
     * @param \moodle_database|null $db Database interface instance.
     * @param array|null $additionals Optional preloaded additional field configs.
     * @param int|null $mintxtfieldsize Minimum length threshold for text columns.
     */
    public function __construct(
        ?moodle_database $db = null,
        ?array $additionals = null,
        ?int $mintxtfieldsize = null
    ) {
        global $DB;
        $this->db = $db ?? $DB;
        if ($additionals !== null) {
            $this->additionals = $additionals;
        }
        if ($mintxtfieldsize !== null) {
            $this->mintxtfieldsize = $mintxtfieldsize;
        } else {
            $scannedsize = get_config('local_deepler', 'scannedfieldsize');
            $this->mintxtfieldsize = ($scannedsize !== false && $scannedsize !== '') ? (int) $scannedsize : 254;
        }
    }

    /**
     * Load the additional db field config from admin settings or bundled json fallback.
     *
     * @return array
     * @throws \dml_exception
     */
    public function load_additionals(): array {
        if (empty($this->additionals)) {
            $jsonconfig = get_config('local_deepler', 'additionalconf');
            $decoded = null;
            if ($jsonconfig !== false && $jsonconfig !== '') {
                $decoded = json_decode($jsonconfig, true);
            }
            if (!is_array($decoded)) {
                // Fallback: config not yet seeded or invalid json.
                $decoded = json_decode(
                    file_get_contents(utils::get_plugin_root() . '/additional_conf.json'),
                    true
                );
            }
            $this->additionals = is_array($decoded) ? $decoded : [];
        }
        return $this->additionals;
    }

    /**
     * Backward-compatibility alias for load_additionals.
     *
     * @return array
     * @throws \dml_exception
     */
    public function loadadditionals(): array {
        return $this->load_additionals();
    }

    /**
     * Set additionals config explicitly (useful for unit testing).
     *
     * @param array $additionals
     * @return void
     */
    public function set_additionals(array $additionals): void {
        $this->additionals = $additionals;
    }

    /**
     * Reset cached schema reflections.
     *
     * @return void
     */
    public function reset_cache(): void {
        $this->filteredtablefields = [];
        $this->fieldlengths = [];
    }

    /**
     * Get additional fields for a course module.
     *
     * @param \cm_info $cm
     * @return array
     * @throws \ddl_exception
     * @throws \dml_exception
     */
    public function get_additionals(cm_info $cm): array {
        $tables = $this->load_additionals()['mod_' . $cm->modname] ?? [];
        if (empty($tables)) {
            return [];
        }

        $fields = [];
        foreach ($tables as $tablename => $tabledef) {
            // The module's own table is handled by get_fields_from_info().
            if ($tablename === $cm->modname) {
                continue;
            }

            $fields = array_merge($fields, $this->get_table_additionals($tablename, $tabledef, $cm));
        }
        return $fields;
    }

    /**
     * Backward-compatibility alias for get_additionals.
     *
     * @param \cm_info $cm
     * @return array
     * @throws \ddl_exception
     * @throws \dml_exception
     */
    public function getadditionals(cm_info $cm): array {
        return $this->get_additionals($cm);
    }

    /**
     * Get additional fields for a specific table definition.
     *
     * @param string $tablename
     * @param array $tabledef
     * @param \cm_info $cm
     * @return array
     * @throws \ddl_exception
     * @throws \dml_exception
     */
    private function get_table_additionals(string $tablename, array $tabledef, cm_info $cm): array {
        $tabledefid = $tabledef['id'] ?? 'id';
        $configfields = $tabledef['fields'] ?? [];

        // Auto-discover: no configured fields → use all DB text columns.
        if (empty($configfields)) {
            return $this->get_autodiscovered_table_fields($tablename, $tabledefid, $cm);
        }

        // Configured fields path — delegate to shared method.
        return $this->build_fields_from_table_config([$tablename => $tabledef], $cm->instance, $cm->id, $tabledefid);
    }

    /**
     * Auto-discover and create field instances for all text columns of a table.
     *
     * @param string $tablename
     * @param string $tabledefid
     * @param \cm_info $cm
     * @return array
     * @throws \ddl_exception
     * @throws \dml_exception
     */
    private function get_autodiscovered_table_fields(string $tablename, string $tabledefid, cm_info $cm): array {
        if (!$this->db->get_manager()->table_exists($tablename)) {
            return [];
        }

        $fields = [];
        $rows = $this->db->get_records($tablename, [$tabledefid => $cm->instance]);
        $textcols = $this->filter_db_text_fields($tablename);

        foreach ($rows as $record) {
            foreach ($textcols as $col) {
                if (trim($record->{$col}) === '') {
                    continue;
                }
                $fields[] = new field(
                    $record->id,
                    $record->{$col},
                    $record->{$col . 'format'} ?? 0,
                    $col,
                    $tablename,
                    $cm->id,
                    true,
                    $this->fieldlengths[$tablename][$col] ?? null
                );
            }
        }

        return $fields;
    }

    /**
     * Build field objects from a table config section.
     *
     * @param array $tableconfigs e.g. ['qtype_essay_options' => ['id' => 'questionid', 'fields' => [...]]]
     * @param int $fkvalue Value to match the FK column (question id or module instance id)
     * @param int $cmid Course-module id for the field constructor
     * @param string $defaultfkcolumn FK column name when 'id' key is absent in config
     * @return array
     */
    public function build_fields_from_table_config(
        array $tableconfigs,
        int $fkvalue,
        int $cmid,
        string $defaultfkcolumn = 'questionid'
    ): array {
        $fields = [];
        foreach ($tableconfigs as $tablename => $tabledef) {
            $tablefields = $this->build_single_table_config_fields(
                $tablename,
                $tabledef,
                $fkvalue,
                $cmid,
                $defaultfkcolumn
            );
            $fields = array_merge($fields, $tablefields);
        }
        return $fields;
    }

    /**
     * Backward-compatibility alias for build_fields_from_table_config.
     *
     * @param array $tableconfigs
     * @param int $fkvalue
     * @param int $cmid
     * @param string $defaultfkcolumn
     * @return array
     */
    public function buildfieldsfromtableconfig(
        array $tableconfigs,
        int $fkvalue,
        int $cmid,
        string $defaultfkcolumn = 'questionid'
    ): array {
        return $this->build_fields_from_table_config($tableconfigs, $fkvalue, $cmid, $defaultfkcolumn);
    }

    /**
     * Build field objects for a single table config.
     *
     * @param string $tablename
     * @param array $tabledef
     * @param int $fkvalue
     * @param int $cmid
     * @param string $defaultfkcolumn
     * @return array
     */
    private function build_single_table_config_fields(
        string $tablename,
        array $tabledef,
        int $fkvalue,
        int $cmid,
        string $defaultfkcolumn
    ): array {
        $configfields = $tabledef['fields'] ?? [];
        if (empty($configfields) || !$this->db->get_manager()->table_exists($tablename)) {
            return [];
        }

        $fkcol = $tabledef['id'] ?? $defaultfkcolumn;
        $selectcols = $this->get_table_select_columns($tablename, $configfields);
        $rows = $this->db->get_records($tablename, [$fkcol => $fkvalue], '', $selectcols);

        $fields = [];
        foreach ($rows as $row) {
            $fields = array_merge($fields, $this->build_row_config_fields($row, $tablename, $configfields, $cmid));
        }
        return $fields;
    }

    /**
     * Get SELECT columns list for a table based on its config fields.
     *
     * @param string $tablename
     * @param array $configfields
     * @return string
     */
    private function get_table_select_columns(string $tablename, array $configfields): string {
        $dbcols = $this->db->get_columns($tablename);
        $cols = ['id'];
        foreach (array_keys($configfields) as $col) {
            if (!isset($dbcols[$col])) {
                continue;
            }
            $cols[] = $col;
            if (isset($dbcols[$col . 'format'])) {
                $cols[] = $col . 'format';
            }
        }
        return implode(', ', array_unique($cols));
    }

    /**
     * Build fields for a single record row.
     *
     * @param \stdClass $row
     * @param string $tablename
     * @param array $configfields
     * @param int $cmid
     * @return array
     */
    private function build_row_config_fields(
        stdClass $row,
        string $tablename,
        array $configfields,
        int $cmid
    ): array {
        $fields = [];
        foreach ($configfields as $col => $clauses) {
            $field = $this->build_single_config_field($row, $tablename, $col, $clauses, $cmid);
            if ($field !== null) {
                $fields[] = $field;
            }
        }
        return $fields;
    }

    /**
     * Build a single field object from row data if valid and not excluded.
     *
     * @param \stdClass $row
     * @param string $tablename
     * @param string $col
     * @param mixed $clauses
     * @param int $cmid
     * @return field|null
     */
    private function build_single_config_field(
        stdClass $row,
        string $tablename,
        string $col,
        mixed $clauses,
        int $cmid
    ): ?field {
        $content = $row->{$col} ?? '';
        if (trim($content) === '' || $this->is_field_excluded($clauses, $content)) {
            return null;
        }

        $editable = $clauses['editable'] ?? true;
        $format = $row->{"{$col}format"} ?? 0;
        return new field(
            $row->id,
            $content,
            $format,
            $col,
            $tablename,
            $cmid,
            $editable,
            $this->fieldlengths[$tablename][$col] ?? null
        );
    }

    /**
     * Check whether a field value matches an exclusion rule.
     *
     * @param mixed $clauses
     * @param string $content
     * @return bool
     */
    private function is_field_excluded(mixed $clauses, string $content): bool {
        if (!is_array($clauses) || !isset($clauses['exclude'])) {
            return false;
        }
        $exclude = $clauses['exclude'];
        if ($exclude === true) {
            return true;
        }
        if (is_string($exclude) && trim($content) === trim($exclude)) {
            return true;
        }
        return false;
    }

    /**
     * Filter the text fields of a table.
     *
     * @param string $tablename
     * @return array
     */
    public function filter_db_text_fields(string $tablename): array {
        if (!isset($this->filteredtablefields[$tablename])) {
            $columns = $this->db->get_columns($tablename);
            $filtered = [];
            foreach ($columns as $name => $field) {
                if (
                    (($field->meta_type === "C" && $field->max_length > $this->mintxtfieldsize) ||
                        $field->meta_type === "X") &&
                    !in_array('*_' . $field->name, ['*_displayoptions', '*_stamp']) &&
                    !in_array($tablename . '_' . $field->name, $this->get_cols_to_skip())
                ) {
                    $filtered[] = $field->name;
                    $this->fieldlengths[$tablename][$field->name] = $field->max_length ?? null;
                }
            }
            $this->filteredtablefields[$tablename] = $filtered;
        }
        return $this->filteredtablefields[$tablename];
    }

    /**
     * Backward-compatibility alias for filter_db_text_fields.
     *
     * @param string $tablename
     * @return array
     */
    public function filterdbtextfields(string $tablename): array {
        return $this->filter_db_text_fields($tablename);
    }

    /**
     * Get the fields from record columns.
     *
     * @param mixed $info
     * @param string $table
     * @param array $columns
     * @param int $cmid
     * @return array
     */
    public function get_fields_from_columns(mixed $info, string $table, array $columns, int $cmid = 0): array {
        $infos = [];
        foreach ($columns as $column => $clauses) {
            $fieldobject = $this->extract_column_field($info, $table, $column, $clauses, $cmid);
            if ($fieldobject !== null) {
                $infos[] = $fieldobject;
            }
        }
        return $infos;
    }

    /**
     * Backward-compatibility alias for get_fields_from_columns.
     *
     * @param mixed $info
     * @param string $table
     * @param array $columns
     * @param int $cmid
     * @return array
     */
    public function getfieldsfromcolumns(mixed $info, string $table, array $columns, int $cmid = 0): array {
        return $this->get_fields_from_columns($info, $table, $columns, $cmid);
    }

    /**
     * Extract a single field instance from record columns.
     *
     * @param mixed $info
     * @param string $table
     * @param string $column
     * @param mixed $clauses
     * @param int $cmid
     * @return field|null
     */
    private function extract_column_field(
        mixed $info,
        string $table,
        string $column,
        mixed $clauses,
        int $cmid
    ): ?field {
        if (!isset($info->{$column})) {
            return null;
        }

        $content = $info->{$column};
        if (!is_string($content) || $content === '' || $this->is_field_excluded($clauses, $content)) {
            return null;
        }

        $editable = true;
        if (is_array($clauses) && isset($clauses['editable'])) {
            $editable = (bool) $clauses['editable'];
        }

        $fieldtextformat = "{$column}format";
        $maxlength = $this->fieldlengths[$table][$column] ?? null;

        return new field(
            $info->id,
            $content,
            $info->{$fieldtextformat} ?? 0,
            $column,
            $table,
            $cmid,
            $editable,
            $maxlength
        );
    }

    /**
     * Get the fields from the course module info.
     *
     * @param \cm_info $cminfo
     * @return array
     * @throws \dml_exception
     */
    public function get_fields_from_info(cm_info $cminfo): array {
        $mod = $cminfo->modname;
        $filters = $this->load_additionals()['mod_' . $mod][$mod]['fields'] ?? [];
        if (!is_array($filters)) {
            $filters = [];
        }
        $activitydbrecord = $this->db->get_record($mod, ['id' => $cminfo->instance]);
        $infocols = $this->filter_db_text_fields($cminfo->modname);
        $filteredfileds = [];
        if (!empty($filters)) {
            // A configured module table is restrictive: only the listed columns are used.
            foreach ($filters as $col => $clauses) {
                if (in_array($col, $infocols, true)) {
                    $filteredfileds[$col] = $clauses ?? [];
                }
            }
        } else {
            foreach ($infocols as $infocol) {
                $filteredfileds[$infocol] = [];
            }
        }
        return $this->get_fields_from_columns($activitydbrecord, $mod, $filteredfileds, $cminfo->id);
    }

    /**
     * Backward-compatibility alias for get_fields_from_info.
     *
     * @param \cm_info $cminfo
     * @return array
     * @throws \dml_exception
     */
    public function getfieldsfrominfo(cm_info $cminfo): array {
        return $this->get_fields_from_info($cminfo);
    }

    /**
     * Create a class from a string and DB record.
     *
     * @param string $name
     * @param mixed $record
     * @return mixed
     */
    public function create_class_from_string(string $name, mixed $record): mixed {
        $class = "\\local_deepler\\local\\data\\subs\\{$name}";
        if (class_exists($class)) {
            return new $class($record);
        }
        return null;
    }

    /**
     * Backward-compatibility alias for create_class_from_string.
     *
     * @param string $name
     * @param mixed $record
     * @return mixed
     */
    public function createclassfromstring(string $name, mixed $record): mixed {
        return $this->create_class_from_string($name, $record);
    }

    /**
     * Prepare the array of default table columns to skip including custom ones.
     *
     * @return array
     */
    private function get_cols_to_skip(): array {
        $modcolstoskip = [
            'url_parameters', 'hotpot_outputformat', 'hvp_authors', 'hvp_changes', 'lesson_conditions',
            'scorm_reference', 'studentquiz_allowedqtypes', 'studentquiz_excluderoles',
            'studentquiz_reportingemail',
            'survey_questions', 'data_csstemplate', 'data_config', 'data_jstemplate', 'data_rsstemplate',
            'data_rsstitletemplate', 'data_asearchtemplate', 'wiki_firstpagetitle',
            'bigbluebuttonbn_moderatorpass', 'bigbluebuttonbn_participants', 'bigbluebuttonbn_guestpassword',
            'rattingallocate_setting', 'rattingallocate_strategy', 'hvp_json_content', 'hvp_filtered', 'hvp_slug',
            'wooclap_linkedwooclapeventslug', 'wooclap_wooclapeventid', 'kalvidres_metadata', 'filetypelist',
        ];
        return array_merge($this->usercolstoskip, $modcolstoskip);
    }

    /**
     * Parse field key and extract table, id, field, cmid components.
     *
     * @param string $key
     * @return array
     * @throws \coding_exception
     */
    public static function generate_data_from_key(string $key): array {
        $pattern = "/(\w+)\[(\d+)\]\[(\w+)\]\[(\d+)\]/si";
        preg_match($pattern, $key, $matches);
        if (count($matches) !== 5) {
            throw new coding_exception('Invalid key format');
        }
        return [
            'table' => $matches[1],
            'id' => (int) $matches[2],
            'field' => $matches[3],
            'cmid' => (int) $matches[4],
        ];
    }

    /**
     * Backward-compatibility alias for generate_data_from_key.
     *
     * @param string $key
     * @return array
     * @throws \coding_exception
     */
    public static function generatedatfromkey(string $key): array {
        return self::generate_data_from_key($key);
    }
}
