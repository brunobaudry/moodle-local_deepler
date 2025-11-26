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
 * Calculated question type wrapper.
 *
 * @package    local_deepler
 * @copyright  2025  <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_calculated extends qbase {
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
        foreach ($this->question->answers as $answer) {
            if (!empty($answer->feedback)) {
                $fields[] = new field(
                    $answer->id,
                    $answer->feedback,
                    $answer->feedbackformat,
                    'feedback',
                    'question_answers',
                    $this->cmid
                );
            }
        }
        return $fields;
    }
}
