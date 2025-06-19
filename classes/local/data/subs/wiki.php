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
global $CFG;
require_once($CFG->dirroot . '/mod/wiki/locallib.php');

use local_deepler\local\data\field;

/**
 * Subclass of wiki as it has sub wikis (subs).
 *
 * @package    local_deepler
 * @copyright  2025  <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wiki extends subbase {

    /**
     * Get fields.
     *
     * @return array
     * @throws \moodle_exception
     */
    public function getfields(): array {
        $fields = [];
        $wikis = wiki_get_subwikis($this->record->id);
        foreach (array_keys($wikis) as $wid) {
            $pages = wiki_get_page_list($wid);
            $pagesorphaned = array_map(function($op) {
                return $op->id;
            }, wiki_get_orphaned_pages($wid));
            foreach ($pages as $p) {
                if (!in_array($p->id, $pagesorphaned)) {
                    $fields[] = new field($p->id,
                            $p->title, 0, 'chapter', 'wiki_pages', $this->cm->id);
                    $fields[] = new field($p->id,
                            $p->cachedcontent, 1, 'cachedcontent', 'wiki_pages', $this->cm->id);
                }
            }
        }
        return $fields;
    }
}
