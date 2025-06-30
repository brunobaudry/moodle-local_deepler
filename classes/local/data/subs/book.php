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

use local_deepler\local\data\field;

global $CFG;
require_once($CFG->dirroot . '/mod/book/locallib.php');

/**
 * Subclass of book as it has chapters (subs).
 *
 * @package    local_deepler
 * @copyright  2025  <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class book extends subbase {
    /**
     * Get the fields to be translated.
     *
     * @return array
     */
    public function getfields() {
        $fields = [];
        $table = 'book_chapters';
        $chapters = book_preload_chapters($this->record);
        foreach ($chapters as $c) {
            $fields[] = new field($c->id,
                    $c->title, 0, 'title', $table, $this->cm->id);
            $fields[] = new field($c->id,
                    $c->content, 1, 'content', $table, $this->cm->id);
        }
        return $fields;
    }
}
