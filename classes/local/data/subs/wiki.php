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
global $CFG;
include_once($CFG->dirroot . '/mod/wiki/locallib.php');

use local_deepler\local\data\field;
use stdClass;

class wiki {
    /** @var \stdClass wiki db */
    private stdClass $wiki;
    /** @var array  wiki_subwikis */
    private array $wikis;

    public function __construct(stdClass $wiki) {
        $this->wiki = $wiki;
        $this->wikis = wiki_get_subwikis($this->wiki->id);
    }

    public function getfields() {
        $fields = [];
        foreach ($this->wikis as $wid => $wiki) {
            $pages = wiki_get_page_list($wid);
            $pagesorphaned = array_map(function($op) {
                return $op->id;
            }, wiki_get_orphaned_pages($wid));
            foreach ($pages as $p) {
                if (!in_array($p->id, $pagesorphaned)) {
                    $fields[] = new field($p->id,
                            $p->title, 0, 'chapter', 'wiki_pages', $this->wiki->id);
                    $fields[] = new field($p->id,
                            $p->cachedcontent, 1, 'cachedcontent', 'wiki_pages', $this->wiki->id);
                }
            }
        }
    }
}
