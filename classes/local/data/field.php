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

use local_deepler\local\services\utils;

/**
 * Class field encapsulates the state and behavior of an individual translatable field.
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class field {
    /** @var int|null Maximum character length if constrained */
    private ?int $maxlength = null;

    /** @var int Global counter for simple fields */
    public static int $countsimplefields = 0;

    /** @var int Global counter for formatted area fields */
    public static int $countareafields = 0;

    /** @var string Target language code for DeepL translation status */
    public static string $targetlangdeepl = '';

    /** @var string Raw text content */
    private string $text;

    /** @var string Database table name */
    private string $table;

    /** @var string Field/column name */
    private string $field;

    /** @var int Record ID */
    private int $id;

    /** @var int Course module ID */
    private int $cmid;

    /** @var int Text format (e.g. FORMAT_HTML, FORMAT_MOODLE) */
    private int $format;

    /** @var int Translation status ID */
    private int $tid;

    /** @var string Display text (with resolved plugin files) */
    private string $displaytext;

    /** @var status Translation status object */
    private status $status;

    /** @var bool Whether the field is editable */
    private bool $editable;

    /**
     * Create a new field to use as atomic source for translations.
     *
     * @param int $id Record ID
     * @param string $text Raw text
     * @param int $format Text format (0 or 1)
     * @param string $field Column name
     * @param string $table Table name
     * @param int $cmid Course module ID
     * @param bool $editable Whether editable for translation
     * @param int|null $maxlength Maximum length
     * @throws \dml_exception
     */
    public function __construct(
        int $id,
        string $text,
        int $format,
        string $field,
        string $table,
        int $cmid = 0,
        bool $editable = true,
        ?int $maxlength = null,
    ) {
        $this->id = $id;
        $this->editable = $editable;
        $this->field = $field;
        $this->table = $table;
        $this->format = $format;
        $this->cmid = $cmid;
        $this->maxlength = $maxlength;
        $this->displaytext = $this->text = $text;
        if ($this->format === 1) {
            self::$countareafields++;
            $this->preparetexts();
        } else {
            self::$countsimplefields++;
        }
        $this->init_db();
    }

    /**
     * Check if the field is editable.
     *
     * @return bool
     */
    public function iseditable(): bool {
        return $this->editable;
    }

    /**
     * Getter for maximum length.
     *
     * @return int|null
     */
    public function get_maxlength(): ?int {
        return $this->maxlength;
    }

    /**
     * Setter for maximum length.
     *
     * @param int|null $maxlength
     * @return void
     */
    public function set_maxlength(?int $maxlength): void {
        $this->maxlength = $maxlength;
    }

    /**
     * Getter for record ID.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Getter for course module ID.
     *
     * @return int
     */
    public function get_cmid(): int {
        return $this->cmid;
    }

    /**
     * Getter for field name.
     *
     * @return string
     */
    public function get_tablefield(): string {
        return $this->field;
    }

    /**
     * Getter for table name.
     *
     * @return string
     */
    public function get_table(): string {
        return $this->table;
    }

    /**
     * Getter for status.
     *
     * @return \local_deepler\local\data\status
     */
    public function get_status(): status {
        return $this->status;
    }

    /**
     * Getter for text.
     *
     * @return string
     */
    public function get_text(): string {
        return $this->text;
    }

    /**
     * Getter for table id.
     *
     * @return int
     */
    public function get_tid(): int {
        return $this->tid;
    }

    /**
     * Getter for field format 0 or 1.
     *
     * @return int
     */
    public function get_format(): int {
        return $this->format;
    }

    /**
     * Getter for the display text (to display on the translation page).
     *
     * @return string
     */
    public function get_displaytext(): string {
        return $this->displaytext;
    }

    /**
     * Generate the field key identifier.
     *
     * @return string
     */
    public function getkey(): string {
        return "$this->table[$this->id][$this->field][$this->cmid]";
    }

    /**
     * Generate the field key identifier other format.
     *
     * @return string
     */
    public function getkeyid(): string {
        return "{$this->table}-{$this->id}-{$this->field}-{$this->cmid}";
    }

    /**
     * Checks if the multilang tag OTHER and the current/source language is already there.
     *
     * @param string $lang
     * @return bool
     */
    public function check_field_has_other_and_sourcetag(string $lang): bool {
        return str_contains($this->text, '{mlang other}') && str_contains($this->text, "{mlang $lang");
    }

    /**
     * Building the text attributes.
     *
     * @return void
     * @throws \dml_exception
     */
    private function preparetexts(): void {
        if (str_contains($this->displaytext, '@@PLUGINFILE@@')) {
            $this->displaytext = utils::resolve_pluginfiles($this);
        }
    }

    /**
     * Stores the translation's statuses.
     *
     * @return void
     */
    private function init_db(): void {
        $this->status = new status($this->id, $this->table, $this->field, self::$targetlangdeepl);
        // Skip if target lang is undefined.
        if ($this->status->isready()) {
            $this->status->getupdate();
        }
        $this->tid = $this->status->get_id();
    }
}
