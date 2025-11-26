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
 * Local Deepler plugin glossaries upload management.
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use DeepL\DeepLException;
use local_deepler\local\data\glossary;
use local_deepler\local\data\user_glossary;
use local_deepler\local\services\lang_helper;
use local_deepler\local\services\spreadsheetglossaryparser;

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/version.php');
require_login();

global $USER;
try {
    $uploadinglossary = required_param('uploadinglossary', PARAM_BOOL);
    $redirect = required_param('redirect', PARAM_ALPHANUM);
} catch (moodle_exception $exception) {
    $uploadinglossary = null;
    $status = 'idmissing';
}
$context = context_user::instance($USER->id);
require_login();
require_capability('local/deepler:edittranslations', $context);

// Load glossary manager.
$langhelper = new lang_helper();
$langhelper->initdeepl($USER, $plugin->release);
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
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $parser = new spreadsheetglossaryparser();
        if (!spreadsheetglossaryparser::is_supported($ext)) {
            $status = 'filetypeunsupported';
            $message = $ext;
            // Handle return early as appropriate!
        } else {
            try {
                // Parse file name conventions.
                $filenameext = explode('.', $filename);
                $namearray = explode('_', reset($filenameext));
                $langpair = explode('-', array_pop($namearray));
                $glossaryname = str_replace(' ', '_', implode('_', $namearray));
                $source = $langpair[0];
                $target = $langpair[1];
                $headerlangs = [];
                $sourceok = $targetok = false;
                if (isset($source) && isset($target)) {
                    // First check of the file convention found lang pair.
                    $sourceok = $langhelper->islangsupported($source);
                    $targetok = $langhelper->islangsupported($target);
                }
                // If not using convention or source target not supported.
                $usehedarelangs = !isset($source) || !isset($target) || !($sourceok && $targetok);
                $csvcontent = $parser->parse_to_csv($tmpfile, $ext, $usehedarelangs, $headerlangs);
                if ($usehedarelangs) {
                    // Get the headers.
                    $source = $headerlangs['source'];
                    $target = $headerlangs['target'];
                    // Set the name to the whole name without extention.
                    $glossaryname = $filenameext[0];
                } else {
                    // Set the name stripping the suffix and without extention.
                    $glossaryname = str_replace(' ', '_', implode('_', $namearray));
                }
                if (isset($source) && isset($target)) {
                    $sourceok = $langhelper->islangsupported($source);
                    $targetok = $langhelper->islangsupported($target);
                    if (!$sourceok) {
                        $status = 'sourcenotsupported';
                        $message = $source;
                    } else if (!$targetok) {
                        $status = 'targetnotsupported';
                        $message = $target;
                    } else {
                        if ($csvcontent === '') {
                            $status = 'fileempty';
                        } else {
                            $glossaryinfo = $langhelper->gettranslator()->createGlossaryFromCsv(
                                $glossaryname,
                                $source,
                                $target,
                                $csvcontent
                            );
                            $gid = glossary::create(new glossary(
                                $glossaryinfo->glossaryId,
                                $glossaryinfo->name,
                                $glossaryinfo->sourceLang,
                                $glossaryinfo->targetLang,
                                $glossaryinfo->entryCount,
                                $langhelper->getdbtokenid()
                            ));

                            if ($gid) {
                                user_glossary::create(new user_glossary(
                                    $USER->id,
                                    $gid
                                ));
                                $status = 'success';
                            } else {
                                $status = 'failed';
                            }
                        }
                    }
                } else {
                    $status = 'langpair:notresolved';
                }
            } catch (DeepLException $e) {
                $status = 'deeplissue';
                $message = $e->getMessage();
            } catch (dml_exception $e) {
                $status = 'databaseerror';
                $message = $e->getMessage();
            } catch (Exception $e) {
                $status = 'unknownerror';
                $message = $e->getMessage();
            }
        }
    }
    unset($uploadinglossary);
}
// Redirect.
redirect(new moodle_url('/local/deepler/glossarymanager' . $redirect . '.php?uploadstatus=' . $status . '&message=' .
    urlencode($message)));
