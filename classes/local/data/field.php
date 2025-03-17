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
use course_modinfo;
use moodle_url;
use TypeError;

class field {
    public static string $targetlangdeepl = '';
    public static course_modinfo $modinfo;
    static public int $mintxtfieldsize = 254;
    static private array $comoncolstoskip = ['*_displayoptions', '*_stamp'];
    static private array $modcolstoskip =
            ['url_parameters', 'hotpot_outputformat', 'hvp_authors', 'hvp_changes', 'lesson_conditions',
                    'scorm_reference', 'studentquiz_allowedqtypes', 'studentquiz_excluderoles', 'studentquiz_reportingemail',
                    'survey_questions', 'data_csstemplate', 'data_config', 'wiki_firstpagetitle',
                    'bigbluebuttonbn_moderatorpass', 'bigbluebuttonbn_participants', 'bigbluebuttonbn_guestpassword',
                    'rattingallocate_setting', 'rattingallocate_strategy', 'hvp_json_content', 'hvp_filtered', 'hvp_slug',
                    'wooclap_linkedwooclapeventslug', 'wooclap_wooclapeventid', 'kalvidres_metadata',
            ];
    static private array $usercolstoskip = [];

    static private array $filteredtablefields = [];
    public string $text;
    public string $table;
    public string $field;
    public int $id;
    public int $cmid;
    public bool $tneeded; // @todo MDL-000 should be called via status.
    public int $format;
    public int $tid;
    public string $displaytext;
    public string $pluginname;
    public string $translatedfieldname;
    public string $iconurl;
    public moodle_url $link;
    public mixed $purpose;
    public status $status;

    /**
     * @param int $id
     * @param string $text
     * @param int $format
     * @param string $field
     * @param string $table
     * @param int $cmid
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function __construct(
            int $id,
            string $text,
            int $format,
            string $field,
            string $table,
            int $cmid = -1
    ) {
        $this->id = $id;
        $this->field = $field;
        $this->table = $table;
        $this->format = $format;
        $this->cmid = $cmid;
        $this->purpose = null;
        $this->iconurl = '';
        $this->translatedfieldname = '';
        $this->tneeded = true;
        //$this->status = null;
        $this->preparetexts($text);
        $this->init_db();
        // Prepare link.
        //$this->link_builder($parentid);
        //$this->build_ui($activity);
        $this->search_field_strings();
    }

    /**
     * Building the text attributes.
     *
     * @param string $text
     * @param string $activitycontent
     * @return void
     */
    private function preparetexts(string $text) {
        $this->displaytext = $this->text = $text;
        if (str_contains($text, '@@PLUGINFILE@@')) {
            $this->displaytext = $this->resolve_pluginfiles($text, $this->table, $this->id, $this->cmid);
        }
    }

    /**
     * Retrieve the urls of files.
     *
     * @param string $text
     * @param int $itemid
     * @param string $table
     * @param string $field
     * @param int $cmid
     * @return array|string|string[]
     * @throws \dml_exception
     *
     * private function get_file_url(string $text, int $itemid, string $table, string $field, int $cmid) {
     * global $DB;
     * $tmp = $this->get_item_contextid($itemid, $table, $cmid);
     * switch ($table) {
     * case 'course_sections' :
     * $select =
     * 'component = "course" AND itemid =' . $itemid . ' AND filename != "." AND filearea = "section"';
     * $params = [];
     * break;
     * default :
     * $select =
     * 'contextid = :contextid AND component = :component AND filename != "." AND ' .
     * $DB->sql_like('filearea', ':field');
     * $params = ['contextid' => $tmp['contextid'], 'component' => $tmp['component'],
     * 'field' => '%' . $DB->sql_like_escape($field) . '%'];
     * break;
     * }
     *
     * $result = $DB->get_recordset_select('files', $select, $params);
     * if ($result->valid()) {
     * $itemid = ($field == 'intro' || $field == 'summary') && $table != 'course_sections' ? '' : $result->current()->itemid;
     * return file_rewrite_pluginfile_urls($text, 'pluginfile.php', $result->current()->contextid,
     * $result->current()->component, $result->current()->filearea, $itemid);
     * } else {
     * return file_rewrite_pluginfile_urls($text, 'pluginfile.php', $tmp['contextid'], $tmp['component'], $field,
     * $tmp['itemid']);
     * }
     * }*/

