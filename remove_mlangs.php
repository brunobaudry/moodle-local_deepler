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
 * Local mlang remover.
 *
 * @package    local_mlangremover
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/version.php
 */

use local_deepler\local\data\course;
use local_deepler\output\remove_mlangs_page;

require_once(__DIR__ . '/../../config.php');

global $CFG;
global $PAGE;
global $DB;
require_once('./version.php');
require_once($CFG->dirroot . '/filter/multilang2/filter.php');
require_once(__DIR__ . '/version.php');

// Needed vars for processing.
try {
    $courseid = required_param('courseid', PARAM_INT);

} catch (moodle_exception $exception) {
    $courseid = required_param('course_id', PARAM_INT);
}
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$jsconfig = new stdClass();
// Setup page.
$context = context_course::instance($courseid);
$PAGE->set_context($context);
require_login();
require_capability('local/deepler:deletetranslations', $context);
// Set initial page layout.
$title = get_string('mlangremover', 'local_deepler');
$PAGE->set_url('/local/deepler/remove_mlangs.php', ['course_id' => $courseid]);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('base');
$PAGE->set_course($course);
// Adding page JS.
// Prepare course data.
$jsconfig->courseid = $courseid;
$jsconfig->debug = $CFG->debug;
$PAGE->requires->js_call_amd('local_deepler/mlangremover', 'init', [$jsconfig]);

$output = $PAGE->get_renderer('local_deepler');

// Output header.
echo $output->header();
// Course name heading.
$mlangfilter = new filter_multilang2($context, []);
echo $output->heading($mlangfilter->filter($course->fullname));
// Output translation grid.
$coursedata = new course($course);
// Build the page.

$renderable = new remove_mlangs_page($coursedata, $mlangfilter, $plugin->release);
echo $output->render($renderable);
// Output footer.
echo $output->footer();
