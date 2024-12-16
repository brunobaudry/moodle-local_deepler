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
 * Environment Loader Class
 *
 * @package    local_deepler
 * @copyright  2024 bruno.baudry@bfh.ch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class env_loader
 * Loads environment variables from a .env file.
 *
 * @package local_myplugin
 */
class env_loader {
    /**
     * Load environment variables from a .env file.
     * If file not found will fail gracefully.
     *
     * @param string $path Path to the .env file.
     */
    public static function load($path): void {
        if (!file_exists($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments.
            if (str_starts_with(trim($line), '#')) {
                continue;
            }
            putenv(trim($line));
        }
    }
}
