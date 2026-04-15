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
 * Optional capability interface for providers that expose API usage quota information.
 *
 * Callers must check instanceof usage_capable_interface before using these methods.
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface usage_capable_interface {
    /**
     * Returns the current API usage for the configured API key.
     *
     * The returned object must expose at minimum:
     *   - character->count  (int) characters used this period
     *   - character->limit  (int) maximum characters for this period
     *   - anyLimitReached() (bool) whether any usage limit has been hit
     *
     * @return object  Provider-specific usage object.
     */
    public function get_usage(): object;

    /**
     * Returns whether the configured API key belongs to a free-tier account.
     *
     * Free accounts may have restrictions (e.g. no rephrase, lower limits).
     *
     * @return bool
     */
    public function is_free_account(): bool;
}
