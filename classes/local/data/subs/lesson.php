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

use local_deepler\local\data\field;

/**
 * Sub for Lesson activities.
 *
 * @package local_deepler
 * @copyright 2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lesson extends subbase {
    /**
     * Do it.
     *
     * @return array
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function getfields() {
        global $DB;
        $fields = [];
        $table = 'lesson_pages';
        $tablequestion = 'lesson_answers';
        $pages = $DB->get_records($table, ['lessonid' => $this->cm->instance], 'id ASC');

        foreach ($pages as $page) {
            if ($page->title) {
                $fields[] = new field(
                    $page->id,
                    $page->title,
                    0,
                    'title',
                    $table,
                    $this->cm->id
                );
            }
            if ($page->contents) {
                $fields[] = new field(
                    $page->id,
                    $page->contents,
                    0,
                    'contents',
                    $table,
                    $this->cm->id
                );
            }
            if ($page->qtype != 0) {
                $answers = $DB->get_records($tablequestion, ['pageid' => $page->id]);
                foreach ($answers as $answer) {
                    if ($answer->answer) {
                        $fields[] = new field(
                            $answer->id,
                            $answer->answer,
                            0,
                            'answer',
                            $tablequestion,
                            $this->cm->id
                        );
                    }
                    if ($answer->response) {
                        $fields[] = new field(
                            $answer->id,
                            $answer->response,
                            0,
                            'response',
                            $tablequestion,
                            $this->cm->id
                        );
                    }
                }
            }
        }
        return $fields;
    }
}
