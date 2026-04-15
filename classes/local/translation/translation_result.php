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

namespace local_deepler\local\translation;

/**
 * Normalized translation result value object returned by all translation providers.
 *
 * Both translate() and rephrase() return arrays of this object,
 * letting the external API classes work with a single result format
 * regardless of the underlying provider.
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class translation_result {
    /**
     * The translated or improved text.
     *
     * @var string
     */
    public string $text;

    /**
     * The detected source language code (may be empty if not detected/returned).
     *
     * @var string
     */
    public string $detected_source_language;

    /**
     * The target language code used for this result.
     *
     * @var string
     */
    public string $target_language;

    /**
     * Number of billed characters (0 if the provider does not report this).
     *
     * @var int
     */
    public int $billed_characters;

    /**
     * @param string $text
     * @param string $detected_source_language
     * @param string $target_language
     * @param int    $billed_characters
     */
    public function __construct(
        string $text,
        string $detected_source_language = '',
        string $target_language = '',
        int $billed_characters = 0
    ) {
        $this->text = $text;
        $this->detected_source_language = $detected_source_language;
        $this->target_language = $target_language;
        $this->billed_characters = $billed_characters;
    }
}
