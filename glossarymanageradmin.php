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
 * @copyright  2025 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use DeepL\DeepLClient;
use local_deepler\local\services\lang_helper;
use local_deepler\local\data\glossary;

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/version.php');
require_login();

global $USER, $PAGE, $OUTPUT;

$context = context_user::instance($USER->id);
require_login();
require_capability('local/deepler:edittranslations', $context);

$PAGE->set_context($context);
/** @var local_deepler\output\glossary_renderer $renderer */
$renderer = $PAGE->get_renderer('local_deepler', 'glossary');
$PAGE->set_url(new moodle_url('/local/deepler/glossarymanager.php'));
$title = get_string('glossary:manage:title', 'local_deepler');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('base');

echo $OUTPUT->header();
// Load glossary manager.
$apikey = get_config('local_deepler', 'apikey');
if ($apikey) {
    $langhelper = new lang_helper(new DeepLClient($apikey), $apikey);
    $langhelper->initdeepl($USER, $plugin->release);
    // Prepare content.

    $pluginsglossaries = $langhelper->syncdeeplglossaries();

    // Handle glossary deletion status.
    if (isset($_REQUEST['deletestatus'])) {
        // Statuses are: deeplissue, failed, idmissing, invalidsesskey, success.
        $status = $_REQUEST['deletestatus'];
        $glossary = $_REQUEST['name'] . ' (' . $_REQUEST['token'] . ')';
        echo $renderer->handle_glossary_status('delete', $status, $glossary);
    }

    // Handle glossary upload status.
    if (isset($_REQUEST['uploadstatus'])) {
        // Statuses are: deeplissue, failed, fileerror, invalidsesskey, success, suffixerror, unknownerror.
        $status = $_REQUEST['uploadstatus'];
        $message = $_REQUEST['message'] ?? '';
        echo $renderer->handle_glossary_status('upload', $status, $message);
    }
    // Glossary table.
    echo $renderer->glossary_uploader('admin');
    echo $renderer->glossaries_table_admin($pluginsglossaries);
} else {
    echo $renderer->glossary_warning(get_string('error'), get_string('missingmainapikey', 'local_deepler'));
}

// Add js.
$PAGE->requires->js_call_amd('local_deepler/glossary', 'init', ['version' => $plugin->release]);
echo $OUTPUT->footer();
