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
 * Local Deepler plugin glossaries management settings in user's pref.
 *
 * @package    local_deepler
 * @copyright  2022 Kaleb Heitzman
 * @copyright  2024 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_deepler\local\services\lang_helper;

require_once(__DIR__ . '/../../config.php');
require_login();

global $USER, $PAGE, $OUTPUT;

$context = context_user::instance($USER->id);
require_login();
require_capability('local/deepler:edittranslations', $context);

$PAGE->set_context($context);
// Load glossary manager.
$langhelper = new lang_helper();
$langhelper->initdeepl($USER);
$renderer = $PAGE->get_renderer('local_deepler', 'glossary');
$PAGE->set_url(new moodle_url('/local/deepler/glossarymanager.php'));
$PAGE->set_title(get_string('glossarymanagetitle', 'local_deepler'));
$PAGE->set_heading(get_string('glossarymanagetitle', 'local_deepler'));
$PAGE->set_pagelayout('base');

echo $OUTPUT->header();
// Prepare content.
$glossaries = $langhelper->getusersglossaries();
$poolglossaries = $langhelper->getpoolglossaries($glossaries);
$publicglossaries = $langhelper->getpublicglossaries();

/**
 * @param $type
 * @param $status
 * @param $data
 * @param $renderer
 * @return void
 * @throws \coding_exception
 */
function handle_glossary_status($type, $status, $data, $renderer) {
    $successKey = $type . 'success';
    $errorKey = $status !== '' ? $status : 'error' . $type;

    if ($status !== 'success') {
        echo $renderer->glossary_error(
                get_string("glossary:{$errorKey}:title", 'local_deepler'),
                get_string("glossary:{$errorKey}:body", 'local_deepler', $data)
        );
    } else {
        echo $renderer->glossary_success(
                get_string("glossary:{$successKey}:title", 'local_deepler'),
                get_string("glossary:{$successKey}:body", 'local_deepler', $data)
        );
    }
}

// Handle glossary deletion status.
if (isset($_REQUEST['deletestatus'])) {
    // Statuses are: deeplissue, failed, idmissing, invalidsesskey, success.
    $status = $_REQUEST['deletestatus'];
    $glossary = $_REQUEST['deleteglossary'] ?? '';
    handle_glossary_status('delete', $status, $glossary, $renderer);
}

// Handle glossary upload status.
if (isset($_REQUEST['uploadstatus'])) {
    // Statuses are: deeplissue, failed, fileerror, invalidsesskey, success, suffixerror, unknownerror.
    $status = $_REQUEST['uploadstatus'];
    $message = $_REQUEST['message'] ?? '';
    handle_glossary_status('upload', $status, $message, $renderer);
}
// Glossary table
if (!empty($publicglossaries)) {
    echo $renderer->glossary_table_view($publicglossaries, get_string('glossary:public:title', 'local_deepler'));
}
if (!empty($poolglossaries)) {
    echo $renderer->glossary_table_view($poolglossaries, get_string('glossary:pool:title', 'local_deepler'));
}
echo $renderer->glossary_table($glossaries);
echo $renderer->glossary_uploader();

// Add js.
$PAGE->requires->js_call_amd('local_deepler/glossary', 'init', []);
echo $OUTPUT->footer();
