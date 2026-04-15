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
 * Normalized language value object returned by all translation providers.
 *
 * Providers convert their native language objects into this common structure
 * so that lang_helper and the frontend do not need provider-specific code.
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class translation_language {
    /**
     * Normalized language code.
     *
     * Uppercase ISO 639-1 with optional region, e.g. 'EN', 'EN-GB', 'PT-BR'.
     *
     * @var string
     */
    public string $code;

    /**
     * Human-readable language name in English, e.g. 'English (British)'.
     *
     * @var string
     */
    public string $name;

    /**
     * Whether this language supports the formality option (formal/informal register).
     *
     * @var bool
     */
    public bool $supports_formality;

    /**
     * Whether this language supports the rephrase/improve feature.
     *
     * @var bool
     */
    public bool $supports_rephrase;

    /**
     * @param string $code
     * @param string $name
     * @param bool   $supports_formality
     * @param bool   $supports_rephrase
     */
    public function __construct(
        string $code,
        string $name,
        bool $supports_formality = false,
        bool $supports_rephrase = false
    ) {
        $this->code = $code;
        $this->name = $name;
        $this->supports_formality = $supports_formality;
        $this->supports_rephrase = $supports_rephrase;
    }
}
