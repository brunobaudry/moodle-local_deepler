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

/**
 * Post-install hook for local_deepler.
 *
 * Seeds the additionalconf setting from the bundled JSON file so that the
 * value is stored in config_plugins on fresh installs, making it immediately
 * editable through the admin UI.
 *
 * @package    local_deepler
 * @copyright  2026 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Post-install hook.
 *
 * @return void
 */
function xmldb_local_deepler_install(): void {
    $jsonfile = __DIR__ . '/../additional_conf.json';
    if (file_exists($jsonfile)) {
        set_config('additionalconf', file_get_contents($jsonfile), 'local_deepler');
    }
}
