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

use local_deepler\local\data\glossary;
use local_deepler\local\data\user_glossary;
use local_deepler\local\services\lang_helper;
require_once(__DIR__ . '/../../config.php');
require_login();

global $USER;
try {
    $uploadinglossary = required_param('uploadinglossary', PARAM_BOOL);
} catch (moodle_exception $exception) {
    $uploadinglossary = null;
    $status = 'idmissing';
}
$context = context_user::instance($USER->id);
require_login();
require_capability('local/deepler:edittranslations', $context);

// Load glossary manager.
$langhelper = new lang_helper();
$langhelper->initdeepl($USER);
$status = 'failed';
$message = '';
if ($uploadinglossary) {
    if (!confirm_sesskey()) {
        $status = 'invalidsesskey';
        throw new moodle_exception('invalidsesskey');
    }
    if (!isset($_FILES['glossaryfile']) || $_FILES['glossaryfile']['error'] !== UPLOAD_ERR_OK) {
        $status = 'fileerror';
    } else {
        // Proceed with glossary management.
        $tmpfile = $_FILES['glossaryfile']['tmp_name'];
        $filename = $_FILES['glossaryfile']['name'];
        $file = file_get_contents($tmpfile);
        try {
            // Parse file name conventions.
            $filenameext = explode('.', $filename);
            $ext = strtolower(end($filenameext));
            $namearray = explode('_', reset($filenameext));
            $langpair = explode('-', array_pop($namearray));
            $glossaryname = str_replace(' ', '_', implode('_', $namearray));
            $source = $langpair[0];
            $target = $langpair[1];
            if (isset($source) && isset($target) && $langhelper->islangsupported($source) && $langhelper->islangsupported
                    ($target)) {
                $glossaryinfo = $langhelper->gettranslator()->createGlossaryFromCsv($glossaryname, $source, $target, $file);
                $gid = glossary::create(new glossary(
                        $glossaryinfo->glossaryId,
                        $glossaryinfo->name,
                        $glossaryinfo->sourceLang,
                        $glossaryinfo->targetLang,
                        $glossaryinfo->entryCount,
                        $langhelper->getdbtokenid()
                ));
                $guid = user_glossary::create(new user_glossary($USER->id, $gid));
                $status = 'success';
                $message = $filename;
            } else {
                $status = 'suffixerror';
            }
        }
        catch (\DeepL\DeepLException $e) {
            $status = 'deeplissue';
            $message = $e->getMessage();
        }
        catch (Exception $e) {
            $status = 'unknownerror';
            $message = $e->getMessage();
        }
    }
    unset($uploadinglossary);
}
// Redirect.
redirect(new moodle_url('/local/deepler/glossarymanager.php?uploadstatus=' . $status.'&message='.urlencode($message)));
