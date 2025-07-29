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
$errortitle = '';
$errorbody = '';
$glossaries = $langhelper->getusersglossaries();
$a = '';
// Handle glossary deletion status
if (isset($_REQUEST['deletestatus'])) {
    $status = $_REQUEST['deletestatus'];
    $glossary = $_REQUEST['deleteglossary'] ?? '';

    if ($status !== 'success') {
        $errortitle = $status !== '' ? $status : 'errordelete';
        echo $renderer->glossary_error(
                get_string("glossary:{$status}:title", 'local_deepler'),
                get_string("glossary:{$errortitle}:body", 'local_deepler', $glossary)
        );
    } else {
        echo $renderer->glossary_success(
                get_string("glossary:deletesuccess:title", 'local_deepler'),
                get_string("glossary:deletesuccess:body", 'local_deepler', $glossary)
        );
    }
}

// Handle glossary upload status
if (isset($_REQUEST['uploadstatus'])) {
    $status = $_REQUEST['uploadstatus'];
    $message = $_REQUEST['message'] ?? '';

    if ($status !== 'success') {
        $errortitle = $status !== '' ? $status : 'errorupload';
        echo $renderer->glossary_error(
                get_string("glossary:{$status}:title", 'local_deepler'),
                get_string("glossary:{$status}:body", 'local_deepler', $message)
        );
    } else {
        echo $renderer->glossary_success(
                get_string("glossary:uploadsuccess:title", 'local_deepler'),
                get_string("glossary:uploadsuccess:body", 'local_deepler', $message)
        );
    }
}
// Glossary table
echo $renderer->glossary_table($glossaries);
echo $renderer->glossary_uploader();

// Add js.
$PAGE->requires->js_call_amd('local_deepler/glossary', 'init', []);
echo $OUTPUT->footer();
