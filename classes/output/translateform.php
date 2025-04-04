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

namespace local_deepler\output;
defined('MOODLE_INTERNAL') || die();
define('DIV_CLOSE', '</div>');
global $CFG;

use core_filters\text_filter;
use local_deepler\local\data\field;
use local_deepler\local\data\interfaces\iconic_interface;
use local_deepler\local\data\interfaces\visibility_interface;
use local_deepler\local\data\module;
use local_deepler\local\data\section;
use local_deepler\local\services\lang_helper;
use moodleform;
use MoodleQuickForm;

// Load the files we're going to need.
require_once("$CFG->libdir/form/editor.php");
require_once("$CFG->dirroot/local/deepler/classes/editor/MoodleQuickForm_cteditor.php");

/**
 * Translate Form Output.
 *
 * Provides output class for /local/deepler/translate.php
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class translateform extends deeplerform {

    /**
     * Available langs.
     *
     * @var lang_helper
     */
    private lang_helper $langpack;
    /**
     * @var string dropdown options for source language.
     */
    private string $sourceoptions;


    /**
     * Define Moodle Form.
     *
     * @return void
     * @throws \coding_exception
     */
    public function definition(): void {
        parent::definition();
        global $CFG;
        $this->langpack = $this->_customdata['langpack'];
        // Get source options.
        $this->sourceoptions = $this->langpack->preparehtmlsources();
        // Start moodle form.
        $this->_form->disable_form_change_checker();
        MoodleQuickForm::registerElementType('cteditor', "$CFG->libdir/form/editor.php",
                '\local_deepler\editor\MoodleQuickForm_cteditor');
        // Open Form local_deepler__form.
        $this->_form->addElement('html', '<div class="container-fluid local_deepler__form">');
        // Create course settings section.
        $this->makecoursesetting(
                $this->makeheader(get_string('settings'), $this->coursedata->getlink(), 3),
                $this->coursedata->getfields());
        // Create sections.
        $this->makesections($this->coursedata->getsections());
        // Close form.
        $this->_form->addElement('html', DIV_CLOSE);
    }

    /**
     * Prepare and display the translation return text editor and the status button.
     *
     * @param string $key
     * @param \local_deepler\local\data\field $field
     * @return void
     */
    private function fieldrowcolumn3(string $key, field $field): void {
        // Column 3 settings.
        $translatededitor = "<div
            class='col-5 px-0 local_deepler__translation'
            data-action='local_deepler/editor'
            data-key='$key'
            data-table='{$field->get_table()}'
            data-cmid='{$field->get_cmid()}'
            data-id='{$field->get_id()}'
            data-field='{$field->get_tablefield()}'
            data-tid='{$field->get_tid()}'>"; // Translation Input div.

        $fieldformat = $field->get_format();
        $nowisiwig = "<div
                class='format-{$fieldformat} border py-2 px-3'
                contenteditable='true'
                data-format='{$fieldformat}'>" . DIV_CLOSE; // No wisiwig editor text fields.

        // Column 3 Layout.
        $this->_form->addElement('html', $translatededitor); // Open $translatededitor.
        if ($fieldformat === 0) { // Plain text input.
            $this->_form->addElement('html', $nowisiwig);
        } else {
            $this->_form->addElement('cteditor', $key);
            $this->_form->setType($key, PARAM_RAW);
        }
        $this->_form->addElement('html', DIV_CLOSE); // Closing $translatededitor.
        // Status button.
        $savetogglebtn = "<span class='disabled' data-status='local_deepler/wait'
                role='status' aria-disabled='true'><i class='fa'
                ></i></span>";  // Status icon/button.
        // Status surrounding div.
        $statusdiv = "<div class='col-1 text-center' data-key-validator='$key'>$savetogglebtn" . DIV_CLOSE;
        $this->_form->addElement('html', $statusdiv);// Adding validator btn.
    }

    /**
     * Build the middle column of the translation form that includes source text and source lang selector.
     *
     * @param \local_deepler\local\data\field $field
     * @param string $keyid
     * @param string $key
     * @param bool $isdbkey
     * @return void
     * @throws \coding_exception
     */
    public function fieldrowcolumn2(field $field, string $keyid, string $key, bool $isdbkey): void {
        $hasotherandsourcetag = $field->check_field_has_other_and_sourcetag($this->langpack->currentlang);
        $alreadyhasmultilang = $field->has_multilang();
        $multilangdisabled = $alreadyhasmultilang ? '' : 'disabled';
        if ($alreadyhasmultilang) {
            if ($hasotherandsourcetag) {
                $badgeclass = 'danger';
                $titlestring = get_string('warningsource', 'local_deepler',
                        strtoupper($this->langpack->currentlang));
            } else {
                $titlestring = get_string('viewsource', 'local_deepler');
                $badgeclass = 'info';
            }
        } else {
            $titlestring = get_string('viewsourcedisabled', 'local_deepler');
            $badgeclass = 'secondary';
        }
        $mutlilangspantag =
                "<span
                    title='$titlestring'
                    id='toggleMultilang'
                    aria-controls='$keyid'
                    aria-pressed='false'
                    data-toggle='button'
                    role='button'
                    class='mx-1 p-2 btn btn-sm btn-outline-$badgeclass $multilangdisabled'
                    >
                    <i class='fa fa-language' aria-hidden='true'></i></span>";
        // Source lang select.

        $selecttitle = get_string('specialsourcetext', 'local_deepler', strtoupper($this->langpack->currentlang));
        $sourceselect =
                "<select class='custom-select' title='$selecttitle' data-key='$key' data-action='local_deepler/sourceselect'>
                    {$this->sourceoptions}</select>";
        // Source Text.
        $sourcetextdiv = "<div class='col-5 px-0 pr-5 local_deepler__source-text' data-key='$key'>";
        // Source texts.
        $fieldtext = $field->get_text();
        $rawsourcetext = base64_encode($this->mlangfilter->filter($fieldtext) ?? '');
        $trimedtext = trim($fieldtext);
        $rawunfilterdtext = base64_encode($trimedtext);
        $mlangfiltered = $this->mlangfilter->filter($field->get_displaytext());
        $sourcetextarea = "<div class='collapse show' data-sourcetext-key='$key'
                data-sourcetext-raw='$rawsourcetext' data-filedtext-raw='$rawunfilterdtext' >$mlangfiltered" . DIV_CLOSE;
        // Collapsible multilang textarea.
        $multilangtextarea = "<div class='collapse' id='$keyid'>";
        $multilangtextarea .= "<div data-key='$key'
            data-action='local_deepler/textarea'>$trimedtext" . DIV_CLOSE;
        $multilangtextarea .= DIV_CLOSE;
        // Column 2 layout.
        $this->_form->addElement('html', $sourcetextdiv);
        if (!$isdbkey) {
            $this->_form->addElement('html', $mutlilangspantag);
            $this->_form->addElement('html', $sourceselect);
        }
        $this->_form->addElement('html', $sourcetextarea);
        if (!$isdbkey) {
            $this->_form->addElement('html', $multilangtextarea);
        }

        // Closing sourcetext div.
        $this->_form->addElement('html', DIV_CLOSE);
    }

    /**
     * Build the first column of the translation form that includes the checkbox and the status badge.
     *
     * @param \local_deepler\local\data\field $field
     * @param string $key
     * @param bool $isdbkey
     * @return void
     * @throws \coding_exception
     */
    public function fieldrowcolumn1(field $field, string $key, bool $isdbkey): void {
        $cssclass = '';
        $tneeded = $field->get_status()->istranslationneeded();
        $status = $tneeded ? 'needsupdate' : 'updated';
        $rowtitle = $isdbkey ? get_string('translationdisabled', 'local_deepler') : '';

        $sametargetassource = $this->langpack->isrephrase();
        if ($sametargetassource || $this->langpack->targetlang === '') {
            $buttonclass = 'badge-dark';
            $titlestring = get_string('canttranslate', 'local_deepler', $this->langpack->targetlang);
        } else if ($tneeded) {
            if (str_contains($field->get_text(), "{mlang " . $this->langpack->targetlang)) {
                $buttonclass = 'badge-warning';
                $titlestring = get_string('needsupdate', 'local_deepler');
            } else {
                $buttonclass = 'badge-danger';
                $titlestring = get_string('nevertranslated', 'local_deepler', $this->langpack->targetlang);
            }

        } else {
            $buttonclass = 'badge-success';
            $titlestring = get_string('uptodate', 'local_deepler');
        }
        $titlestring = htmlentities($titlestring, ENT_HTML5);
        // Thew little badge showing the status of the translations.
        $bulletstatus = '<span id="previousTranslationStatus" title="' . $titlestring .
                '" class="badge badge-pill ' . $buttonclass . '">&nbsp;</span>';
        // The checkbox to select items for batch actions.
        $checkbox = '<input title="' . $titlestring . '"' . " type='checkbox' data-key='$key'
            class='mx-2'
            data-action='local_deepler/checkbox'
            disabled/>";
        // Open translation item.
        $this->_form->addElement('html',
                "<div title='$rowtitle' class='$cssclass d-flex align-items-start py-2' data-row-id='$isdbkey$key'
                    data-status='$status'>");
        // Column 1 layout.
        $this->_form->addElement('html', '<div class="col-1 px-0 local_deepler__selectorbox">');
        $this->_form->addElement('html', "<small class='local_deepler__activityfield lh-sm'>
            {$field->get_translatedfieldname()}</small><br/>");
        if (!$isdbkey) {
            $this->_form->addElement('html', $bulletstatus);
            $this->_form->addElement('html', $checkbox);
        }
        $this->_form->addElement('html', DIV_CLOSE);
    }

    /**
     * Granular row creation.
     *
     * @param \local_deepler\local\data\field $field
     * @return void
     * @throws \coding_exception
     */
    protected function makefieldrow(field $field) {

        $key = $field->getkey();
        $keyid = $field->getkeyid();

        // Hacky Special cases where the content is a db key (should never be translated).
        $isdbkey = str_contains($field->get_table(), 'wiki_pages') && $field->get_tablefield() === 'title';
        $this->fieldrowcolumn1($field, $key, $isdbkey);

        // Column 2 settings.
        $this->fieldrowcolumn2($field, $keyid, $key, $isdbkey);

        if (!$isdbkey) {
            $this->fieldrowcolumn3($key, $field);
        }
        // Close translation item.
        $this->_form->addElement('html', DIV_CLOSE);

    }

}
