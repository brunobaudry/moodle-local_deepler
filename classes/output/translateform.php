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

use classes\local\services\lang_helper;
use core_filters\text_filter;
use local_deepler\local\data\field;
use local_deepler\local\data\section;
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
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * TODO MDL-0 should use Mustache templating rather than extending a form as communication is done with JS.
 */
class translateform extends moodleform {

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
     * @var text_filter
     */
    private text_filter $mlangfilter;

    /**
     * Create an edit in place button for each item.
     *
     * @param string $link
     * @param bool $outlined
     * @return string
     * @throws \coding_exception
     */
    function makeeditbutton(string $link, bool $outlined = false): string {
        // Edit button.
        $class = $outlined ? 'btn-outline-info btn btn-sm' : '';
        $editbuttontitle = get_string('editbutton', 'local_deepler');
        $editinplacebutton = "<a class='small p-2 $class'
                        id='local_deepler__sourcelink' href='{$link}' target='_blank'
                            title='$editbuttontitle'>
                            <i class='fa fa-pencil' aria-hidden='true'></i>
                        </a>";
        return $editinplacebutton;
    }
    /**
     * Granular row creation.
     *
     * @param \MoodleQuickForm $mform
     * @param \local_deepler\local\data\field $field
     * @return void
     * @throws \coding_exception
     */
    private function makefieldrow(MoodleQuickForm $mform, field $field) {
        $cssclass = '';
        $key = $field->getkey();
        $keyid = $field->getkeyid();
        $alreadyhasmultilang = $field->has_multilang();
        $hasotherandsourcetag = $field->check_field_has_other_and_sourcetag($this->langpack->currentlang);
        $tneeded = $field->get_status()->istranslationneeded();
        $status = $tneeded ? 'needsupdate' : 'updated';
        // Hacky Special cases where the content is a db key (should never be translated).
        $isdbkey = str_contains($field->get_table(), 'wiki_pages') && $field->get_tablefield() === 'title';
        $rowtitle = $isdbkey ? get_string('translationdisabled', 'local_deepler') : '';
        // Open translation item.
        $mform->addElement('html',
                "<div title='$rowtitle' class='$cssclass row align-items-start py-2' data-row-id='$isdbkey$key'
                    data-status='$status'>");
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
        // Column 1 layout.
        $mform->addElement('html', '<div class="col-1 px-0 local_deepler__selectorbox">');
        $mform->addElement('html', "<small class='local_deepler__activityfield lh-sm'>
            {$field->get_translatedfieldname()}</small><br/>");
        if (!$isdbkey) {
            $mform->addElement('html', $bulletstatus);
            $mform->addElement('html', $checkbox);
        }
        $mform->addElement('html', DIV_CLOSE);
        // Column 2 settings.
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
        $rawsourcetext = base64_encode($this->mlangfilter->filter($item->text) ?? '');
        $trimedtext = trim($field->get_text());
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
        $mform->addElement('html', $sourcetextdiv);
        //$mform->addElement('html', $editinplacebutton);
        if (!$isdbkey) {
            $mform->addElement('html', $mutlilangspantag);
            $mform->addElement('html', $sourceselect);
        }
        $mform->addElement('html', $sourcetextarea);
        if (!$isdbkey) {
            $mform->addElement('html', $multilangtextarea);
        }

        // Closing sourcetext div.
        $mform->addElement('html', DIV_CLOSE);

        if (!$isdbkey) {
            // Column 3 settings.
            // Translation Input div.
            $translatededitor = "<div
            class='col-5 px-0 local_deepler__translation'
            data-action='local_deepler/editor'
            data-key='$key'
            data-table='{$field->get_table()}'
            data-cmid='{$field->get_cmid()}'
            data-id='{$field->get_id()}'
            data-field='{$field->get_tablefield()}'
            data-tid='{$field->get_tid()}'>";
            // No wisiwig editor text fields.
            $fieldformat = $field->get_format();
            $nowisiwig = "<div
                class='format-{$fieldformat} border py-2 px-3'
                contenteditable='true'
                data-format='{$fieldformat}'>" . DIV_CLOSE;
            // Status icon/button.
            $savetogglebtn = "<span class='disabled' data-status='local_deepler/wait'
                role='status' aria-disabled='true'><i class='fa'
                ></i></span>";
            // Status surrounding div.
            $statusdiv = "<div class='col-1 text-center'
            data-key-validator='$key'>$savetogglebtn" . DIV_CLOSE;
            // Column 3 Layout.
            $mform->addElement('html', $translatededitor);
            // Plain text input.
            if ($fieldformat === 0) {
                $mform->addElement('html', $nowisiwig);
            } else {
                $mform->addElement('cteditor', $key);
                $mform->setType($key, PARAM_RAW);
            }
            // Closing $translatededitor.
            $mform->addElement('html', DIV_CLOSE);
            // Adding validator btn.
            $mform->addElement('html', $statusdiv);
        }
        // Close translation item.
        $mform->addElement('html', DIV_CLOSE);

    }
    /**
     * Define Moodle Form.
     *
     * @return void
     */
    public function definition(): void {
        global $CFG;

        // Get course data.
        // $course = $this->_customdata['course'];
        /** @var \local_deepler\local\data\course $coursedata */
        $coursedata = $this->_customdata['coursedata'];
        $this->langpack = $this->_customdata['langpack'];
        // Get mlangfilter to filter text.
        $this->mlangfilter = $this->_customdata['mlangfilter'];
        // Get source options.
        $this->sourceoptions = $this->langpack->preparehtmlotions(true, false);
        // Start moodle form.
        $mform = $this->_form;
        $mform->disable_form_change_checker();
        MoodleQuickForm::registerElementType('cteditor', "$CFG->libdir/form/editor.php",
                '\local_deepler\editor\MoodleQuickForm_cteditor');
        // Open Form local_deepler__form.
        $mform->addElement('html', '<div class="container-fluid local_deepler__form">');
        // Create course settings section.
        $this->makecoursesetting($mform, $coursedata->getfields(), $coursedata->getlink());
        // Create sections.
        $this->makesections($mform, $coursedata->getsections());
        $sectioncount = 1;

        // Close form.
        $mform->addElement('html', DIV_CLOSE);
    }

    /**
     * Course first section
     *
     * @param \MoodleQuickForm $mform
     * @param array $settingfields
     * @param string $link
     * @return void
     * @throws \coding_exception
     */
    function makecoursesetting(MoodleQuickForm $mform, array $settingfields, string $link) {
        // Open section container for the course settings course__settings section-item.
        $mform->addElement('html', "<div class='local_deepler_course__settings section-item'>");
        $this->makesettings($mform, $settingfields, $link, get_string('settings'), 0);
        // Close section container for the course settings course__settings section-item.
        $mform->addElement('html', DIV_CLOSE);
    }

    /**
     * First section of the form for course's settings.
     *
     * @param \MoodleQuickForm $mform
     * @param array $settingfields
     * @param string $link
     * @param string $title
     * @param string $index
     * @return void
     * @throws \coding_exception
     */
    public function makesettings(MoodleQuickForm $mform, array $settingfields, string $link, string $title,
            string $index): void {
        // Course settings title.
        $mform->addElement('html',
                "<span class='row h3 sectionname course-content-item d-flex align-self-stretch align-items-center mb-0 p-2'>
                $title {$this->makeeditbutton($link)}</span>");
        // Open course settings section.
        $mform->addElement('html', "<div id='sectiondata[$index]' class='local_deepler__sectiondata'>");
        /** @var field $field */
        foreach ($settingfields as $field) {
            $this->makefieldrow($mform, $field);
        }
        // Close course settings section.
        $mform->addElement('html', DIV_CLOSE);
    }

    private function makesection(MoodleQuickForm $mform, section $section) {
        $sectionid = $section->getid();
        $isvisible = $section->isvisible();
        $sectionorder = $section->getorder();
        $sectionname = $this->mlangfilter->filter($section->getsectionname());
        $mform->addElement('html', "<div class='local_deepler_course__settings section-item'>");
        // Open section container for the course settings course__settings section-item.
        $this->makesettings($mform, $section->getfields(), $section->getlink(), $sectionname, $sectionorder);
        // Close section container for the course settings course__settings section-item.
        $mform->addElement('html', DIV_CLOSE);

        // Close section container for the course settings course__settings section-item.

    }

    private function makesections(MoodleQuickForm $mform, array $sections) {
        foreach ($sections as $section) {
            $this->makesection($mform, $section);
        }
    }
}
