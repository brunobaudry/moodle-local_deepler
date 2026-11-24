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

defined('MOODLE_INTERNAL') || die();
/**
 * Factored test string.
 */
const LOCAL_DEEPLER_EDITTRANSLATIONS = 'local/deepler:edittranslations';
require_once(__DIR__ . '/classes/vendor/autoload.php');

use local_deepler\local\services\navigation_helper;

/**
 * Add Translate Course to the course settings menu.
 *
 * @param mixed $navigation
 * @param mixed $course
 * @return void
 * @throws \coding_exception
 * @package local_deepler
 */
function local_deepler_extend_navigation_course(mixed $navigation, mixed $course): void {
    navigation_helper::extend_navigation_course($navigation, $course);
}

/**
 * Extends settings navigation for activities, resources, and sections.
 *
 * @param \settings_navigation $settingsnav
 * @param \context $context
 * @return void
 */
function local_deepler_extend_settings_navigation(\settings_navigation $settingsnav, \context $context): void {
    navigation_helper::extend_settings_navigation($settingsnav, $context);
}

/**
 * Add entry in user prefs.
 *
 * @param mixed $navigation
 * @param mixed $user
 * @param mixed $usercontext
 * @param mixed $course
 * @param mixed $coursecontext
 * @return array
 * @throws \coding_exception
 */
function local_deepler_extend_navigation_user_settings($navigation, $user, $usercontext, $course, $coursecontext) {
    $prefs = [];
    global $PAGE;
    // Only inject if user is on the preferences page.
    $onpreferencepage = $PAGE->url->compare(new moodle_url('/user/preferences.php'), URL_MATCH_BASE);
    if (!$onpreferencepage) {
        return null;
    }
    if (has_capability(LOCAL_DEEPLER_EDITTRANSLATIONS, $usercontext)) {
        $url = new moodle_url('/local/deepler/glossarymanageruser.php');
        $node = navigation_node::create(
            get_string('glossary:manage:title', 'local_deepler'),
            $url,
            navigation_node::TYPE_SETTING
        );
        $usernode = $navigation->find('useraccount', navigation_node::TYPE_CONTAINER);
        $usernode->add_node($node);
    }
    return $prefs;
}

/**
 * File handling.
 *
 * Called by file_pluginfile(), which passes a null $cm (and a null $course for the system context).
 *
 * @param stdClass|null $course
 * @param stdClass|null $cm
 * @param \core\context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool false if the file is not served
 * @throws \coding_exception
 */
function local_deepler_pluginfile(
    $course,
    $cm,
    \core\context $context,
    string $filearea,
    array $args,
    bool $forcedownload,
    array $options = []
): bool {
    // Context validation.
    if ($context->contextlevel != CONTEXT_BLOCK && $context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }
    // File area whitelist.
    $validareas = ['custom_docs', 'user_uploads'];
    if (!in_array($filearea, $validareas, true)) {
        return false;
    }
    // Security checks, in the context the file belongs to.
    require_login($course, false, $cm);
    if (!has_capability(LOCAL_DEEPLER_EDITTRANSLATIONS, $context)) {
        return false;
    }
    $itemid = (int) array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    if (!$file = $fs->get_file($context->id, 'local_deepler', $filearea, $itemid, $filepath, $filename)) {
        return false;
    }

    send_stored_file($file, 86400, 0, $forcedownload, $options);
    return true;
}

/**
 * Serve question files embedded in translatable fields, outside any question attempt.
 *
 * Called by question_pluginfile() for URLs built with question_rewrite_question_preview_urls()
 * by \local_deepler\local\services\utils::resolve_pluginfiles().
 *
 * @param \core\context $previewcontext Context the question is shown in (the course).
 * @param int $questionid
 * @param \core\context $filecontext
 * @param string $filecomponent
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return void Does not return: serves the file or sends a not found.
 */
function local_deepler_question_preview_pluginfile(
    $previewcontext,
    $questionid,
    $filecontext,
    $filecomponent,
    $filearea,
    $args,
    $forcedownload,
    $options = []
): void {
    [$context, $course, $cm] = get_context_info_array($previewcontext->id);
    require_login($course, false, $cm);

    $file = \local_deepler\local\services\utils::get_question_preview_file(
        $previewcontext,
        (int) $questionid,
        $filecontext,
        (string) $filecomponent,
        (string) $filearea,
        $args
    );
    if (!$file) {
        send_file_not_found();
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Fetchall tokens mappings
 *
 * @return array
 * @throws \dml_exception
 */
function get_all_tokens() {
    global $DB;
    return $DB->get_records('local_deepler_tokens');
}