    /**
     * Get the correct context.
     *
     * @param int $id
     * @param string $table
     * @param int $cmid
     * @return array
     *
     * private function get_item_contextid(int $id, string $table, int $cmid = 0) {
     * $i = 0;
     * $iscomp = false;
     *
     * switch ($table) {
     * case 'course':
     * $i = context_course::instance($id)->id;
     * break;
     * case 'course_sections':
     * $i = context_module::instance($id)->id;
     * break;
     * default :
     * $i = context_module::instance($cmid)->id;
     * $iscomp = true;
     * break;
     * }
     * return ['contextid' => $i, 'component' => $iscomp ? 'mod_' . $table : $table, 'itemid' => $iscomp ? $cmid : ''];
     * }*/

    /**
     * Stores the translation's statuses.
     *
     * @return false|mixed|\stdClass
     * @throws \dml_exception
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
     * Link Builder to edit in place.
     *
     * @param int $parentid
     * @return void
     * @throws \moodle_exception
     */
    private function link_builder(int $parentid = 0): void {
        global $CFG;
        $link = null;
        switch ($this->table) {
            case 'course':
                $link = new moodle_url($CFG->wwwroot . "/course/edit.php", ['id' => $this->id]);
                break;
            case 'course_sections':
                $link = new moodle_url($CFG->wwwroot . "/course/editsection.php", ['id' => $this->id]);
                break;
            case 'quiz' :
                $link = new moodle_url($CFG->wwwroot . "/course/modedit.php", ['update' => $this->cmid]);
                break;
            case 'question' :
                $link = new moodle_url($CFG->wwwroot . "/question/bank/editquestion/question.php",
                        ['id' => $this->id, 'cmid' => $this->cmid]);
                break;
            case 'question_answers' :
                $link = new moodle_url($CFG->wwwroot . "/question/bank/editquestion/question.php",
                        ['id' => $parentid, 'cmid' => $this->cmid]);
                break;
            default:
                if (str_contains($this->table, "_")) {
                    $split = explode("_", $this->table);
                    $params = ['cmid' => $this->cmid, 'id' => $this->id];
                    $path = "/mod/  {$split[0]}/edit.php";
                    switch ($split[0]) {
                        case 'qtype':
                            $params = ['cmid' => $this->cmid, 'id' => $parentid];
                            $path = '/question/bank/editquestion/question.php';
                            break;
                        case 'wiki':
                            $params = ['pageid' => $this->id];
                            break;
                    }
                    $link = new moodle_url($CFG->wwwroot . $path, $params);
                } else if ($this->cmid !== 0) {
                    $link = new moodle_url($CFG->wwwroot . "/course/modedit.php", ['update' => $this->cmid]);
                }
                break;
        }
        $this->link = $link;
    }

    private function build_ui(activity $activity) {
        global $OUTPUT;
        try {
            if ($activity->qtype !== '') {
                // Question icons.
                $this->iconurl = $OUTPUT->image_url('icon', $activity->qtype);
                $this->pluginname = get_string('pluginname', $activity->qtype);
            } else if ($activity->cmid !== 0) {
                // Course.
                if ($activity->parent !== null) {
                    $this->iconurl = $OUTPUT->image_url('icon', $activity->parent->qtype);
                } else {
                    $this->iconurl = self::$modinfo->get_cm($this->cmid)->get_icon_url()->out(false);
                }
                $this->purpose = call_user_func($this->table . '_supports', FEATURE_MOD_PURPOSE);
                $this->pluginname = get_string('pluginname', $this->table);
            } else {
                $this->iconurl = new moodle_url('http://moodle.test/local/deepler/pix/icon.svg');
                $this->purpose = null;
            }

        } catch (TypeError $e) {

            // @todo MD-0000 Do something with error message ?.
            $this->purpose = null;
            $this->iconurl === '' ? new moodle_url('http://moodle.test/local/deepler/pix/icon.svg') : $this->iconurl;
        }
    }

