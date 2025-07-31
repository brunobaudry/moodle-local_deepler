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
 * Local Deepler plugin glossaries management settings in Admin.
 *
 * @package    local_deepler
 * @copyright  2022 Kaleb Heitzman
 * @copyright  2024 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use DeepL\DeepLClient;
use local_deepler\local\services\lang_helper;
use local_deepler\local\data\glossary;

require_once(__DIR__ . '/../../config.php');
require_login();

global $USER, $PAGE, $OUTPUT;

$context = context_user::instance($USER->id);
require_login();
require_capability('local/deepler:edittranslations', $context);

$PAGE->set_context($context);
// Load glossary manager.
$apikey = get_config('local_deepler', 'apikey');
$langhelper = new lang_helper(new DeepLClient($apikey), $apikey);
//$langhelper->initdeepl($USER);
$renderer = $PAGE->get_renderer('local_deepler', 'glossary');
$PAGE->set_url(new moodle_url('/local/deepler/glossarymanager.php'));
$title = get_string('glossary:manage:title', 'local_deepler');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('base');

echo $OUTPUT->header();
// Prepare content.
$deeplglossaries = $langhelper->getalldeeplglossaries();
foreach ($deeplglossaries as $deeplglossary) {
    try {
        if (!glossary::exists($deeplglossary->glossaryId)) {
            glossary::create(new glossary(
                    $deeplglossary->glossaryId,
                    $deeplglossary->name,
                    $deeplglossary->sourceLang,
                    $deeplglossary->targetLang,
                    $deeplglossary->entryCount
            ));
        }
    } catch (dml_exception $e) {

    }
}
$pluginsglossaries = glossary::getall(null, null);
// $glossaries = $langhelper->getusersglossaries();
/**
 * @param $type
 * @param $status
 * @param $data
 * @param $renderer
 * @return void
 * @throws \coding_exception
 */
function handlestatus($type, $status, $data, $renderer): void {
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
    handlestatus('delete', $status, $glossary, $renderer);
}

// Handle glossary upload status.
if (isset($_REQUEST['uploadstatus'])) {
    // Statuses are: deeplissue, failed, fileerror, invalidsesskey, success, suffixerror, unknownerror.
    $status = $_REQUEST['uploadstatus'];
    $message = $_REQUEST['message'] ?? '';
    handlestatus('upload', $status, $message, $renderer);
}
// Glossary table
echo $renderer->glossary_uploader();
echo $renderer->glossaries_table_admin($pluginsglossaries);

// Add js.
$PAGE->requires->js_call_amd('local_deepler/glossary', 'init', []);
echo $OUTPUT->footer();
