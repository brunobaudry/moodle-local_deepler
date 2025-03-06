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

namespace local_deepler\local\data;

class activity {
    public string $modname;
    public int $section;
    public string $content;
    public int $parent;
    public int $id;
    public int $cmid;
    public string $qtype;

    /**
     * @param string $modname
     * @param string $content
     * @param int $id
     * @param int $cmid
     * @param int $section
     * @param int $parent
     */
    public function __construct(string $modname, int $id = 0, int $cmid = 0, int $section = 0, string $content = '',
            string $qtype = '', int $parent = 0) {
        $this->qtype = $qtype;
        $this->modname = $modname;
        $this->content = $content;
        $this->id = $id;
        $this->cmid = $cmid;
        $this->parent = $parent;
        $this->section = $section;
    }

}
