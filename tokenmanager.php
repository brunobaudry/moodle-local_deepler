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
 * Entry page to manage tokens.
 *
 * @package local_deepler
 * @copyright 2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\notification;
use local_deepler\local\services\utils;

require_once(__DIR__ . '/../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url(new moodle_url('/local/deepler/tokenmanager.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('tokenmanager', 'local_deepler'));
$PAGE->set_heading(get_string('tokenmanager', 'local_deepler'));

// Handle add/delete logic here (move from settings.php!).
if (optional_param('addtoken', false, PARAM_BOOL)) {
    require_sesskey();
    $attribute = required_param('attribute', PARAM_TEXT);
    $valuefilter = required_param('valuefilter', PARAM_TEXT);
    $token = required_param('token', PARAM_TEXT);

    $errors = [];
    if (empty($attribute)) {
        $errors[] = get_string('tokenerror_noattribute', 'local_deepler');
    }
    if (empty(trim($valuefilter))) {
        $errors[] = get_string('tokenerror_nofilter', 'local_deepler');
    }
    if (!preg_match(utils::DEEPL_API_REGEX, $token)) {
        $errors[] = get_string('tokenerror_invaliduuid', 'local_deepler');
    }
    if (empty($errors)) {
        $data = (object) [
                'attribute' => $attribute,
                'valuefilter' => $valuefilter,
                'token' => $token,
        ];
        $DB->insert_record('local_deepler_tokens', $data);
        redirect($PAGE->url);
    } else {
        foreach ($errors as $error) {
            notification::error($error);
        }
    }
}

if ($tokenid = optional_param('deletetoken', 0, PARAM_INT)) {
    require_sesskey();
    $DB->delete_records('local_deepler_tokens', ['id' => $tokenid]);
    redirect($PAGE->url);
}

$PAGE->requires->js_call_amd('local_deepler/formvalidation', 'init');

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('local_deepler', 'tokens');
echo $renderer->render_token_manager();

echo $OUTPUT->footer();
