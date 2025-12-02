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
use core\plugin_manager;
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
require_once(__DIR__ . '/version.php');


global $CFG;
global $PAGE;
global $DB;
global $USER;

require_once($CFG->dirroot . '/filter/multilang2/filter.php'); // Ensure filter_multilang2 is available.

// Needed vars for processing.
try {
    $courseid = required_param('courseid', PARAM_INT);
} catch (moodle_exception $exception) {
    $courseid = required_param('course_id', PARAM_INT);
}
// Section -99 is one selected. Section -1 is all else proper section id.
$sectionid = optional_param('section_id', -99, PARAM_INT);
$activityid = optional_param('activity_id', -1, PARAM_INT);
// Load the cours in DB.
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


// Normalize filter class, workaround to match MDL version from 401 to 501.
if (!class_exists('local_deepler\\output\\Multilang2TextFilter')) {
    if (class_exists('\\filter_multilang2')) {
        class_alias('\\filter_multilang2', 'local_deepler\\output\\Multilang2TextFilter');
    } else if (class_exists('\\core_filters\\text_filter')) {
        /**
         * Wrapper.
         *
         * @package local_deepler
         * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
         * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
         */
        class Multilang2TextFilter extends \core_filters\text_filter {
            /**
             * Main function.
             *
             * @param $text
             * @param array $options
             * @return mixed
             */
            public function filter($text, array $options = []) {
                // Implement minimal logic or leave empty if not needed.
                return $text;
            }
        }
        class_alias('Multilang2TextFilter', 'local_deepler\\output\\Multilang2TextFilter');
    }
}

// Instantiate the normalized class.
$mlangfilter = new \local_deepler\output\Multilang2TextFilter($context, []);


echo $output->heading($mlangfilter->filter($course->fullname));
$version = $plugin->release;
// Get Language helper.
$languagepack = new lang_helper();
try {
    field::$mintxtfieldsize = get_config('local_deepler', 'scannedfieldsize');
    $initok = $languagepack->initdeepl($USER, $version);
    if ($initok && $languagepack->iscurrentsupported()) {
        // Set js data.
        $jsconfig = new stdClass();
        // We get the version from the version dot php file.
        $jsconfig->version = $version;
        // Adds user ID for security checks in external calls.
        $jsconfig->userid = $USER->id;
        // Adds the user's prefered editor to the jsconfig.
        $defaulteditor = strstr($CFG->texteditors, ',', true);
        $userprefs = get_user_preferences();
        $jsconfig->userPrefs = $userprefs['htmleditor'] ?? $defaulteditor;
        // Adds course id.
        $jsconfig->courseid = $courseid;
        // Add the debug setting for logger.
        $jsconfig->debug = $CFG->debug;
        // Adds the language settings strings to the jsconfig.
        $jsconfig = $languagepack->prepareconfig($jsconfig);
        // Pass the breadcrumbs subs max length to JS.
        $jsconfig->crumbsmaxlen = get_config('local_deepler', 'breadcrumblength');
        $jsconfig->cookieduration = get_config('local_deepler', 'cookieduration');
        // Adding page JS.
        $PAGE->requires->js_call_amd('local_deepler/deepler', 'init', [$jsconfig]);
        // Create the structure.
        $coursedata = new course($course, $sectionid, $activityid);
        // Build the page.
        $renderable = new translate_page($coursedata, $mlangfilter, $languagepack, $version, $jsconfig->userPrefs);
        echo $output->render($renderable);
    } else if ($initok) {
        $renderable = new sourcenotsupported_page(get_string('onomatopoeia', 'local_deepler'));
        echo $output->render($renderable);
    } else {
        $renderable = new badsettings_page(get_string('onomatopoeia', 'local_deepler'));
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
