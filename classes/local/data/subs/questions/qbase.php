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
use lang_string;
use local_deepler\local\data\field;
use local_deepler\local\data\interfaces\editable_interface;
use local_deepler\local\data\interfaces\iconic_interface;
use local_deepler\local\data\interfaces\translatable_interface;
use moodle_url;
use question_definition;

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

    /**
     * Constructor.
     *
     * @param array $params
     * @throws \core\exception\moodle_exception
     */
    public function __construct(array $params) {
        global $DB, $OUTPUT;
        $this->question = $params['question'];

        $this->link = new moodle_url('/question/bank/editquestion/question.php',
                ['cmid' => $params['cmid'], 'id' => $this->question->id]);
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
        $columns = field::filterdbtextfields('question');
        return field::getfieldsfromcolumns($this->question, 'question', $columns);
    }

    /**
     * Get the options fields.
     *
     * @return array
     */
    private function getoptions(): array {
        $fields = [];
        if ($this->dbmanager->table_exists($this->qtype . '_options')) {
            $columns = field::filterdbtextfields($this->qtype . '_options');
            $optionsfields[] = field::getfieldsfromcolumns($this->question, $this->qtype . '_options', $columns);
            array_merge($fields, $optionsfields);
        }
        if ($this->dbmanager->table_exists($this->qtype . '_choice')) {
            $columns = field::filterdbtextfields($this->qtype . '_choice');
            $choicesfields[] = field::getfieldsfromcolumns($this->question, $this->qtype . '_choice', $columns);
            array_merge($fields, $choicesfields);
        }
        if (count($this->question->hints)) {
            foreach ($this->question->hints as $hint) {
                $columns = field::filterdbtextfields('question_hints');
                $hintfields[] = field::getfieldsfromcolumns($this->question, 'question_hints', $columns);
                array_merge($fields, $hintfields);
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
        $fields = array_merge($fields, $this->getoptions());
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
     * Get the childs.
     *
     * @return array
     */
    abstract protected function getsubs(): array;
}
