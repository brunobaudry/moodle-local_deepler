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
 * Course Translator extended libs.
 *
 * @package      local_deepler
 * @copyright    2022 Kaleb Heitzman <kaleb@jamfire.io>
 * @copyright    2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add Translate Course to course settings menu.
 *
 * @param object $navigation
 * @param object $course
 * @return void
 * @package local_deepler
 */
function local_deepler_extend_navigation_course($navigation, $course): void {
    // Do not show in menu if no capability.
    if (!has_capability('local/deepler:edittranslations', context_course::instance($course->id))) {
        return;
    }
    // Get current language.
    $lang = current_language();

    // Build a moodle url.
    $url = new moodle_url("/local/deepler/translate.php?courseid=$course->id&lang=$lang");

    // Get title of translate page for navigation menu.
    $title = get_string('pluginname', 'local_deepler');

    // Navigation node.
    $translatecontent = navigation_node::create($title, $url, navigation_node::TYPE_CUSTOM, $title, 'translate');
    // Do not show in menu if no capability.
    $navigation->add_node($translatecontent);
    $navigation->showinflatnavigation = true; // Ensure it shows in the flat navigation.
}

/**
 * File handling.
 *
 * @param object $course
 * @param object $cm
 * @param \core\context $context
 * @param object $filearea
 * @param object $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 * @throws \coding_exception
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function local_deepler_pluginfile(object $course, object $cm, \core\context $context, object $filearea, object $args,
        bool $forcedownload, array $options): bool {

    error_log($cm);
    // Context validation.
    if ($context->contextlevel != CONTEXT_BLOCK && $context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }
    // File area whitelist.
    $validareas = ['custom_docs', 'user_uploads'];
    if (!in_array($filearea, $validareas)) {
        return false;
    }
    // Security checks.
    if (!has_capability('local/deepler:edittranslations', context_course::instance($course->id))) {
        return false;
    }
    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    if (!$file = $fs->get_file($context->id, 'local_deepler', $filearea, $itemid, $filepath, $filename)) {
        return false;
    }

    send_stored_file($file, 86400, 0, $forcedownload, $options);
    return true;
}
