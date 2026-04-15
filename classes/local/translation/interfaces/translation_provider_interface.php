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

namespace local_deepler\local\translation\interfaces;

use local_deepler\local\translation\translation_language;
use local_deepler\local\translation\translation_result;

/**
 * Core contract for any translation provider.
 *
 * All providers (DeepL, LibreTranslate, Google, etc.) must implement this interface.
 * Optional capabilities (rephrase, glossary, usage) are expressed via separate interfaces.
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface translation_provider_interface {
    /**
     * Returns the stable machine-readable provider ID (e.g. 'deepl', 'libretranslate').
     *
     * @return string
     */
    public function get_provider_id(): string;

    /**
     * Returns the human-readable provider name for display in admin UI.
     *
     * @return string
     */
    public function get_provider_name(): string;

    /**
     * Returns the list of languages that can be used as the source of a translation.
     *
     * @return translation_language[]
     */
    public function get_source_languages(): array;

    /**
     * Returns the list of languages that can be used as the target of a translation.
     *
     * @return translation_language[]
     */
    public function get_target_languages(): array;

    /**
     * Checks whether the given normalized language code is supported as a source language.
     *
     * @param string $lang Uppercase ISO code, e.g. 'EN' or 'EN-GB'.
     * @return bool
     */
    public function is_language_supported(string $lang): bool;

    /**
     * Translates an array of plain text or HTML strings.
     *
     * The options array is provider-specific; each provider filters/maps it via map_options().
     * Unknown options are silently ignored by providers that do not understand them.
     *
     * @param string[] $texts         Texts to translate.
     * @param string   $sourcelang    Source language code (may be empty for auto-detect).
     * @param string   $targetlang    Target language code.
     * @param array    $options       Provider-specific options (tag_handling, formality, glossary, …).
     * @return translation_result[]   One result per input text, in the same order.
     */
    public function translate(array $texts, string $sourcelang, string $targetlang, array $options = []): array;

    /**
     * Maps a generic options array to the options the provider actually understands.
     *
     * Called automatically by translate() / rephrase() before the real API call.
     * Implementations should strip unknown keys rather than fail.
     *
     * @param array $options
     * @return array
     */
    public function map_options(array $options): array;

    /**
     * Returns whether the provider has a valid API key / base URL configured.
     *
     * @return bool
     */
    public function is_api_key_set(): bool;
}
