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

namespace local_deepler\local\data\subs;

use cm_info;
use local_deepler\local\data\field;
use mod_quiz\quiz_settings;
use mod_quiz\structure;
use qtype_random;
use question_bank;
use dml_missing_record_exception;

/**
 * Subclass for quiz with sub questions.
 *
 * @package    local_deepler
 * @copyright  2025  <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz {
    /**
     * Constructor.
     *
     * @param \cm_info $quiz
     * @throws \dml_exception
     */
    public function __construct(cm_info $quiz) {
        global $DB;
        $this->quiz = $quiz;
        $slots = $this->getslots($quiz);
        $this->questions = [];
        $hasrandom = false;
        foreach ($slots as $slot) {
            $israndom = $DB->record_exists(
                'question_set_references',
                [
                    'component' => 'mod_quiz',
                    'questionarea' => 'slot',
                    'itemid' => $slot->id,
                ]
            );
            // Seems like there a new slot attribute to describe random questions.
            if ($israndom) {
                $hasrandom = true;
                $this->fetchrandomquestions($slot->id);
            } else if (is_numeric($slot->questionid)) {
                // Non numeric ids (e.g. 's12') are placeholders for questions that have gone missing.
                $this->addquestion((int) $slot->questionid);
            }
        }
        // Remove duplicates (often in a quiz whith random questions).
        if ($hasrandom) {
            $this->questions = array_map('unserialize', array_unique(array_map('serialize', $this->questions)));
        }
    }

    /**
     * Load a question definition and add it to the list.
     * Questions whose type is not installed on this site are skipped, as core cannot load their options
     * (question_bank::load_question() would fail with "Attempt to assign property shuffleanswers on null").
     *
     * @param int $questionid
     * @return void
     */
    private function addquestion(int $questionid): void {
        $questiondata = question_bank::load_question_data($questionid);
        if (!question_bank::is_qtype_installed($questiondata->qtype)) {
            $message = "local_deepler: skipping question $questionid, question type '{$questiondata->qtype}' is not installed.";
            debugging($message, DEBUG_DEVELOPER);
            return;
        }
        $this->questions[] = question_bank::load_question($questionid, false);
    }

    /**
     * Try to fetch the slots.
     * With special cases for LTS 401.
     *
     * @param \cm_info $quiz
     * @return array|\stdClass[]
     * @throws \dml_exception
     */
    private function getslots(cm_info $quiz) {
        global $CFG;
        if (version_compare($CFG->version, '2023042411', '<')) {
            global $DB;

            // Load slots for the quiz.
            return $DB->get_records_sql("SELECT qs.*,
       qv.version,
       qv.id AS versionid,
       q.id AS questionid,
       q.qtype,
       q.name,
       q.questiontext,
       q.generalfeedback
FROM {quiz_slots} qs
JOIN {question_references} qr ON qr.component = 'mod_quiz' AND qr.questionarea = 'slot' AND qr.itemid = qs.id
JOIN {question_versions} qv ON qv.questionbankentryid = qr.questionbankentryid
JOIN {question} q ON q.id = qv.questionid
WHERE qs.quizid = ?", ['quizid' => $this->quiz->instance]);
        } else {
            $quizsettings = quiz_settings::create($quiz->instance);
            $structure = structure::create_for_quiz($quizsettings);
            return $structure->get_slots();
        }
    }

    /**
     * Returns all qvilable questions from categories.
     *
     * @param int $categoryid
     * @param bool $includesubs
     * @return array
     */
    private function get_available_questions_from_category(
        int $categoryid,
        bool $includesubs
    ): array {
        global $DB;

        $categoryids = [$categoryid];

        if ($includesubs) {
            $category = $DB->get_record(
                'question_categories',
                ['id' => $categoryid],
                'id,path',
                MUST_EXIST
            );

            $subcategories = $DB->get_fieldset_select(
                'question_categories',
                'id',
                $DB->sql_like('path', ':path'),
                ['path' => $category->path . '/%']
            );

            $categoryids = array_merge($categoryids, $subcategories);
        }

        [$catsql, $params] = $DB->get_in_or_equal(
            $categoryids,
            SQL_PARAMS_NAMED,
            'cat'
        );

        $params['status1'] = 'ready';
        $params['status2'] = 'ready';

        $sql = "
    SELECT q.id
      FROM {question} q
      JOIN {question_versions} qv
        ON qv.questionid = q.id
      JOIN {question_bank_entries} qbe
        ON qbe.id = qv.questionbankentryid
     WHERE qbe.questioncategoryid {$catsql}
       AND qv.status = :status1
       AND qv.version = (
            SELECT MAX(qv2.version)
              FROM {question_versions} qv2
             WHERE qv2.questionbankentryid = qv.questionbankentryid
               AND qv2.status = :status2
       )
       AND q.qtype <> 'missingtype'
";
        return $DB->get_fieldset_sql($sql, $params);
    }
    /**
     * Special method to fetch random questions.
     *
     * @param int $slotid
     * @return void
     * @throws \dml_exception
     */
    public function fetchrandomquestions(int $slotid): void {
        global $DB, $CFG;

        // Read the reference for this slot and decode its filter.
        $reference = $DB->get_record('question_set_references', [
            'component' => 'mod_quiz',
            'questionarea' => 'slot',
            'itemid' => $slotid,
        ], '*', MUST_EXIST);

        $filter = json_decode($reference->filtercondition);

        // Extract category and include-subcategories flags (support modern and legacy formats).
        $categoryid = null;
        $includesubs = false;

        if (!empty($filter->filter->category)) {
            // New question bank filter format.
            $categoryid = (int) ($filter->filter->category->values[0] ?? 0);
            $includesubs = (bool) ($filter->filter->category->filteroptions->includesubcategories ?? false);
        } else if (isset($filter->cat)) {
            // Legacy format used by older Moodle versions.
            $categories = array_map('intval', explode(',', (string) $filter->cat));
            $categoryid = $categories[0] ?? 0;
            $includesubs = (bool) ($filter->includesubcategories ?? false);
        }

        if (empty($categoryid)) {
            return; // Nothing to do without a category.
        }

        // Resolve tag ids from the filter, supporting several shapes.
        $tagids = [];
        // New filter format could be under $filter->filter->tags or $filter->tags.
        $rawtags = $filter->filter->qtagids->values ?? ($filter->tags ?? []);
        if (!empty($rawtags) && is_array($rawtags)) {
            foreach ($rawtags as $t) {
                if (is_int($t) || ctype_digit((string) $t)) {
                    $tagids[] = (int) $t;
                } else if (is_object($t) && isset($t->id)) {
                    $tagids[] = (int) $t->id;
                } else if (is_object($t) && isset($t->name)) {
                    // Best-effort resolve by name.
                    if ($tagid = $DB->get_field('tag', 'id', ['name' => $t->name])) {
                        $tagids[] = (int) $tagid;
                    }
                } else if (is_string($t) && $t !== '') {
                    if ($tagid = $DB->get_field('tag', 'id', ['name' => $t])) {
                        $tagids[] = (int) $tagid;
                    }
                }
            }
            // Ensure uniqueness.
            $tagids = array_values(array_unique(array_filter($tagids)));
        }

        // Get available question ids using Moodle random qtype helper (handles excluded qtypes etc.).
        $questionids = $this->get_available_questions_from_category(
            $categoryid,
            $includesubs
        );

        if (empty($questionids)) {
            return; // No candidates.
        }

        // If tags are specified, filter the ids so that each question has ANY of the requested tags (OR semantics).
        if (!empty($tagids)) {
            [$idsql, $idparams] = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED, 'qid');
            [$tagsql, $tagparams] = $DB->get_in_or_equal($tagids, SQL_PARAMS_NAMED, 'ti');

            $params = $idparams + $tagparams + [
                    'questionitemtype' => 'question',
                    'questioncomponent' => 'core_question',
                ];

            $sql = "SELECT DISTINCT ti.itemid
                      FROM {tag_instance} ti
                     WHERE ti.itemtype = :questionitemtype
                       AND ti.component = :questioncomponent
                       AND ti.tagid {$tagsql}
                       AND ti.itemid {$idsql}";

            $filtered = $DB->get_fieldset_sql($sql, $params);

            if (empty($filtered)) {
                return; // No question matches any of the tags.
            }
            $questionids = $filtered;
        }

        // Load and append the question objects.
        foreach ($questionids as $qid) {
            $this->addquestion((int) $qid);
        }
    }

    /**
     * Get the child fields.
     *
     * @return array
     */
    public function getchilds(): array {
        $childs = [];
        /** @var \question_definition $question */
        foreach ($this->questions as $question) {
            $params = [
                'question' => $question,
                'cmid' => $this->quiz->id,
            ];
            $childs[] = field::createclassfromstring('questions\qtype_basic', $params);
        }
        return $childs;
    }
    /** @var array */
    private array $questions;
    /**
     * @var \cm_info
     */
    private cm_info $quiz;
}
