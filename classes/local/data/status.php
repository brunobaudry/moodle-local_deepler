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
 * Class status for storing the translation status in the DB.
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class status {
    /**
     * @var string $dtable
     */
    public static string $dtable = 'local_deepler';
    /** @var int */
    private int $id;
    /** @var int Table ID. */
    private int $t_id;
    /** @var string */
    private string $t_lang;
    /** @var string */
    private string $t_table;
    /** @var string */
    private string $t_field; // Table's field we want to inject mlangs.
    /** @var int */
    private int $t_lastmodified; // Last modified time of translation.
    /** @var int */
    private int $s_lastmodified; // Last modified time of source text.

    /**
     * Build a translation status object.
     *
     * @param int $tableid
     * @param string $table
     * @param string $field
     * @param string $lang
     */
    public function __construct(int $tableid, string $table, string $field, string $lang) {
        $this->t_id = $tableid;
        $this->id = 0;
        $this->t_lang = $lang;
        $this->t_table = $table;
        $this->t_field = $field;
        $this->t_lastmodified = 0;
        $this->s_lastmodified = 0;
    }

    /**
     * Get the status record.
     *
     * @throws \dml_exception
     */
    public function getupdate(): void {
        global $DB;
        $wantedfields = 'id,s_lastmodified,t_lastmodified';
        $params = $this->getbasicparams();
        if (!$this->exists()) {
            $time = time();
            $params['s_lastmodified'] = $time;
            $params['t_lastmodified'] = $time;
            $this->id = $DB->insert_record(self::$dtable, $params);
            $params = ['id' => $this->id];
        }
        $r = $DB->get_record(self::$dtable, $params, $wantedfields);
        $this->id = $r->id;
        $this->s_lastmodified = $r->s_lastmodified;
        $this->t_lastmodified = $r->t_lastmodified;
    }

    /**
     * returns the basic parameters.
     *
     * @return array
     */
    private function getbasicparams(): array {
        return [
                't_id' => $this->t_id,
                't_lang' => $this->t_lang,
                't_table' => $this->t_table,
                't_field' => $this->t_field,
        ];
    }

    /**
     * Check if the record exists.
     *
     * @throws \dml_exception
     */
    private function exists(): bool {
        global $DB;
        return $DB->record_exists(self::$dtable, $this->getbasicparams());
    }

    /**
     * Compare the last modified timestamps.
     *
     * @return bool
     */
    public function istranslationneeded(): bool {
        return $this->s_lastmodified >= $this->t_lastmodified;
    }

    /**
     * Lang muss be set before we can start.
     *
     * @return bool
     */
    public function isready(): bool {
        return $this->t_lang !== '';
    }

    /**
     * Getters for the status id.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }
}
