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
     * Define Moodle Form.
     *
     * @return void
     */
    public function definition(): void {
        global $CFG;

        // Get course data.
        /** @var \local_deepler\local\data\course $coursedata */
        $coursedata = $this->_customdata['coursedata'];
        $this->langpack = $this->_customdata['langpack'];
        // Get mlangfilter to filter text.
        $this->mlangfilter = $this->_customdata['mlangfilter'];
        // Get source options.
        $this->sourceoptions = $this->langpack->preparehtmlsources();
        // Start moodle form.
        $this->_form->disable_form_change_checker();
        MoodleQuickForm::registerElementType('cteditor', "$CFG->libdir/form/editor.php",
                '\local_deepler\editor\MoodleQuickForm_cteditor');
        // Open Form local_deepler__form.
        $this->_form->addElement('html', '<div class="container-fluid local_deepler__form">');
        // Create course settings section.
        $this->makecoursesetting($this->makeheader(get_string('settings'), $coursedata->getlink(), 3), $coursedata->getfields());
        // Create sections.
        $this->makesections($coursedata->getsections());
        // Close form.
        $this->_form->addElement('html', DIV_CLOSE);
    }

    /**
     * Course first section
     *
     * @param string $header
     * @param array $settingfields
     * @return void
     * @throws \coding_exception
     */
    private function makecoursesetting(string $header, array $settingfields): void {
        // Open section container for the course settings course__settings section-item.
        $this->_form->addElement('html', "<div class='section-item'>");
        // Open header div.
        $this->_form->addElement('html', "<div class='course-section-header d-flex'>");
        $this->_form->addElement('html', $header);
        $this->_form->addElement('html', DIV_CLOSE); // Close header div.
        $this->makesettings($settingfields, 0);
        // Close section container for the course settings course__settings section-item.
        $this->_form->addElement('html', DIV_CLOSE);
    }

    /**
     * First section of the form for course's settings.
     *
     * @param array $settingfields
     * @param string $index
     * @return void
     * @throws \coding_exception
     */
    public function makesettings(array $settingfields, string $index): void {
        // Open course settings section.
        $this->_form->addElement('html', "<div id='sectiondata[$index]' class='local_deepler__sectiondata'>");
        /** @var field $field */
        foreach ($settingfields as $field) {
            $this->makefieldrow($field);
        }
        // Close course settings section.
        $this->_form->addElement('html', DIV_CLOSE);
    }

    /**
     * Create a section
     *
     * @param \local_deepler\local\data\section $section
     * @return void
     * @throws \coding_exception
     */
    private function makesection(section $section) {
        $sectionfields = $section->getfields();
        $sectionmodules = $section->getmodules();
        if (!empty($sectionmodules) || !empty($sectionfields)) {
            // Open section container for the course settings course__settings section-item.
            $this->_form->addElement('html', "<div id='local_deepler__section{$section->getid()}'
                        class='section-item {$this->getitemvisibilityclass($section)}'>");
            $this->buildhiddenftomstudent();
            // Open header div.
            $this->_form->addElement('html', "<div class='course-section-header d-flex'>");
            $this->_form->addElement('html',
                    $this->makeheader($this->mlangfilter->filter($section->getsectionname()), $section->getlink(), 3));
            $this->_form->addElement('html', DIV_CLOSE); // Close header div.
            // Section fields.
            $this->makesettings($sectionfields, $section->getorder());
            // Section's modules.
            $this->makemodules($sectionmodules);
            // Close section container for the course settings course__settings section-item.
            $this->_form->addElement('html', DIV_CLOSE);
            // Close section container for the course settings course__settings section-item.
        }
    }

    /**
     * Create sections.
     *
     * @param array $sections
     * @return void
     * @throws \coding_exception
     */
    private function makesections(array $sections) {
        foreach ($sections as $section) {
            $this->makesection($section);
        }
    }

    /**
     * Make modules.
     *
     * @param array $sectionmodules
     * @return void
     */
    private function makemodules(array $sectionmodules): void {
        /** @var \local_deepler\local\data\module $module */
        foreach ($sectionmodules as $module) {
            $this->_form->addElement('html', '<div class="divider"><hr/></div>');
            $this->makemodule($module);
        }
    }

    /**
     * Make single module.
     *
     * @param \local_deepler\local\data\module $module
     * @return void
     * @throws \coding_exception
     */
    private function makemodule(module $module): void {
        $this->_form->addElement('html',
                "<div id='{$module->getpluginname()}'
                    class='activity-item local_deepler__activity py-2 {$this->getitemvisibilityclass($module)}'>");
        $this->buildhiddenftomstudent();
        $icon = $this->makeicon($module, "class='activityicon' data-region='activity-icon'");
        $header = $this->makeheader($this->makeactivitydesc($module), $module->getlink(), 4, $icon);
        $this->_form->addElement('html', $header);
        $fileds = $module->getfields();
        $childs = $module->getchilds();
        // Basic common fields.
        if (!empty($fileds)) {
            /** @var field $field */
            foreach ($fileds as $field) {
                $this->makefieldrow($field);
            }
        }
        // Childs (like book pages or quiz questions).
        if (!empty($childs)) {
            /** @var \local_deepler\local\data\interfaces\translatable_interface $child */
            foreach ($childs as $child) {
                $interfaces = class_implements($child);
                $isiconic = in_array('local_deepler\local\data\interfaces\iconic_interface', $interfaces);
                $iseditable = in_array('local_deepler\local\data\interfaces\editable_interface', $interfaces);
                // Open section container for the course settings course__settings section-item.
                $this->_form->addElement('html', "<div class='section-item'>");
                if ($isiconic && $iseditable) {
                    // Open header div.
                    $this->_form->addElement('html', "<div class='course-section-header d-flex'>");
                    // Add a header for the child.
                    $this->_form->addElement('html',
                            $this->makeheader($this->makeactivitydesc($child), $child->getlink(), 5, $this->makeicon($child)));

                    $this->_form->addElement('html', DIV_CLOSE);
                }
                /** @var field $f */
                foreach ($child->getfields() as $f) {
                    $this->makefieldrow($f);
                }
                // Close section container.
                $this->_form->addElement('html', DIV_CLOSE);
            }
        }
        $this->_form->addElement('html', DIV_CLOSE);
    }

    /**
     * Build icon the Moodle way.
     *
     * @param \local_deepler\local\data\interfaces\iconic_interface $item
     * @param string $imageattributes
     * @return string
     */
    private function makeicon(iconic_interface $item, string $imageattributes = ''): string {
        return "<span class='activity-icon activityiconcontainer smaller {$item->getpurpose()} courseicon align-self-start mr-2'>
                                    <img src='{$item->geticon()}' $imageattributes
                                    alt='icon for {$item->getpluginname()}'/></span>";
    }

    /**
     * Write a header for the section, module or sub.
     *
     * @param string $title
     * @param string $link
     * @param int $level
     * @param string $icon
     * @return string
     * @throws \coding_exception
     */
    private function makeheader(string $title, string $link, int $level, string $icon = ''): string {
        $class = "h$level sectionname course-content-item d-flex align-self-stretch align-items-center mb-0 p-2";
        return "<span id='$title' class='$class'>$icon $title {$this->makeeditbutton($link)}</span>";
    }

    /**
     * Create an edit in place button for each item.
     *
     * @param string $link
     * @return string
     * @throws \coding_exception
     */
    private function makeeditbutton(string $link): string {
        // Edit button.
        $editbuttontitle = get_string('editbutton', 'local_deepler');
        return "<a class='small p-2'
                    id='local_deepler__sourcelink'
                    href='{$link}'
                    target='_blank'
                    title='$editbuttontitle'>
                    <i class='icon fa fa-pen fa-fw' aria-hidden='true'></i>
                    </a>";
    }

    /**
     * Build the header line.
     *
     * @param editable_interface|iconic_interface $item
     * @return string
     */
    public function makeactivitydesc(iconic_interface|editable_interface $item): string {
        return $item->getpluginname() . ': ' . $this->mlangfilter->filter($item->getfields()[0]->get_text());
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
    private function makefieldrow(field $field) {

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

    /**
     * Write the visibility class for the item for js filtering.
     *
     * @param \local_deepler\local\data\interfaces\visibility_interface $item
     * @return string
     */
    public function getitemvisibilityclass(visibility_interface $item): string {
        return 'local_deepler' . ($item->isvisible() ? 'visible' : 'invisible');
    }

    /**
     * Create a hidden from students badge.
     *
     * @return void
     * @throws \coding_exception
     */
    public function buildhiddenftomstudent(): void {
        $hiddenfromstudents = get_string('hiddenfromstudents');
        $this->_form->addElement('html',
                "<small class='badge rounded-pill bg-secondary text-dark'
                data-action='local_deepler__hiddenfromstudents'>
                <i class='fa fa-eye-slash' aria-hidden='true'></i>&nbsp;<small>$hiddenfromstudents</small></small>");
    }
}
