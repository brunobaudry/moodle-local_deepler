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
 * Local Course Translator Files Page.
 *
 * @package    local_deepler
 * @copyright  2026 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use DeepL\AuthorizationException;
use DeepL\DeepLException;
use local_deepler\local\services\file_scanner;
use local_deepler\local\services\lang_helper;
use local_deepler\output\badsettings_page;
use local_deepler\output\files_page;
use local_deepler\output\files_renderer;
use local_deepler\output\nodeepl_page;
use local_deepler\output\sourcenotsupported_page;

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/version.php');
require_once($CFG->dirroot . '/filter/multilang2/filter.php');

global $CFG, $PAGE, $DB, $USER;

/**
 * Factored test string.
 */
const LOCAL_DEEPLER_OUTPUT_MULTILANG_2_TEXT_FILTER = 'local_deepler\\output\\Multilang2TextFilter';
if (!class_exists(LOCAL_DEEPLER_OUTPUT_MULTILANG_2_TEXT_FILTER)) {
    if (class_exists('\\filter_multilang2')) {
        class_alias('\\filter_multilang2', LOCAL_DEEPLER_OUTPUT_MULTILANG_2_TEXT_FILTER);
    } else if (class_exists('\\core_filters\\text_filter')) {
        class_alias('\\core_filters\\text_filter', LOCAL_DEEPLER_OUTPUT_MULTILANG_2_TEXT_FILTER);
    }
}

try {
    $courseid = required_param('courseid', PARAM_INT);
} catch (moodle_exception $exception) {
    $courseid = required_param('course_id', PARAM_INT);
}

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

$PAGE->set_context($context);
require_login();
require_capability('local/deepler:translatefiles', $context);

if (!get_config('local_deepler', 'filetranslation')) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('filetranslation', 'local_deepler'));
}

$title = get_string('files:title', 'local_deepler');
$PAGE->set_url('/local/deepler/files.php', ['courseid' => $courseid]);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('base');
$PAGE->set_course($course);

$mlangfilter = new \local_deepler\output\Multilang2TextFilter($context, []);

$output = $PAGE->get_renderer('local_deepler');
echo $output->header();
echo $output->heading($mlangfilter->filter(format_string($course->fullname)));

$version = $plugin->release;
$languagepack = new lang_helper();

try {
    if ($languagepack->initdeepl($USER, $version)) {
        $scanner = new file_scanner($course);
        $files = $scanner->get_course_files();

        $glossaries = $languagepack->getusersglossaries() ?? [];
        $poolglossaries = $languagepack->getpoolglossaries($glossaries) ?? [];
        $publicglossaries = $languagepack->getpublicglossaries() ?? [];
        $allglossaries = array_merge($glossaries, $publicglossaries, $poolglossaries);

        $glossarydata = [];
        foreach ($allglossaries as $g) {
            $nameadd = ' (' . get_string('glossary:visibility:private', 'local_deepler') . ')';
            if ($g->shared === 1) {
                $nameadd = ' (' . get_string('glossary:visibility:pool', 'local_deepler') . ')';
            } else if ($g->shared === 2) {
                $nameadd = ' (' . get_string('glossary:visibility:public', 'local_deepler') . ')';
            }
            $glossarydata[] = [
                'id' => $g->glossaryid,
                'name' => $g->name . $nameadd,
                'sourcelang' => strtolower($g->sourcelang),
                'targetlang' => strtolower($g->targetlang),
            ];
        }

        $jsconfig = new stdClass();
        $jsconfig->version = $version;
        $jsconfig->courseid = $courseid;
        $jsconfig->userid = $USER->id;
        $jsconfig->sourcelang = $languagepack->getcurrentlang(true);
        $jsconfig->glossaries = $glossarydata;

        $PAGE->requires->js_call_amd('local_deepler/files', 'init', [$jsconfig]);

        $renderable = new files_page($course, $files, $languagepack, $version, $mlangfilter);
        $filesrenderer = new files_renderer($PAGE, null);
        echo $filesrenderer->render_files_page($renderable);
    } else if (empty($languagepack->preparesourcesoptionlangs())) {
        $renderable = new sourcenotsupported_page(get_string('onomatopoeia', 'local_deepler'));
        echo $output->render($renderable);
    } else {
        $renderable = new badsettings_page(get_string('onomatopoeia', 'local_deepler'));
        echo $output->render($renderable);
    }
} catch (AuthorizationException $e) {
    $renderable = new badsettings_page(get_string('onomatopoeia', 'local_deepler'));
    echo $output->render($renderable);
} catch (DeepLException $e) {
    if ($languagepack->isapikeynoset()) {
        $renderable = new badsettings_page(get_string('onomatopoeia', 'local_deepler'));
    } else {
        $renderable = new nodeepl_page(get_string('onomatopoeia', 'local_deepler'));
    }
    echo $output->render($renderable);
}

echo $output->footer();
