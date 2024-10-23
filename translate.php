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
 * @todo MDL-0 use deepl-php instead of js ajax for further maintainability and absctraction
 * @todo MDL-0 check images tag handling in deepl.
 */

// Get libs.
use local_deepler\data\course_data;
use local_deepler\data\lang_helper;
use local_deepler\output\translate_page;

require_once(__DIR__ . '/../../config.php');

global $CFG;
global $PAGE;
global $DB;
require_once($CFG->dirroot . '/filter/multilang2/filter.php');
require_once('./classes/output/translate_page.php');
require_once('./classes/output/nodeepl_page.php');
require_once('./classes/data/course_data.php');
require_once('./classes/data/lang_helper.php');
require_once($CFG->dirroot . '/lib/editorlib.php');

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
// Get Language helper.
$languagepack = new lang_helper();
$initok = $languagepack->init('');
// Get the renderer.
$output = $PAGE->get_renderer('local_deepler');
// Output header.
echo $output->header();
// Course name heading.
$mlangfilter = new filter_multilang2($context, []);
echo $output->heading($mlangfilter->filter($course->fullname));

if ($initok) {
    if ($languagepack->iscurrentsupported()) {
        // Set js data.
        $jsconfig = new stdClass();
        $jsconfig = $languagepack->addlangproperties($jsconfig);
        // Prepare course data.
        $jsconfig->courseid = $courseid;
        $jsconfig->debug = $CFG->debug;

        $defaulteditor = strstr($CFG->texteditors, ',', true);
        $userprefs = get_user_preferences();
        // Get users prefrences to pass the editor's type to js.
        $jsconfig->userPrefs = $userprefs['htmleditor'] ?? $defaulteditor;

        // Adding page JS.
        $PAGE->requires->js_call_amd('local_deepler/deepler', 'init', [$jsconfig]);

        // Output translation grid.
        $coursedata = new course_data($course, $languagepack->targetlang, $context->id);

        // Build the page.
        $prepareddata = $coursedata->getdata();
        $renderable = new translate_page($course, $prepareddata, $mlangfilter, $languagepack);
        echo $output->render($renderable);
        // Output footer.
        echo $output->footer();
    } else {
        $renderable = new \local_deepler\output\sourcenotsupported_page();
        echo $output->render($renderable);
        // Output footer.
        echo $output->footer();
    }

} else {
    $renderable = new \local_deepler\output\nodeepl_page();
    echo $output->render($renderable);
    // Output footer.
    echo $output->footer();
}
