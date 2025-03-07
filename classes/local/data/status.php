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

class status {
    public static string $dtable = 'local_deepler';
    public int $id;
    private int $t_id; // Table ID;
    private string $t_lang;
    private string $t_table;
    private string $t_field; // Table's field we want to inject mlangs.
    private string $t_lastmodified; // Last update timestamp.
    private string $s_lastmodified; // Initial timestamp.

    public function __construct(int $tableid, string $table, string $field, string $lang) {
        $this->t_id = $tableid;
        $this->id = 0;
        $this->t_lang = $lang;
        $this->t_table = $table;
        $this->t_field = $field;
        $this->t_lastmodified = '0';
        $this->s_lastmodified = '0';
    }

    public function getupdate() {
        global $DB;
        $wantedfields = 'id,s_lastmodified,t_lastmodified';
        $params = $this->getbasicparams();
        $r = null;
        if (!$this->exists()) {
            $time = time();

            $params['s_lastmodified'] = $time;
            $params['t_lastmodified'] = $time;
            $this->id = $DB->insert_record(self::$dtable, $params);
            $params = ['id' => $this->id];
        }
        $r = $DB->get_record(status::$dtable, $params, $wantedfields);
        $this->id = $r->id;
        $this->s_lastmodified = $r->s_lastmodified;
        $this->t_lastmodified = $r->t_lastmodified;
    }

    private function getbasicparams(): array {
        return [
                't_id' => $this->t_id,
                't_lang' => $this->lang,
                't_table' => $this->t_table,
                't_field' => $this->t_field,
        ];
    }

    private function exists() {
        global $DB;
        return $DB->record_exists(status::$dtable, $this->getbasicparams());
    }

    public function istranslationneeded() {
        return $this->s_lastmodified >= $this->t_lastmodified;
    }

    public function isready() {
        return $this->t_lang !== '';
    }
}
