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
 * Local Course Translator Translate Page.
 *
 * @package    local_deepler
 * @copyright  2022 Kaleb Heitzman <kaleb@jamfire.io>
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/Output_API
 */
/**
 * @todo MDL-0 check images tag handling in deepl.
 */

// Get libs.
use DeepL\AuthorizationException;
use DeepL\DeepLException;
use local_deepler\local\data\course;
use local_deepler\local\data\field;
use local_deepler\local\services\lang_helper;
use local_deepler\output\badsettings_page;
use local_deepler\output\nodeepl_page;
use local_deepler\output\sourcenotsupported_page;
use local_deepler\output\translate_page;

require_once(__DIR__ . '/../../config.php');

global $CFG;
global $PAGE;
global $DB;
global $USER;

require_once(__DIR__ . '/version.php');


// Needed vars for processing.
try {
    $courseid = required_param('courseid', PARAM_INT);
} catch (moodle_exception $exception) {
    $courseid = required_param('course_id', PARAM_INT);
}
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

// Setup page.
$context = context_course::instance($courseid);
$PAGE->set_context($context);
require_login();
require_capability('local/deepler:edittranslations', $context);
// Set initial page layout.
$title = get_string('pluginname', 'local_deepler');
$PAGE->set_url('/local/deepler/translate.php', ['course_id' => $courseid]);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('base');
$PAGE->set_course($course);
// Get the renderer.
$output = $PAGE->get_renderer('local_deepler');
// Output header.
echo $output->header();
// Course name heading.
try {
    $mlangfilter = new filter_multilang2\text_filter($context, []);
} catch (Exception $e) {
    $mlangfilter = new filter_multilang2($context, []);
}

echo $output->heading($mlangfilter->filter($course->fullname));
// Get Language helper.
$languagepack = new lang_helper();
try {
    field::$mintxtfieldsize = get_config('local_deepler', 'scannedfieldsize');
    $languagepack->initdeepl();
    if ($languagepack->iscurrentsupported()) {
        // Set js data.
        $jsconfig = new stdClass();
        // We get the version from the version dot php file.
        $jsconfig->version = $plugin->release;
        // Adds user ID for security checks in external calls.
        $jsconfig->userid = $USER->id;
        // Adds the user's prefered editor to the jsconfig.
        $defaulteditor = strstr($CFG->texteditors, ',', true);
        $userprefs = get_user_preferences();
        $userseditor = $userprefs['htmleditor'];
        $jsconfig->userPrefs = $userseditor ?? $defaulteditor;
        // Adds course id.
        $jsconfig->courseid = $courseid;
        // Add the debug setting for logger.
        $jsconfig->debug = $CFG->debug;
        // Adds the language settings strings to the jsconfig.
        $jsconfig = $languagepack->prepareconfig($jsconfig);
        // Adding page JS.
        $PAGE->requires->js_call_amd('local_deepler/deepler', 'init', [$jsconfig]);
        // Create the structure.
        $coursedata = new course($course);
        // Build the page.
        $renderable = new translate_page($coursedata, $mlangfilter, $languagepack, $plugin->release);
        echo $output->render($renderable);
    } else {
        $renderable = new sourcenotsupported_page(get_string('onomatopoeia', 'local_deepler'));
        echo $output->render($renderable);
    }
} catch (AuthorizationException $e) {
    // Deepl could not be initialized.
    $renderable = new badsettings_page(get_string('onomatopoeia', 'local_deepler'));
    echo $output->render($renderable);
} catch (DeepLException $e) {
    // Deepl cannot connect.
    if ($languagepack->isapikeynoset()) {
        $renderable = new badsettings_page(get_string('onomatopoeia', 'local_deepler'));
    } else {
        $renderable = new nodeepl_page(get_string('onomatopoeia', 'local_deepler'));
    }
    echo $output->render($renderable);
}
// Output footer.
echo $output->footer();
