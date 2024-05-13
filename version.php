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
 * Local Course Translator.
 *
 * @package    local_deepler
 * @copyright  2022 Kaleb Heitzman <kaleb@jamfire.io>
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/version.php
 */

defined('MOODLE_INTERNAL') || die();
$plugin->component = 'local_deepler'; // Full name of the plugin (used for diagnostics).
$plugin->version = 2024051300; // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires = 2020061500; // Requires Moodle 3.9 LTS.
$plugin->supported = [401, 404]; // Supported Moodle Versions.
$plugin->maturity = MATURITY_ALPHA; // Maturity level.
$plugin->release = 'v1.0.0'; // Semantic Versioning for CHANGES.md.
// Dependencies.
$plugin->dependencies = ['filter_multilang2' => 2020101300];
