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

use local_deepler\local\translation\translation_result;

/**
 * Optional capability interface for providers that support text improvement / rephrasing.
 *
 * Currently only DeepL (paid accounts) supports this. Callers must check
 * instanceof rephrase_capable_interface before attempting to call these methods.
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface rephrase_capable_interface {
    /**
     * Improves / rephrases an array of texts in the given target language.
     *
     * @param string[] $texts       Texts to improve.
     * @param string   $targetlang  Target language code.
     * @param array    $options     Provider-specific validated options (from build_rephrase_params()).
     * @return translation_result[]
     */
    public function rephrase(array $texts, string $targetlang, array $options = []): array;

    /**
     * Returns the list of language codes that support the rephrase feature.
     *
     * @return string[]  Normalized uppercase codes, e.g. ['EN-GB', 'EN-US', 'DE', …].
     */
    public function get_rephrase_languages(): array;

    /**
     * Returns whether the current API key/account tier allows rephrasing.
     *
     * For DeepL this requires a paid (non-free) account.
     *
     * @return bool
     */
    public function can_rephrase(): bool;

    /**
     * Validates and builds the provider-specific parameter bag for a rephrase request.
     *
     * @param string      $targetlang  Target language code.
     * @param string|null $style       Writing style identifier, or null.
     * @param string|null $tone        Tone identifier, or null.
     * @return array  Validated options ready to pass to rephrase().
     */
    public function build_rephrase_params(string $targetlang, ?string $style, ?string $tone): array;
}
