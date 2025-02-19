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

/**
 * Container for translatable text items.
 *
 * @package local_deepler
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class translatable {
    protected $id;
    protected $hierarchy;
    protected $tid;
    protected $displaytext;
    protected $text;
    protected $format;
    protected $table;
    protected $field;
    protected $tneeded;
    protected $section;
    protected $purpose;
    protected $iconurl;
    protected $pluginname;
    protected $translatedfieldname;

    public function __construct($id, $text, $level, $table, $field) {
        $this->id = $id;
        $this->hierarchy = "level$level";
        $this->table = $table;
        $this->field = $field;
        //$this->tid = $tid;
        $this->displaytext = $displaytext;
        $this->text = $text;
        $this->format = $format;

        //$this->tneeded = $tneeded;
        $this->section = $section;
        $this->purpose = $purpose;
        $this->iconurl = $iconurl;
        $this->pluginname = $pluginname;
        $this->translatedfieldname = $translatedfieldname;

    }

    /**
     * Stores the translation's statuses.
     *
     * @param int $id
     * @param string $table
     * @param string $field
     * @return false|mixed|\stdClass
     * @throws \dml_exception
     */
    private function store_status_db(int $id, string $table, string $field) {
        global $DB;
        // Skip if target lang is undefined.
        if ($this->lang == null || $id === -1) {
            $dummy = new stdClass();
            $dummy->id = "0";
            $dummy->s_lastmodified = "0";
            $dummy->t_lastmodified = "0";
            return $dummy;
        }
        // Build db params.
        $params = [
                't_id' => $id,
                't_lang' => $this->lang,
                't_table' => $table,
                't_field' => $field,
        ];

        // Insert tracking record if it does not exist.
        if (!$DB->record_exists($this->dbtable, $params)) {
            $time = time();
            $params['s_lastmodified'] = $time;
            $params['t_lastmodified'] = $time;
            $id = $DB->insert_record($this->dbtable, $params);
            $record = $DB->get_record($this->dbtable, ['id' => $id], 'id,s_lastmodified,t_lastmodified');
        } else {
            $record = $DB->get_record($this->dbtable, $params, 'id,s_lastmodified,t_lastmodified');
        }
        return $record;
    }
}
