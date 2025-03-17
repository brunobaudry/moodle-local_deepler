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

use Exception;
use mod_quiz\quiz_settings;
use mod_quiz\structure;
use question_bank;

class quiz {
    private array $questions;

    public function __construct($quiz) {
        $quizsettings = quiz_settings::create($quiz->instance);
        $structure = structure::create_for_quiz($quizsettings);
        $slots = $structure->get_slots();
        $this->questions = [];
        foreach ($slots as $slot) {
            $this->questions[] = question_bank::load_question($slot->questionid);
        }
    }

    public function getfields() {
        $fields = [];
        /** @var \question_definition $question */
        foreach ($this->questions as $question) {
            $class = "local_deepler\local\data\subs\questions\{$question->qtype}";
            try {
                $q = new $class($question);
                $fields[] = $q->getfields();
            } catch (Exception $e) {
                debugging($e->getMessage());
                //return [];
            }
        }
        return $fields;
    }
}
