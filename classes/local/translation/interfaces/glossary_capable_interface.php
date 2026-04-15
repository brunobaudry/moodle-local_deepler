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

/**
 * Optional capability interface for providers that support glossaries / terminology management.
 *
 * Callers must check instanceof glossary_capable_interface before using these methods.
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface glossary_capable_interface {
    /**
     * Returns all glossaries available for the current API key.
     *
     * @return object[]  Provider-specific glossary metadata objects.
     */
    public function list_glossaries(): array;

    /**
     * Returns metadata for a single glossary.
     *
     * @param string $glossaryid
     * @return object
     */
    public function get_glossary(string $glossaryid): object;

    /**
     * Returns the entries of a glossary.
     *
     * @param string $glossaryid
     * @return object  Provider-specific glossary entries object (e.g. DeepL GlossaryEntries).
     */
    public function get_glossary_entries(string $glossaryid): object;

    /**
     * Deletes a glossary from the provider.
     *
     * @param string $glossaryid  The provider's glossary UUID/ID (not the local DB row ID).
     * @return void
     */
    public function delete_glossary(string $glossaryid): void;
}
