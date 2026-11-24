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

use context_course;
use context_module;
use core\context;
use core\output\action_icon;
use core_plugin_manager;
use Exception;
use filter_multilang2;
use filter_multilang2\text_filter;
use html_writer;
use local_deepler\local\data\field;
use moodle_url;

/**
 * Utilitarian statics.
 *
 * @package local_deepler
 * @copyright 2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {
    /**
     * API Key validator.
     */
    const DEEPL_API_REGEX = '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}(:fx|:pro)?$/i';
    /**
     * List of bg colors for highlighting.
     */
    const COLORS = [
        'FloralWhite',
        'Lavender',
        'LightYellow',
        'MintCream',
        'Honeydew',
        'AliceBlue', 'GhostWhite',
        'Ivory',
    ];

    /**
     * Generate a color index for a given array.
     *
     * @param array $tab
     * @return array
     */
    public static function makecolorindex(array $tab): array {
        $t = [];
        foreach ($tab as $i => $v) {
            $t[] = ['key' => $v, 'value' => self::COLORS[$i % count(self::COLORS)]];
        }
        return $t;
    }

    /**
     * Unified file URL resolver with context-aware processing.
     *
     * Rewrites @@PLUGINFILE@@ placeholders in the field text to real pluginfile URLs.
     *
     * @param \local_deepler\local\data\field $field
     * @return string Processed text with valid URLs
     */
    public static function resolve_pluginfiles(field $field): string {
        global $CFG;
        $text = $field->get_displaytext();

        try {
            $info = self::get_context_info($field->get_table(), $field->get_id(), $field->get_cmid(), $field->get_tablefield());
            $itemid = self::pluginfile_url_itemid($info['component'], $info['filearea'], $info['itemid']);
            if (isset($info['questionid'])) {
                // Question files are only served outside an attempt through a preview URL,
                // see local_deepler_question_preview_pluginfile().
                require_once($CFG->libdir . '/questionlib.php');
                return question_rewrite_question_preview_urls(
                    $text,
                    $info['questionid'],
                    $info['contextid'],
                    $info['component'],
                    $info['filearea'],
                    $itemid,
                    $info['previewcontextid'],
                    'local_deepler'
                );
            }
            return file_rewrite_pluginfile_urls(
                $text,
                'pluginfile.php',
                $info['contextid'],
                $info['component'],
                $info['filearea'],
                $itemid
            );
        } catch (Exception $e) {
            return $text; // Fail gracefully.
        }
    }

    /**
     * Get preview/download URL for a stored file.
     *
     * @param \stored_file $file
     * @param int $courseid
     * @return string
     */
    public static function get_file_url(\stored_file $file, int $courseid): string {
        $component = $file->get_component();
        $filearea = $file->get_filearea();

        if ($component === 'course' && $filearea === 'legacy') {
            return moodle_url::make_legacyfile_url($courseid, $file->get_filepath() . $file->get_filename())->out(false);
        }

        return moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $component,
            $filearea,
            self::pluginfile_url_itemid($component, $filearea, (int) $file->get_itemid()),
            $file->get_filepath(),
            $file->get_filename()
        )->out(false);
    }

    /**
     * File areas of module sub-tables, keyed by table then column.
     * Their itemid is the row id, unless MODULE_SUBTABLE_ITEMID_COLUMNS names another column of the row.
     */
    const MODULE_SUBTABLE_FILEAREAS = [
        'book_chapters' => ['content' => 'chapter'],
        'forum_posts' => ['message' => 'post'],
        'glossary_entries' => ['definition' => 'entry'],
        'lesson_pages' => ['contents' => 'page_contents'],
        'lesson_answers' => ['answer' => 'page_answers', 'response' => 'page_responses'],
        'quiz_feedback' => ['feedbacktext' => 'feedback'],
        'wiki_pages' => ['cachedcontent' => 'attachments'],
    ];

    /**
     * Module sub-tables whose files are stored per parent record: table => column holding the itemid.
     */
    const MODULE_SUBTABLE_ITEMID_COLUMNS = [
        'wiki_pages' => 'subwikiid',
    ];

    /**
     * File areas of main activity record columns whose name differs from the column, keyed by module then column.
     * Their itemid is 0.
     */
    const MODULE_MAINTABLE_FILEAREAS = [
        'assign' => ['activity' => 'activityattachment'],
    ];

    /**
     * Question related tables whose files are stored per row, keyed by table then column.
     * Values are [component, filearea]; the itemid is the row id.
     */
    const QUESTION_ROW_FILEAREAS = [
        'question' => [
            'questiontext' => ['question', 'questiontext'],
            'generalfeedback' => ['question', 'generalfeedback'],
        ],
        'question_answers' => [
            'answer' => ['question', 'answer'],
            'feedback' => ['question', 'answerfeedback'],
        ],
        'question_hints' => ['hint' => ['question', 'hint']],
        'qtype_match_subquestions' => ['questiontext' => ['qtype_match', 'subquestion']],
        'qtype_kprime_rows' => [
            'optiontext' => ['qtype_kprime', 'optiontext'],
            'optionfeedback' => ['qtype_kprime', 'feedbacktext'],
        ],
        'qtype_mtf_rows' => [
            'optiontext' => ['qtype_mtf', 'optiontext'],
            'optionfeedback' => ['qtype_mtf', 'feedbacktext'],
        ],
        'qtype_formulas_answers' => [
            'subqtext' => ['qtype_formulas', 'answersubqtext'],
            'feedback' => ['qtype_formulas', 'answerfeedback'],
            'partcorrectfb' => ['qtype_formulas', 'partcorrectfb'],
            'partpartiallycorrectfb' => ['qtype_formulas', 'partpartiallycorrectfb'],
            'partincorrectfb' => ['qtype_formulas', 'partincorrectfb'],
        ],
    ];

    /**
     * Combined feedback columns, saved by core_question_type::save_combined_feedback_helper()
     * in component 'question', filearea = column, itemid = question id, whatever the qtype table.
     */
    const QUESTION_COMBINED_FEEDBACKS = ['correctfeedback', 'partiallycorrectfeedback', 'incorrectfeedback'];

    /**
     * Resolve where the files embedded in a translatable field are stored.
     *
     * @param string $table Table of the record.
     * @param int $itemid Record id.
     * @param int $cmid Course module id (0 for course and sections).
     * @param string $column Column holding the text, used to derive the file area.
     * Question data also gets 'questionid' and 'previewcontextid' (the course context) for preview URLs.
     *
     * @return array{context: \core\context, contextid: int, component: string, filearea: string, itemid: int}
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get_context_info(string $table, int $itemid, int $cmid = 0, string $column = ''): array {
        global $DB;

        if ($table === 'course') {
            return self::make_context_info(context_course::instance($itemid), 'course', $column, 0);
        }
        if ($table === 'course_sections') {
            $courseid = (int) $DB->get_field('course_sections', 'course', ['id' => $itemid], MUST_EXIST);
            return self::make_context_info(context_course::instance($courseid), 'course', 'section', $itemid);
        }
        if ($table === 'question' || str_starts_with($table, 'question_') || str_starts_with($table, 'qtype_')) {
            $info = self::get_question_context_info($table, $itemid, $column);
            $previewcontext = $cmid ? context_module::instance($cmid)->get_course_context() : $info['context'];
            $info['previewcontextid'] = (int) $previewcontext->id;
            return $info;
        }

        // Activity modules: the component comes from the course module, not from the table name.
        $cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
        $context = context_module::instance($cmid);
        $component = 'mod_' . $cm->modname;
        if (isset(self::MODULE_SUBTABLE_FILEAREAS[$table][$column])) {
            if (isset(self::MODULE_SUBTABLE_ITEMID_COLUMNS[$table])) {
                $itemcolumn = self::MODULE_SUBTABLE_ITEMID_COLUMNS[$table];
                $itemid = (int) $DB->get_field($table, $itemcolumn, ['id' => $itemid], MUST_EXIST);
            }
            return self::make_context_info($context, $component, self::MODULE_SUBTABLE_FILEAREAS[$table][$column], $itemid);
        }
        if ($table === $cm->modname) {
            // Main activity record (intro, page content, ...): files are stored with itemid 0.
            $filearea = self::MODULE_MAINTABLE_FILEAREAS[$table][$column] ?? $column;
            return self::make_context_info($context, $component, $filearea, 0);
        }
        // Other sub-tables: Moodle's usual convention is filearea = column and itemid = row id.
        return self::make_context_info($context, $component, $column, $itemid);
    }

    /**
     * Resolve the file storage of a question related record.
     *
     * Question files live in the context of the question bank category, not in the quiz context.
     * See QUESTION_ROW_FILEAREAS and QUESTION_COMBINED_FEEDBACKS; other qtype columns use the qtype component
     * with the question id as itemid.
     *
     * @param string $table
     * @param int $itemid
     * @param string $column
     * @return array
     * @throws \dml_exception
     */
    private static function get_question_context_info(string $table, int $itemid, string $column): array {
        global $DB;

        if ($table === 'question') {
            $questionid = $itemid;
        } else {
            $row = $DB->get_record($table, ['id' => $itemid], '*', MUST_EXIST);
            $questionid = (int) ($row->questionid ?? $row->question ?? 0);
        }
        $question = self::get_question_bank_record($questionid);
        $context = context::instance_by_id($question->contextid);

        if (isset(self::QUESTION_ROW_FILEAREAS[$table][$column])) {
            [$component, $filearea] = self::QUESTION_ROW_FILEAREAS[$table][$column];
            $info = self::make_context_info($context, $component, $filearea, $itemid);
        } else if (in_array($column, self::QUESTION_COMBINED_FEEDBACKS, true)) {
            $info = self::make_context_info($context, 'question', $column, $questionid);
        } else {
            // Other qtype specific columns (e.g. qtype_essay graderinfo): qtype component, itemid is the question id.
            $info = self::make_context_info($context, 'qtype_' . $question->qtype, $column, $questionid);
        }
        $info['questionid'] = $questionid;
        return $info;
    }

    /**
     * Fetch a question's type and the context of its question bank category.
     *
     * @param int $questionid
     * @return \stdClass with qtype and contextid
     * @throws \dml_exception
     */
    private static function get_question_bank_record(int $questionid): \stdClass {
        global $DB;
        $sql = 'SELECT q.qtype, qc.contextid
                  FROM {question} q
                  JOIN {question_versions} qv ON qv.questionid = q.id
                  JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                  JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
                 WHERE q.id = :questionid';
        return $DB->get_record_sql($sql, ['questionid' => $questionid], MUST_EXIST);
    }

    /**
     * Access checks and lookup for a question file requested through a local_deepler preview URL.
     *
     * The user must be able to translate in the preview (course) context and to view the question,
     * and the file must belong to that question's context and to the 'question' or the question's qtype component.
     *
     * @param \core\context $previewcontext Context given in the preview URL.
     * @param int $questionid Question given in the preview URL.
     * @param \core\context $filecontext
     * @param string $filecomponent
     * @param string $filearea
     * @param array $args itemid followed by the file path parts and the file name.
     * @return \stored_file|null The file, or null if it does not exist or does not belong to the question.
     * @throws \required_capability_exception|\moodle_exception When the user is not allowed.
     */
    public static function get_question_preview_file(
        context $previewcontext,
        int $questionid,
        context $filecontext,
        string $filecomponent,
        string $filearea,
        array $args
    ): ?\stored_file {
        global $CFG;
        require_once($CFG->libdir . '/questionlib.php');

        require_capability('local/deepler:edittranslations', $previewcontext);
        question_require_capability_on($questionid, 'view');

        $question = self::get_question_bank_record($questionid);
        if ((int) $question->contextid !== (int) $filecontext->id) {
            return null;
        }
        if ($filecomponent !== 'question' && $filecomponent !== 'qtype_' . $question->qtype) {
            return null;
        }
        $fullpath = '/' . $filecontext->id . '/' . $filecomponent . '/' . $filearea . '/' . implode('/', $args);
        $file = get_file_storage()->get_file_by_hash(sha1($fullpath));
        if (!$file || $file->is_directory()) {
            return null;
        }
        return $file;
    }

    /**
     * Build the array returned by {@see get_context_info()}.
     *
     * @param \core\context $context
     * @param string $component
     * @param string $filearea
     * @param int $itemid
     * @return array
     */
    private static function make_context_info(context $context, string $component, string $filearea, int $itemid): array {
        return [
            'context' => $context,
            'contextid' => (int) $context->id,
            'component' => $component,
            'filearea' => $filearea,
            'itemid' => $itemid,
        ];
    }

    /**
     * Returns the itemid to put in a pluginfile URL, or null if the pluginfile handler expects none.
     *
     * Module intro files, course summary/overviewfiles and workshop instructions/conclusion are served
     * without itemid in the URL. Other areas (e.g. course/section, mod_resource/content, mod_page/content) expect it.
     *
     * @param string $component
     * @param string $filearea
     * @param int|null $itemid
     * @return int|null
     */
    public static function pluginfile_url_itemid(string $component, string $filearea, ?int $itemid): ?int {
        if ($filearea === 'intro') {
            return null;
        }
        if ($component === 'course' && in_array($filearea, ['summary', 'overviewfiles'], true)) {
            return null;
        }
        if ($component === 'mod_workshop' && in_array($filearea, ['instructauthors', 'instructreviewers', 'conclusion'], true)) {
            return null;
        }
        return $itemid;
    }

    /**
     * Helper to make sure attributes generated from text are compatible.
     *
     * @param string $text
     * @return string
     */
    public static function makehtmlid(string $text): string {
        // Convert to lowercase.
        $text = strtolower($text);

        // Replace spaces and underscores with hyphens.
        $text = preg_replace('/[\s_]+/', '-', $text);

        // Remove all characters that are not alphanumeric or hyphens.
        $text = preg_replace('/[^a-z0-9\-]/', '', $text);

        // Ensure it doesn't start with a digit.
        if (preg_match('/^[0-9]/', $text)) {
            $text = 'id-' . $text;
        }

        // Trim hyphens from start and end.
        $text = trim($text, '-');

        return $text;
    }

    /**
     * Returns an array of all available user fields (standard + custom profile fields).
     *
     * @param \core\context $context
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function all_user_fields(context $context): array {
        global $DB;
        $fields = self::standard_user_fields();
        try {
            $mlangfilter = new text_filter($context, []);
        } catch (Exception $e) {
            $mlangfilter = new filter_multilang2($context, []);
        }
        // Add custom profile fields.
        foreach ($DB->get_records('user_info_field') as $field) {
            $fields['profile_field_' . $field->shortname] = $mlangfilter->filter($field->name);
        }
        return $fields;
    }

    /**
     * Returns an array of standard user fields for token mapping.
     *
     * @return array
     * @throws \coding_exception
     */
    public static function standard_user_fields(): array {
        return [
            'username' => get_string('username'),
            'email' => get_string('email'),
            'firstname' => get_string('firstname'),
            'lastname' => get_string('lastname'),
            'city' => get_string('city'),
            'country' => get_string('country'),
            'institution' => get_string('institution'),
            'department' => get_string('department'),
            'phone1' => get_string('phone1'),
            'phone2' => get_string('phone2'),
            'address' => get_string('address'),
            'idnumber' => get_string('idnumber'),
        ];
    }

    /**
     * Checks if a value matches a pattern with SQL-style wildcards (% and _).
     *
     * @param string $pattern
     * @param string $value
     * @return bool
     */
    public static function wildcard_match(string $pattern, string $value) {
        // Convert * to % for user convenience.
        $pattern = str_replace('*', '%', $pattern);
        // Escape regex special chars except % and _.
        $regex = preg_quote($pattern, '/');
        // Convert SQL wildcards to regex.
        $regex = str_replace(['%', '_'], ['.*', '.'], $regex);
        // Match full string (case-insensitive).
        return (bool) preg_match('/^' . $regex . '$/i', $value);
    }

    /**
     * Returns the absolute path to the plugin root directory.
     *
     * @return string
     */
    public static function get_plugin_root(): string {
        $pluginman = core_plugin_manager::instance();
        $plugininfo = $pluginman->get_plugin_info('local_deepler');
        return $plugininfo->rootdir;
    }

    /**
     * Wrapper Alternative for v > 405.
     *
     * @param mixed $url
     * @param string $iconname
     * @param string $tooltip
     * @param string $component
     * @param array $attributes
     * @return \action_icon|string
     * @throws \core\exception\moodle_exception
     */
    public static function local_deepler_get_action_icon(
        mixed $url,
        string $iconname,
        string $tooltip = '',
        string $component = 'core',
        array $attributes = []
    ) {
        global $OUTPUT;

        // Ensure $url is a moodle_url object.
        if (is_string($url)) {
            $url = new moodle_url($url);
        }

        // Check if the action_icon class exists (Moodle >= 4.0.5).
        if (class_exists('\core\output\action_icon')) {
            $icon = new \pix_icon($iconname, $tooltip, $component, $attributes);
            return new \action_icon($url, $icon);
        } else {
            // Fallback for older Moodle versions.
            $iconhtml = html_writer::empty_tag('img', array_merge([
                'src' => $OUTPUT->image_url($iconname, $component),
                'alt' => $tooltip,
                'class' => 'icon',
            ], $attributes));

            return html_writer::link($url->out(false), $iconhtml, ['title' => $tooltip]);
        }
    }
}
