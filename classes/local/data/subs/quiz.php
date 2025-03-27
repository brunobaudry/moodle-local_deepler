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
use question_bank;

/**
 * Subclass for quiz with sub questions.
 *
 * @package    local_deepler
 * @copyright  2025  <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz {
    /** @var array */
    private array $questions;
    /**
     * @var \cm_info
     */
    private cm_info $quiz;

    /**
     * Constructor.
     *
     * @param \cm_info $quiz
     * @throws \dml_exception
     */
    public function __construct(cm_info $quiz) {
        $this->quiz = $quiz;
        $slots = $this->getslots($quiz);
        $this->questions = [];
        foreach ($slots as $slot) {
            if ($slot->qtype === 'random') {
                $this->fetchrandomquestions($slot->id);
            } else {
                $this->questions[] = question_bank::load_question($slot->questionid);
            }
        }
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
            $name = $question->qtype->plugin_name();
            $class = $this->findclass($name);

            $item = field::createclassfromstring($class, $params);
            if ($item === null) {
                // Case 'qtype_description'.
                // Case 'qtype_randomsamatch'.
                // Case 'qtype_essay'.
                // Case 'qtype_multianswer'.
                // Other cases.
                $item = field::createclassfromstring('questions\qtype_basic', $params);
            }
            $childs[] = $item;
        }
        return $childs;
    }

    /**
     * Special method to fetch random questions.
     *
     * @param int $slotid
     * @return void
     * @throws \dml_exception
     */
    public function fetchrandomquestions(int $slotid): void {
        global $DB;
        // Retrieve category and filter parameters.
        $reference = $DB->get_record('question_set_references', [
                'component' => 'mod_quiz',
                'questionarea' => 'slot',
                'itemid' => $slotid,
        ], '*', MUST_EXIST);
        // Decode filter condition.
        $filter = json_decode($reference->filtercondition);
        // Initialize category information.
        $categoryconfig = [
                'primary_category' => null,
                'includesubcategories' => false,
        ];

        // Modern format detection.
        if (isset($filter->filter->category)) {
            $categoryconfig['primary_category'] = (int) $filter->filter->category->values[0];
            $categoryconfig['includesubcategories'] =
                    (bool) ($filter->filter->category->filteroptions->includesubcategories ?? false);
        } else if (isset($filter->cat)) {
            // Legacy format fallback.
            $categories = array_map('intval', explode(',', $filter->cat));
            $categoryconfig['primary_category'] = $categories[0];
            $categoryconfig['includesubcategories'] = (bool) ($filter->includesubcategories ?? false);
        }
        // Output structure matches modern API.
        $result = [
                'questioncategoryid' => $categoryconfig['primary_category'],
                'includesubcategories' => $categoryconfig['includesubcategories'],
                'tags' => $filter->tags ?? [],
        ];

        // Get all short-answer questions in target category.
        $finder = question_bank::get_finder();
        $questions = $finder->get_questions_from_categories(
                $result['questioncategoryid'],
                $result['includesubcategories'],
                $result['tags']);

        // Load full question objects.
        foreach ($questions as $question) {
            $this->questions[] = question_bank::load_question($question);
        }
    }

    /**
     * Guess the class name
     *
     * @param string $name
     * @return string
     */
    private function findclass(string $name): string {
        $class = "questions\\{$name}";

        switch ($name) {
            case 'qtype_shortanswer':
            case 'qtype_calculatedmulti':
            case 'qtype_multichoice':
                $class = 'questions\qtype_multi';
                break;
            case 'qtype_numerical':
            case 'qtype_calculated':
            case 'qtype_calculatedsimple':
                $class = 'questions\qtype_calculated';
                break;
            case 'qtype_ddwtos':
                $class = 'questions\qtype_gapselect'; // Same as qtype_gapselect obviously.
                break;
            case 'qtype_ddmarker':
                $class = 'questions\qtype_ddimageortext'; // Same as qtype_ddimageortext obviously.
                break;
        }
        return $class;
    }
}
