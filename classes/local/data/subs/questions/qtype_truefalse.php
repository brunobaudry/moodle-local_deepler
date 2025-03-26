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

namespace local_deepler\local\data\subs\questions;

use local_deepler\local\data\field;

/**
 * True/False question type wrapper.
 *
 * @package    local_deepler
 * @copyright  2025  <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_truefalse extends qbase {
    /**
     * Get the fields to be translated.
     *
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    protected function getsubs(): array {
        $fields = [];
        if (!empty($this->question->truefeedback)) {
            $fields[] = new field(
                    $this->question->trueanswerid,
                    $this->question->truefeedback,
                    $this->question->truefeedbackformat,
                    'feedback',
                    'question_answers'
            );
        }
        if (!empty($this->question->falsefeedback)) {
            $fields[] = new field(
                    $this->question->falseanswerid,
                    $this->question->falsefeedback,
                    $this->question->falsefeedbackformat,
                    'feedback',
                    'question_answers'
            );
        }
        return $fields;
    }
}