    /**
     * Try to find the string of each fields of mod/plugin.
     *
     * @return void
     * @throws \coding_exception
     * @todo MDL-0000 this is sick there should be a simple way...
     */
    private function search_field_strings(): void {
        if ($this->table !== null) {
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
                    $this->translatedfieldname = $this->findoutstandingold();
                    $this->translatedfieldname = $this->findoutstanding();
                }
            }
        }
    }

    /**
     * @return string
     * @throws \coding_exception
     *
     * public function findoutstandingold(): string {
     * $foundstring = $this->field;
     * $plugroot = explode("_", $this->table);
     * $fieldwithoutunderscore = str_replace("_", "", $this->field);
     *
     * // Try several combining possible to try to fetch weird unknown string names.
     * $allcombinations = [
     * ['identifier' => $this->table . $this->field, 'component' => 'mod_' . $this->table],
     * ['identifier' => $this->field, 'component' => 'mod_' . $this->table],
     * ['identifier' => $this->field, 'component' => 'moodle'],
     * ['identifier' => $this->field, 'component' => 'core'],
     * ['identifier' => $this->field, 'component' => 'question'],
     * ['identifier' => $this->field, 'component' => 'pagetype'],
     * ['identifier' => $this->field, 'component' => 'core_plugin'],
     * ['identifier' => $this->table . $this->field, 'component' => 'moodle'],
     * ['identifier' => $this->table . $this->field, 'component' => 'core'],
     * ['identifier' => $this->table . $this->field, 'component' => 'pagetype'],
     * ['identifier' => $this->table . $this->field, 'component' => 'core_plugin'],
     * ['identifier' => $this->field . $this->table, 'component' => 'moodle'],
     * ['identifier' => $this->field . $this->table, 'component' => 'core'],
     * ['identifier' => $this->field . $this->table, 'component' => 'pagetype'],
     * ['identifier' => $this->field . $this->table, 'component' => 'core_plugin'],
     * ['identifier' => $this->field . ' ' . $this->table, 'component' => 'moodle'],
     * ['identifier' => $this->field . ' ' . $this->table, 'component' => 'core'],
     * ['identifier' => $this->field . ' ' . $this->table, 'component' => 'pagetype'],
     * ['identifier' => $this->field . ' ' . $this->table, 'component' => 'core_plugin'],
     * ['identifier' => $foundstring, 'component' => 'moodle'],
     * ['identifier' => $foundstring, 'component' => 'core'],
     * ['identifier' => $foundstring, 'component' => 'pagetype'],
     * ['identifier' => $foundstring, 'component' => 'core_plugin'],
     * ['identifier' => $this->field, 'component' => 'mod_' . $plugroot[0]],
     * ['identifier' => ($plugroot[1] ?? '') . $this->field, 'component' => 'mod_' . $plugroot[0]],
     * ['identifier' => $fieldwithoutunderscore, 'component' => 'mod_' . $plugroot[0]],
     * ['identifier' => ($plugroot[1] ?? '') . $fieldwithoutunderscore, 'component' => 'mod_' . $plugroot[0]],
     * ['identifier' => $this->field, 'component' => $this->table],
     * ['identifier' => 'pluginname', 'component' => $this->table],
     * ];
     *
     * foreach ($allcombinations as $string) {
     * $stringid = $string['identifier'];
     * $componentid = $string['component'];
     * if (get_string_manager()->string_exists($stringid, $componentid)) {
     * $foundstring = get_string($stringid, $componentid, 'x'); //Adding x in case the found has a var.
     * break;
     * }
     * }
     * return $foundstring;
     * }*/

    public function findoutstanding(): string {
        $foundstring = $this->field;

        // Extract plugin component from table name
        $tableparts = explode('_', $this->table, 2);
        $plugincomponent = isset($tableparts[1]) ? 'mod_' . $tableparts[0] : '';

        $candidates = [
            // Highest priority: Direct field name in plugin component.
                ['identifier' => $this->field, 'component' => $plugincomponent],

            // Standard Moodle core strings.
                ['identifier' => $this->field, 'component' => 'core'],
                ['identifier' => $this->field, 'component' => 'moodle'],

            // Common field patterns.
                ['identifier' => $this->table . '_' . $this->field, 'component' => $plugincomponent],
                ['identifier' => $this->table . '_' . $this->field, 'component' => 'core'],

            // Field type specific (for data activity modules).
                ['identifier' => $this->field, 'component' => 'datafield_' . $this->field],

            // Legacy patterns.
                ['identifier' => $this->table . $this->field, 'component' => $plugincomponent]
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
    public function resolve_pluginfiles(string $text, string $table, int $itemid,
            string $filearea, int $cmid = 0) {
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
                        'itemid' => $itemid
                ];

            case 'course_sections':
                $context = context_course::instance(
                        get_field_sql("SELECT course FROM {course_sections} WHERE id = ?", [$itemid])
                );
                return [
                        'contextid' => $context->id,
                        'component' => 'course',
                        'itemid' => $itemid
                ];

            default: // Activity modules
                $context = context_module::instance($cmid);
                return [
                        'contextid' => $context->id,
                        'component' => 'mod_' . $table,
                        'itemid' => $cmid
                ];
        }
    }

    static public function filterdbtextfields($tablename) {
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

    static public function getfields(mixed $info, string $table, array $collumns, $cmid): array {
        $infos = [];

        foreach ($collumns as $collumn) {
            $fieldtextformat = "{$collumn}format";
            if ($info->{$collumn} !== null && $info->{$collumn} !== '') {
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

    static public function getfieldsfrominfo(cm_info $cm_info) {
        $mod = $cm_info->modname;
        return self::getfields($cm_info, $mod, self::filterdbtextfields($cm_info->modname), $cm_info->id);
    }

}
