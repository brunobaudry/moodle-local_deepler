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
use context_course;
use context_module;
use moodle_url;

/**
 * Class filed
 *
 * @package local_deepler
 * @copyright 2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class field {
    /** @var string */
    public static string $targetlangdeepl = '';
    /**
     * @var int settings for the minimum text field size.
     */
    static public int $mintxtfieldsize = 254;
    /**
     * @var array common columns to skip.
     */
    static private array $comoncolstoskip = ['*_displayoptions', '*_stamp'];
    /**
     * @var array|string[] columns to skip.
     */
    static private array $modcolstoskip =
            ['url_parameters', 'hotpot_outputformat', 'hvp_authors', 'hvp_changes', 'lesson_conditions',
                    'scorm_reference', 'studentquiz_allowedqtypes', 'studentquiz_excluderoles', 'studentquiz_reportingemail',
                    'survey_questions', 'data_csstemplate', 'data_config', 'wiki_firstpagetitle',
                    'bigbluebuttonbn_moderatorpass', 'bigbluebuttonbn_participants', 'bigbluebuttonbn_guestpassword',
                    'rattingallocate_setting', 'rattingallocate_strategy', 'hvp_json_content', 'hvp_filtered', 'hvp_slug',
                    'wooclap_linkedwooclapeventslug', 'wooclap_wooclapeventid', 'kalvidres_metadata', 'filetypelist',
            ];
    /**
     * @var array customs columns to skip. @todo MDL-000 should be in a config.
     */
    static private array $usercolstoskip = [];
    /**
     * @var array filtered table fields.
     */
    static private array $filteredtablefields = [];
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
    /** @var bool @TODO MDL-000 should be called via status. */
    private bool $tneeded;
    /** @var int */
    private int $format;
    /** @var int */
    private int $tid;
    /** @var string */
    private string $displaytext;
    /** @var string */
    private string $translatedfieldname;
    /** @var status */
    private status $status;

    /**
     * Create a new field to use as atomic source for translations.
     *
     * @param int $id
     * @param string $text
     * @param int $format
     * @param string $field
     * @param string $table
     * @param int $cmid
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function __construct(
            int $id,
            string $text,
            int $format,
            string $field,
            string $table,
            int $cmid = 0
    ) {
        $this->id = $id;
        $this->field = $field;
        $this->table = $table;
        $this->format = $format;
        $this->cmid = $cmid;
        $this->translatedfieldname = '';
        $this->tneeded = true;
        $this->preparetexts($text);
        $this->init_db();
        $this->search_field_strings();
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
     */
    public function get_translatedfieldname(): string {
        return $this->translatedfieldname;
    }

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
     * As the title says.
     *
     * @return bool
     */
    public function has_multilang(): bool {
        return str_contains($this->text, '{mlang}');
    }
    /**
     * Building the text attributes.
     *
     * @param string $text
     * @return void
     */
    private function preparetexts(string $text) {
        $this->displaytext = $this->text = $text;
        if (str_contains($text, '@@PLUGINFILE@@')) {
            $this->displaytext = $this->resolve_pluginfiles($text, $this->table, $this->id, $this->cmid);
        }
    }

    /**
     * Stores the translation's statuses.
     *
     * @return void
     */
    private function init_db() {
        $this->status = new status($this->id, $this->table, $this->field, self::$targetlangdeepl);
        // Skip if target lang is undefined.
        if ($this->status->isready()) {
            $this->status->getupdate();
        }
        $this->tid = $this->status->id;
        $this->tneeded = $this->status->istranslationneeded();
    }

    /**
     * Try to find the string of each fields of mod/plugin.
     *
     * @return void
     * @throws \coding_exception
     */
    private function search_field_strings(): void {
        // Try to find the activity names as well as the field translated in the current lang.
        if ($this->table === 'course') {
            $this->translatedfieldname = get_string($this->field);
        } else if ($this->table === 'course_sections') {
            if ($this->field === 'name') {
                $this->translatedfieldname = get_string('sectionname');
            } else if ($this->field === 'summary') {
                $this->translatedfieldname = get_string('description');
            }
        } else {
            if ($this->field === 'intro') {
                $this->translatedfieldname = get_string('description');
            } else if ($this->field === 'name') {
                $this->translatedfieldname = get_string('name');
            } else {
                // One should be better than the other.
                $this->translatedfieldname = $this->findoutstanding();
            }
        }
    }

    /**
     * Find the string in the Moodle database.
     *
     * @return string
     */
    private function findoutstanding(): string {
        $foundstring = $this->field;

        // Extract plugin component from table name.
        $tableparts = explode('_', $this->table, 2);
        $plugincomponent = isset($tableparts[1]) ? 'mod_' . $tableparts[0] : '';

        $candidates = [
                ['identifier' => $this->field, 'component' => $plugincomponent], // Highest priority: Direct field name in plugin.
                ['identifier' => $this->field, 'component' => 'core'], // Standard Moodle core strings.
                ['identifier' => $this->field, 'component' => 'moodle'], // Standard Moodle core strings.
                ['identifier' => $this->table . '_' . $this->field, 'component' => $plugincomponent], // Common field patterns.
                ['identifier' => $this->table . '_' . $this->field, 'component' => 'core'], // Common field patterns.
                ['identifier' => $this->field, 'component' => 'datafield_' . $this->field], // Field type specific (data activity).
                ['identifier' => $this->table . $this->field, 'component' => $plugincomponent], // Legacy patterns.
        ];
        foreach ($candidates as $candidate) {
            if (empty($candidate['component'])) {
                continue;
            }

            if (get_string_manager()->string_exists($candidate['identifier'], $candidate['component'])) {
                return get_string($candidate['identifier'], $candidate['component']);
            }
        }

        return $foundstring;
    }

    /**
     * Unified file URL resolver with context-aware processing.
     *
     * @param string $text Content containing @@PLUGINFILE@@ references
     * @param string $table Entity type (course|course_sections|mod_name)
     * @param int $itemid Entity ID from specified table
     * @param string $filearea File area identifier
     * @param int $cmid Course module ID (for activities)
     * @return string Processed text with valid URLs
     */
    private function resolve_pluginfiles(string $text, string $table, int $itemid,
            string $filearea, int $cmid = 0): string {
        $contextinfo = $this->get_context_info($table, $itemid, $cmid);
        $fs = get_file_storage();

        try {
            $files = $fs->get_area_files(
                    $contextinfo['contextid'],
                    $contextinfo['component'],
                    $filearea,
                    $contextinfo['itemid'],
                    'filename, filepath',
                    false
            );

            foreach ($files as $file) {
                $url = moodle_url::make_pluginfile_url(
                        $file->get_contextid(),
                        $file->get_component(),
                        $file->get_filearea(),
                        $file->get_itemid(),
                        $file->get_filepath(),
                        $file->get_filename()
                );

                $text = str_replace(
                        '@@PLUGINFILE@@/' . $file->get_filename(),
                        $url->out(),
                        $text
                );
            }
        } catch (Exception $e) {
            debugging('File processing error: ' . $e->getMessage());
        }

        return $text;
    }


    /**
     * Context resolution optimized for Moodle's hierarchy.
     *
     * @param string $table
     * @param int $itemid
     * @param int $cmid
     * @return array
     */
    private function get_context_info(string $table, int $itemid, int $cmid = 0): array {
        switch ($table) {
            case 'course':
                $context = context_course::instance($itemid);
                return [
                        'contextid' => $context->id,
                        'component' => 'course',
                        'itemid' => $itemid,
                ];

            case 'course_sections':
                $context = context_course::instance(
                        get_field_sql("SELECT course FROM {course_sections} WHERE id = ?", [$itemid])
                );
                return [
                        'contextid' => $context->id,
                        'component' => 'course',
                        'itemid' => $itemid,
                ];

            default: // Activity modules.
                $context = context_module::instance($cmid);
                return [
                        'contextid' => $context->id,
                        'component' => 'mod_' . $table,
                        'itemid' => $cmid,
                ];
        }
    }

    /**
     * Filter the text fields of a table.
     *
     * @param $tablename
     * @return int[]|mixed|string[]
     */
    public static function filterdbtextfields($tablename) {
        if (!isset(self::$filteredtablefields[$tablename])) {
            global $DB;
            // We build an array of all Text fields for this record.
            $columns = $DB->get_columns($tablename);
            // Just get db collumns we need (texts content).
            $textcols = array_filter($columns, function($field) use ($tablename) {
                // Only scan the main text types that are above minîmum text field size.
                return (($field->meta_type === "C" && $field->max_length > self::$mintxtfieldsize)
                                || $field->meta_type === "X")
                        && !in_array('*_' . $field->name, self::$comoncolstoskip)
                        && !in_array($tablename . '_' . $field->name, self::$usercolstoskip)
                        && !in_array($tablename . '_' . $field->name, self::$modcolstoskip);
            });
            self::$filteredtablefields[$tablename] = array_keys($textcols);
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
        foreach ($collumns as $collumn) {
            $fieldtextformat = "{$collumn}format";
            if ($info->{$collumn} !== null && $info->{$collumn} !== '' && is_string($info->{$collumn})) {
                $infos[] = new field(
                        $info->id,
                        $info->{$collumn},
                        $info->{$fieldtextformat} ?? 0,
                        $collumn,
                        $table,
                        $cmid
                );
            }
        }
        return $infos;
    }

    /**
     * Get the fields from the course module info.
     *
     * @param \cm_info $cminfo
     * @return array
     */
    public static function getfieldsfrominfo(cm_info $cminfo): array {
        $mod = $cminfo->modname;
        return self::getfieldsfromcolumns($cminfo, $mod, self::filterdbtextfields($cminfo->modname), $cminfo->id);
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

}
