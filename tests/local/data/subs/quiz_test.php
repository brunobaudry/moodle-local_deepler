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
defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use mod_quiz\quiz_settings;

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

/**
 * Unit tests for quiz sub class.
 *
 * @package    local_deepler
 * @covers     \local_deepler\local\data\subs\quiz
 * @copyright  2025 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class quiz_test extends advanced_testcase {
    /**
     * Test the constructor with a regular question and a random question.
     *
     * @return void
     * @covers \local_deepler\local\data\subs\quiz::__construct
     */
    public function test_constructor_with_random_question(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        /** @var \core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $regular = $questiongenerator->create_question('truefalse', null, ['category' => $cat->id]);
        $questiongenerator->create_question('shortanswer', null, ['category' => $cat->id]);
        $questiongenerator->create_question('essay', null, ['category' => $cat->id]);

        quiz_add_quiz_question($regular->id, $quiz, 0);

        $quizobj = quiz_settings::create($quiz->id);
        $structure = $quizobj->get_structure();
        $filtercondition = [
            'filter' => [
                'category' => [
                    'jointype' => \core_question\local\bank\condition::JOINTYPE_DEFAULT,
                    'values' => [$cat->id],
                    'filteroptions' => ['includesubcategories' => false],
                ],
            ],
        ];
        $structure->add_random_questions(0, 1, $filtercondition);

        $courseinfo = get_fast_modinfo($course);
        $quizinstance = new quiz($courseinfo->get_cm($quiz->cmid));
        $childs = $quizinstance->getchilds();
        $this->assertGreaterThanOrEqual(3, count($childs));
    }

    /**
     * Test the constructor when a slot holds a question whose qtype is not installed.
     *
     * @return void
     * @covers \local_deepler\local\data\subs\quiz::__construct
     */
    public function test_constructor_with_missing_qtype(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        /** @var \core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $regular = $questiongenerator->create_question('truefalse', null, ['category' => $cat->id]);
        $missing = $questiongenerator->create_question('shortanswer', null, ['category' => $cat->id]);
        quiz_add_quiz_question($regular->id, $quiz, 0);
        quiz_add_quiz_question($missing->id, $quiz, 0);
        // Simulate a question type that is not installed on this site (e.g. qtype_ordering before Moodle 4.5).
        $DB->set_field('question', 'qtype', 'notinstalledqtype', ['id' => $missing->id]);
        \question_bank::notify_question_edited($missing->id);

        $courseinfo = get_fast_modinfo($course);
        $quizinstance = new quiz($courseinfo->get_cm($quiz->cmid));
        $this->assertDebuggingCalled();
        $childs = $quizinstance->getchilds();
        $this->assertCount(1, $childs);
    }

    /**
     * Test the geticon method on question childs.
     *
     * @return void
     * @covers \local_deepler\local\data\subs\questions\qbase::geticon
     */
    public function test_question_child_geticon(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        /** @var \core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('truefalse', null, ['category' => $cat->id]);
        quiz_add_quiz_question($question->id, $quiz, 0);

        $courseinfo = get_fast_modinfo($course);
        $quizinstance = new quiz($courseinfo->get_cm($quiz->cmid));
        $childs = $quizinstance->getchilds();
        $this->assertNotEmpty($childs);
        $qchild = $childs[0];
        $iconhtml = $qchild->geticon();
        $this->assertNotEmpty($iconhtml);
        $this->assertStringContainsString('qtype_truefalse', $iconhtml);
    }
}
