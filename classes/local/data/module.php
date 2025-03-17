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

use cm_info;

class module {
    /** @var \local_deepler\local\data\field[] */
    private array $subs;
    private cm_info $cm;
    private string $modname;

    public function __construct(cm_info $cminfo) {

        $this->cm = $cminfo;
        $this->modname = $cminfo->modname;
        var_dump($this->cm->modname);
        var_dump($this->isvisible());
    }

    public function isvisible(): bool {
        return $this->cm->visible == true;
    }

    public function getmainfields(): array {
        return field::getfieldsfrominfo($this->cm);
    }

    public function getsubs(): array {
        $class = "\local_deepler\local\data\subs\${$this->modname}";
        if ($this->modname === 'quiz') {
            $quiz = new $class($this->cm);
            return $quiz->getfields();
        } else {
            global $DB;
            $record = $DB->get_record($this->modname, ['id' => $this->cm->id]);
            try {
                $item = new $class($record);
                return $item->getfields();
            } catch (Exception $e) {
                debugging($e->getMessage());
                return [];
            }
        }
    }
}
