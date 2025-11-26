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

use context_module;
use local_deepler\local\data\field;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . "/mod/forum/lib.php");

/**
 * Sub for Forum activities.
 *
 * @package local_deepler
 * @copyright 2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forum extends subbase {
    /**
     * Get the fields to be translated.
     *
     * @return array
     */
    public function getfields(): array {
        global $DB;
        $fields = [];
        $table = 'forum_posts';
        $modcontext = context_module::instance($this->cm->id); // Var $cmid is the course module ID.
        require_capability('mod/forum:viewdiscussion', $modcontext);

        $discussions = $DB->get_records('forum_discussions', ['forum' => $this->cm->instance]);
        foreach ($discussions as $discussion) {
            $posts = forum_get_all_discussion_posts($discussion->id, 'discussion ASC');
            foreach ($posts as $post) {
                // Do something with $post.
                if ($post->subject) {
                    $fields[] = new field(
                        $post->id,
                        $post->subject,
                        0,
                        'subject',
                        $table,
                        $this->cm->id
                    );
                }
                if ($post->message) {
                    $fields[] = new field(
                        $post->id,
                        $post->message,
                        1,
                        'message',
                        $table,
                        $this->cm->id
                    );
                }
            }
        }
        return $fields;
    }
}
