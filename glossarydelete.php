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
 * Local Deepler plugin glossaries removal management.
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\notification;
use DeepL\DeepLException;
use local_deepler\local\services\lang_helper;

require_once(__DIR__ . '/../../config.php');
require_login();

global $USER;
$status = 'failed';
try {
    $deletingglossary = required_param('deleteglossary', PARAM_ALPHANUMEXT);
    $redirect = required_param('redirect', PARAM_ALPHANUM);
    $name = required_param('glossaryname', PARAM_ALPHANUM);
    $token = required_param('glossarytoken', PARAM_ALPHANUM);
} catch (moodle_exception $exception) {
    $deletingglossary = null;
    $status = 'idmissing';
    redirect(new moodle_url('/local/deepler/glossarymanager.php'),
            'Could not delete, unset glossary ID', null, notification::NOTIFY_ERROR);
}

$context = context_user::instance($USER->id);
require_login();
require_capability('local/deepler:edittranslations', $context);
// Load glossary manager.
$langhelper = new lang_helper();
$initok = $langhelper->initdeepl($USER);

if ($initok && $deletingglossary) {
    if (!confirm_sesskey()) {
        $status = 'invalidsesskey';
        redirect(new moodle_url('/local/deepler/glossarymanager' . $redirect . '.php'),
                'Session expired or invalid. Please try again.', null, notification::NOTIFY_ERROR);
    }
    try {
        $langhelper->deleteglossary($deletingglossary);
        $status = 'success';
    } catch (DeepLException $e) {
        $status = 'deeplissue';
    }
} else {
    $status = 'deeplissue';
}
// Redirect.
redirect(new moodle_url('/local/deepler/glossarymanager' . $redirect . '.php?deletestatus=' . $status . '&deleteglossary=' .
        $deletingglossary . '&name=' . $name . '&token=' . $token));
