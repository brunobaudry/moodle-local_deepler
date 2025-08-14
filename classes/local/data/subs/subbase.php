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

use cm_info;

/**
 * Base class for subs.
 *
 * @package    local_deepler
 * @copyright  2025  <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class subbase {
    /**
     * @var \cm_info
     */
    protected cm_info $cm;
    /**
     * @var false|mixed|\stdClass
     */
    protected mixed $record;

    /**
     * Constuctor.
     *
     * @param \cm_info $cm
     * @throws \dml_exception
     */
    public function __construct(cm_info $cm) {
        global $DB;
        $this->cm = $cm;
        $this->record = $DB->get_record($this->cm->modname, ['id' => $this->cm->instance]);
    }
}
