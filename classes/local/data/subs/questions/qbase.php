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

namespace local_deepler\local\data\subs\questions;

use database_manager;
use Exception;
use lang_string;
use local_deepler\local\data\field;
use local_deepler\local\data\interfaces\editable_interface;
use local_deepler\local\data\interfaces\iconic_interface;
use local_deepler\local\data\interfaces\translatable_interface;
use moodle_url;
use question_definition;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/vendor/autoload.php');

/**
 * Base class for question types.
 *
 * @package    local_deepler
 * @copyright  2025  <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class qbase implements translatable_interface, editable_interface, iconic_interface {
    /**
     * @var \database_manager
     */
    protected database_manager $dbmanager;
    /** @var string */
    protected string $qtype;
    /** @var \question_definition */
    protected question_definition $question;
    /** @var string */
    protected string $qidcolname;
    /** @var moodle_url */
    private moodle_url $link;
    /** @var moodle_url */
    private moodle_url $iconurl;
    /** @var string|lang_string */
    private string|lang_string $pluginname;
    /** @var int|mixed */
    protected int $cmid;
    /** @var array
     * protected static array $additionals;*/

    /**
     * Constructor.
     *
     * @param array $params
     * @throws \core\exception\moodle_exception
     */
    public function __construct(array $params) {
        global $DB, $OUTPUT;

        $this->question = $params['question'];
        $this->cmid = $params['cmid'];

        $this->link = new moodle_url('/question/bank/editquestion/question.php',
                ['cmid' => $this->cmid, 'id' => $this->question->id]);
        $this->dbmanager = $DB->get_manager(); // Get the database manager.
        $this->qtype = $this->question->qtype->plugin_name(); // Get the question type.
        $this->iconurl = $OUTPUT->image_url('icon', $this->qtype);
        $this->pluginname = $this->question->qtype->local_name();
        $this->qidcolname = $this->question->qtype->questionid_column_name();
    }

    /**
     * Get the main fields.
     *
     * @return array
     */
    private function getmain(): array {
        $columns = [
                'name' => [],
                'questiontext' => [],
                'generalfeedback' => [],
        ];
        return field::getfieldsfromcolumns($this->question, 'question', $columns, $this->cmid);
    }

    /**
     * Get the options fields.
     *
     * @return array
     */
    private function gethints(): array {
        $fields = [];
        if (count($this->question->hints)) {
            foreach ($this->question->hints as $hint) {
                $hintfield = field::getfieldsfromcolumns($hint, 'question_hints',
                        ['hint' => []], $this->cmid);
                $fields = array_merge($fields, $hintfield);
            }

        }
        return $fields;
    }

    /**
     * Get the fields.
     *
     * @return array
     */
    public function getfields(): array {
        $fields = $this->getmain();
        $fields = array_merge($fields, $this->gethints());
        return array_merge($fields, $this->getsubs());
    }

    /**
     * Get the link.
     *
     * @return string
     */
    public function getlink(): string {
        return $this->link->out();
    }

    /**
     * Get the icon.
     *
     * @return string
     */
    public function geticon(): string {
        return $this->iconurl;
    }

    /**
     * Get the plugin name.
     *
     * @return string
     */
    public function getpluginname(): string {
        return $this->pluginname;
    }

    /**
     * Get the purpose.
     *
     * @return string
     */
    public function getpurpose(): string {
        return '';
    }

    /**
     * Find qtype's sub table fields.
     *
     * @return array
     * @throws \dml_exception
     */
    protected function getadditionalsubs(): array {
        global $DB;
        $qtypefields = [];
        $yamldef = [];
        try {
            $yamldef = field::$additionals[$this->qtype];
            if ($yamldef === null) {
                return $qtypefields;
            }
        } catch (Exception $e) {
            return $qtypefields;
        }
        foreach ($yamldef as $modnamesub => $colnames) {
            $id = $colnames['id'] ?? 'questionid'; // Normal plugin behaviour.
            $yamlfields = $colnames['fields'] ?? [];
            if (empty($yamlfields)) {
                continue;
            }
            $fields = implode(', ', array_merge(['id'], array_keys($yamlfields)));
            $rows = $DB->get_records("$modnamesub", [$id => $this->question->id], '', $fields);
            foreach ($rows as $row) {
                foreach ($yamlfields as $col => $clauses) {
                    $content = $row->{$col} ?? '';
                    $editable = true;
                    if (isset($clauses)) {
                        if (isset($clauses['exclude']) &&
                                ($clauses['exclude'] === $content || '' === trim($content))) {
                            continue;
                        }
                        if (isset($clauses['editable']) && $clauses['editable']) {
                            $editable = $clauses['editable'];
                        }
                    }
                    $format = $row->{"{$col}format"} ?? 0;
                    if (trim($content) !== '') {
                        $qtypefields[] = new field(
                                $row->id,
                                $content,
                                $format,
                                $col,
                                $modnamesub,
                                $this->cmid,
                                $editable
                        );
                    }
                }
            }
        }
        return $qtypefields;
    }

    /**
     * Preparewhereclause
     *
     * @param array $fields
     * @return array
     */
    private function preparewhereclause(array $fields): array {
        $where = [];
        foreach ($fields as $field => $clauses) {
            if ($clauses['where']) {
                $where[$field] = $clauses['where'];
            }
        }
        return $where;
    }
}
