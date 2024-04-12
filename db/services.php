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
 * Local Deepler
 *
 * @package    local_deepler
 * @copyright  2022 Kaleb Heitzman <kaleb@jamfire.io>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/Web_services_API
 */

defined('MOODLE_INTERNAL') || die();

// Define edittranslation capability.
define('LOCAL_DEEPLER_CAP', 'local/deepler:edittranslations');

// Add services definition.
$services = [
        'Course Translator' => [
                'functions' => ['local_deepler_update_translation'],
                'requiredcapability' => LOCAL_DEEPLER_CAP,
                'restrictedusers' => 0,
                'enabled' => 1,
                'shortname' => 'CourseTranslator',
                'downloadfiles' => 0,
                'uploadfiles' => 0,
        ],
];

// Add functions for webservices.
$functions = [
        'local_deepler_update_translation' => [
                'classname' => 'local_deepler_external',
                'methodname' => 'update_translation',
                'classpath' => 'local/deepler/externallib.php',
                'description' => 'Update translation',
                'type' => 'write',
                'ajax' => true,
                'capabilities' => LOCAL_DEEPLER_CAP,
        ],
        'local_deepler_get_field' => [
                'classname' => 'local_deepler_external',
                'methodname' => 'get_field',
                'classpath' => 'local/deepler/externallib.php',
                'description' => 'Get field data',
                'type' => 'read',
                'ajax' => true,
                'capabilities' => LOCAL_DEEPLER_CAP,
        ],
];
