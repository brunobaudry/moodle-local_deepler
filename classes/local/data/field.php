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

namespace local_deepler\local\data;

use cm_info;
use coding_exception;
use local_deepler\local\services\utils;

/**
 * Class filed
 *
 * @package local_deepler
 * @copyright 2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class field {
    /**
     *
     *
     * @param \cm_info $cm
     * @return array
     * @throws \ddl_exception
     * @throws \dml_exception
     */
    public static function getadditionals(cm_info $cm): array {
        global $DB;
        $fields = [];
        $tables = self::$additionals['mod_' . $cm->modname] ?? [];
        if (empty($tables)) {
            return $fields;
        }
        foreach ($tables as $tablename => $tabledef) {
            $tabledefid = $tabledef['id'] ?? 'id';

            $configfields = $tabledef['fields'] ?? [];
            // Auto-discover: no configured fields → use all DB text columns.
            if (empty($configfields)) {
                if (!$DB->get_manager()->table_exists($tablename)) {
                    continue;
                }
                $rows = $DB->get_records($tablename, [$tabledefid => $cm->instance]);
                $textcols = self::filterdbtextfields($tablename);
                foreach ($rows as $record) {
                    foreach ($textcols as $col) {
                        if (trim($record->{$col}) === '') {
                            continue;
                        }
                        $fields[] = new self(
                            $record->id,
                            $record->{$col},
                            $record->{$col . 'format'} ?? 0,
                            $col,
                            $tablename,
                            $cm->id,
                            true
                        );
                    }
                }
                continue;
            }
            // Configured fields path — delegate to shared method.
            $fields = array_merge(
                $fields,
                self::buildfieldsfromtableconfig([$tablename => $tabledef], $cm->instance, $cm->id, $tabledefid)
            );
        }
        return $fields;
    }

    /**
     * Build field objects from a YAML table config section.
     *
     * Shared logic used by both getadditionals() and qbase::getadditionalsubs().
     *
     * @param array  $tableconfigs    e.g. ['qtype_essay_options' => ['id' => 'questionid', 'fields' => [...]]]
     * @param int    $fkvalue         Value to match the FK column (question id or module instance id)
     * @param int    $cmid            Course-module id for the field constructor
     * @param string $defaultfkcolumn FK column name when 'id' key is absent in config
     * @return array
     */
    public static function buildfieldsfromtableconfig(
        array $tableconfigs,
        int $fkvalue,
        int $cmid,
        string $defaultfkcolumn = 'questionid'
    ): array {
        global $DB;
        $fields = [];
        foreach ($tableconfigs as $tablename => $tabledef) {
            $fkcol        = $tabledef['id'] ?? $defaultfkcolumn;
            $configfields = $tabledef['fields'] ?? [];
            if (empty($configfields)) {
                continue;
            }
            if (!$DB->get_manager()->table_exists($tablename)) {
                continue;
            }
            $selectcols = implode(', ', array_merge(['id'], array_keys($configfields)));
            $rows = $DB->get_records($tablename, [$fkcol => $fkvalue], '', $selectcols);
            foreach ($rows as $row) {
                foreach ($configfields as $col => $clauses) {
                    $content = $row->{$col} ?? '';
                    if (trim($content) === '') {
                        continue;
                    }
                    // Unified exclude: string → value match; true/boolean → always skip.
                    if (isset($clauses['exclude'])) {
                        if ($clauses['exclude'] === true || $clauses['exclude'] === $content) {
                            continue;
                        }
                    }
                    $editable = $clauses['editable'] ?? true;
                    $format   = $row->{"{$col}format"} ?? 0;
                    $fields[] = new self($row->id, $content, $format, $col, $tablename, $cmid, $editable);
                }
            }
        }
        return $fields;
    }

    /** @var array */
    private static array $fieldlengths = [];

    /** @var int|null */
    private ?int $maxlength = null;
    /** @var int */
    public static int $countsimplefields = 0;
    /** @var int */
    public static int $countareafields = 0;

    /** @var string */
    public static string $targetlangdeepl = '';
    /**
     * @var int settings for the minimum text field size.
     */
    public static int $mintxtfieldsize = 254;
    /**
     * @var array customs columns to skip.
     */
    private static array $usercolstoskip = [];
    /**
     * @var array filtered table fields.
     */
    public static array $filteredtablefields = [];
    /**
     * @var mixed json additional db field config.
     */
    public static mixed $additionals;
    /** @var string */
    private string $text;
    /** @var string */
    private string $table;

    /** @var string */
    private string $field;
    /** @var int */
    private int $id;
    /** @var int */
    private int $cmid;
    /** @var int */
    private int $format;
    /** @var int */
    private int $tid;
    /** @var string */
    private string $displaytext;
    /** @var status */
    private status $status;
    /** @var bool */
    private bool $editable;

    /**
     * Create a new field to use as atomic source for translations.
     *
     * @param int $id
     * @param string $text
     * @param int $format
     * @param string $field
     * @param string $table
     * @param int $cmid
     * @param bool $editable
     * @throws \dml_exception
     */
    public function __construct(
        int $id,
        string $text,
        int $format,
        string $field,
        string $table,
        int $cmid = 0,
        bool $editable = true,
    ) {
        if (empty(self::$additionals)) {
            $jsonconfig = get_config('local_deepler', 'additionalconf');
            if ($jsonconfig !== false && $jsonconfig !== '') {
                self::$additionals = json_decode($jsonconfig, true);
            } else {
                // Fallback: config not yet seeded (e.g. CLI/test context before install runs).
                self::$additionals = json_decode(
                    file_get_contents(utils::get_plugin_root() . '/additional_conf.json'),
                    true
                );
            }
        }
        $this->id = $id;
        $this->editable = $editable;
        $this->field = $field;
        $this->table = $table;
        $this->format = $format;
        $this->cmid = $cmid;
        $this->displaytext = $this->text = $text;
        if ($this->format === 1) {
            self::$countareafields++;
            $this->preparetexts();
        } else {
            self::$countsimplefields++;
        }
        $this->init_db();
    }

    /**
     * Is false use it to display only (cannot translate)
     *
     * @return bool
     */
    public function iseditable(): bool {
        return $this->editable;
    }

    /**
     * Getter
     *
     * @return int|null
     */
    public function get_maxlength(): ?int {
        return $this->maxlength;
    }

    /**
     * Getter for id.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Getter for cmid.
     *
     * @return int
     */
    public function get_cmid(): int {
        return $this->cmid;
    }

    /**
     * Getter for field.
     *
     * @return string
     */
    public function get_tablefield(): string {
        return $this->field;
    }

    /**
     * Getter for table.
     *
     * @return string
     */
    public function get_table(): string {
        return $this->table;
    }

    /**
     * Getter for status.
     *
     * @return \local_deepler\local\data\status
     */
    public function get_status(): status {
        return $this->status;
    }

    /**
     * Getter for text.
     *
     * @return string
     */
    public function get_text(): string {
        return $this->text;
    }

    /**
     * Getter for table id.
     *
     * @return int
     */
    public function get_tid(): int {
        return $this->tid;
    }

    /**
     * Getter for field format 0 or 1.
     *
     * @return int
     */
    public function get_format(): int {
        return $this->format;
    }

    /**
     * Getter for the display text (to display on the translation page).
     *
     * @return string
     */
    public function get_displaytext(): string {
        return $this->displaytext;
    }

    /**
     * Getter for the translated string field name.
     *
     * @return string
     *
     * public function get_translatedfieldname(): string {
     * return $this->translatedfieldname;
     * }*/

    /**
     * Generate the field key identifier.
     *
     * @return string
     */
    public function getkey(): string {
        return "$this->table[$this->id][$this->field][$this->cmid]";
    }

    /**
     * Generate the field key identifier other format.
     *
     * @return string
     */
    public function getkeyid(): string {
        return "{$this->table}-{$this->id}-{$this->field}-{$this->cmid}";
    }

    /**
     * Checks if the multilang tag OTHER and the current/source language is already there to warn the user that the tags will be
     * overridden and deleted.
     *
     * @param string $lang
     * @return bool
     */
    public function check_field_has_other_and_sourcetag(string $lang): bool {
        return str_contains($this->text, '{mlang other}') && str_contains($this->text, "{mlang $lang");
    }

    /**
     * Building the text attributes.
     *
     * @return void
     * @throws \dml_exception
     */
    private function preparetexts(): void {
        if (str_contains($this->displaytext, '@@PLUGINFILE@@')) {
            $this->displaytext = utils::resolve_pluginfiles($this);
        }
    }

    /**
     * Stores the translation's statuses.
     *
     * @return void
     */
    private function init_db(): void {
        $this->status = new status($this->id, $this->table, $this->field, self::$targetlangdeepl);
        // Skip if target lang is undefined.
        if ($this->status->isready()) {
            $this->status->getupdate();
        }
        $this->tid = $this->status->get_id();
    }

    /**
     * Filter the text fields of a table.
     *
     * @param string $tablename
     * @return mixed
     */
    public static function filterdbtextfields(string $tablename): mixed {
        if (!isset(self::$filteredtablefields[$tablename])) {
            global $DB;
            // We build an array of all Text fields for this record.
            $columns = $DB->get_columns($tablename);
            // Just get db collumns we need (texts content).
            $filtered = [];
            foreach ($columns as $name => $field) {
                if (
                    (($field->meta_type === "C" && $field->max_length > self::$mintxtfieldsize) ||
                        $field->meta_type === "X") &&
                    !in_array('*_' . $field->name, ['*_displayoptions', '*_stamp']) &&
                    !in_array($tablename . '_' . $field->name, self::getcolstoskip())
                ) {
                    $filtered[] = $field->name;
                    self::$fieldlengths[$tablename][$field->name] = $field->max_length ?? null;
                }
            }
            self::$filteredtablefields[$tablename] = $filtered;
        }
        return self::$filteredtablefields[$tablename];
    }

    /**
     * Get the fields from the columns.
     *
     * @param mixed $info
     * @param string $table
     * @param array $collumns
     * @param int $cmid
     * @return array
     */
    public static function getfieldsfromcolumns(mixed $info, string $table, array $collumns, int $cmid = 0): array {
        $infos = [];
        foreach ($collumns as $collumn => $clauses) {
            $fieldtextformat = "{$collumn}format";
            $editable = true;
            if (!isset($info->{$collumn})) {
                continue;
            }
            if ($clauses) {
                if ($clauses['exclude'] && (trim($info->{$collumn}) === trim($clauses['exclude']))) {
                    continue;
                }
                if (isset($clauses['editable'])) {
                    $editable = $clauses['editable'];
                }
            }
            if ($info->{$collumn} !== '' && is_string($info->{$collumn})) {
                $fieldobject = new field(
                    $info->id,
                    $info->{$collumn},
                    $info->{$fieldtextformat} ?? 0,
                    $collumn,
                    $table,
                    $cmid,
                    $editable
                );

                $fieldobject->maxlength = self::$fieldlengths[$table][$collumn] ?? null;

                $infos[] = $fieldobject;
            }
        }
        return $infos;
    }

    /**
     * Get the fields from the course module info.
     *
     * @param \cm_info $cminfo
     * @return array
     * @throws \dml_exception
     */
    public static function getfieldsfrominfo(cm_info $cminfo): array {
        global $DB;
        $mod = $cminfo->modname;
        // Get all the fields as CMINFO does not carry them all.
        $filters = [];
        $addfields = self::$additionals['mod_' . $mod][$mod]['fields'];
        if (!empty($addfields)) {
            $filters = $addfields;
        }
        $activitydbrecord = $DB->get_record($mod, ['id' => $cminfo->instance]);
        $infocols = self::filterdbtextfields($cminfo->modname);
        $filteredfileds = [];
        $keys = array_keys($filters);
        foreach ($infocols as $infocol) {
            if (isset($filters[$infocol])) {
                $filteredfileds[$infocol] = $filters[$infocol];
            } else {
                $filteredfileds[$infocol] = [];
            }
        }
        $infofields = self::getfieldsfromcolumns($activitydbrecord, $mod, $filteredfileds, $cminfo->id);
        return $infofields;
    }

    /**
     * Create a class from a string and DB record.
     *
     * @param string $name
     * @param mixed $record
     * @return mixed
     */
    public static function createclassfromstring(string $name, mixed $record): mixed {
        $class = "\\local_deepler\\local\\data\\subs\\{$name}";
        if (class_exists($class)) {
            return new $class($record);
        }
        return null;
    }

    /**
     * Prepare the array of default table columns to skip including the users.
     *
     * @return array|string[]
     */
    private static function getcolstoskip(): array {
        $modcolstoskip =
            ['url_parameters', 'hotpot_outputformat', 'hvp_authors', 'hvp_changes', 'lesson_conditions',
                'scorm_reference', 'studentquiz_allowedqtypes', 'studentquiz_excluderoles',
                'studentquiz_reportingemail',
                'survey_questions', 'data_csstemplate', 'data_config', 'wiki_firstpagetitle',
                'bigbluebuttonbn_moderatorpass', 'bigbluebuttonbn_participants', 'bigbluebuttonbn_guestpassword',
                'rattingallocate_setting', 'rattingallocate_strategy', 'hvp_json_content', 'hvp_filtered', 'hvp_slug',
                'wooclap_linkedwooclapeventslug', 'wooclap_wooclapeventid', 'kalvidres_metadata', 'filetypelist',
            ];
        return array_merge(self::$usercolstoskip, $modcolstoskip);
    }

    /**
     * Set the columns to skip.
     *
     * @param string $key
     * @return array
     * @throws \coding_exception
     */
    public static function generatedatfromkey(string $key) {
        $pattern = "/(\w+)\[(\d+)\]\[(\w+)\]\[(\d+)\]/si";
        preg_match($pattern, $key, $matches);
        if (count($matches) !== 5) {
            throw new coding_exception('Invalid key format');
        }
        return [
            'table' => $matches[1],
            'id' => $matches[2],
            'field' => $matches[3],
            'cmid' => $matches[4],
        ];
    }
}
