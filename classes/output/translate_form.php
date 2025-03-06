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

use context_system;
use local_deepler\local\data\lang_helper;
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
 * @copyright  2022 Kaleb Heitzman <kaleb@jamfire.io>
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * TODO MDL-0 should use Mustache templating rather than extending a form as communication is done with JS.
 */
class translate_form extends moodleform {

    /**
     * Available langs.
     *
     * @var lang_helper
     */
    private $langpack;
    /**
     * @var string
     */
    private $sourceoptions;

    /**
     * Define Moodle Form.
     *
     * @return void
     */
    public function definition() {
        global $CFG;

        // Get course data.
        $course = $this->_customdata['course'];
        $coursedata = $this->_customdata['coursedata'];
        $this->langpack = $this->_customdata['langpack'];
        $this->sourceoptions = $this->langpack->preparehtmlotions(true, false);
        // Start moodle form.
        $mform = $this->_form;
        $mform->disable_form_change_checker();
        MoodleQuickForm::registerElementType('cteditor', "$CFG->libdir/form/editor.php",
                '\local_deepler\editor\MoodleQuickForm_cteditor');

        // Open Form.
        $mform->addElement('html', '<div class="container-fluid local_deepler__form">');

        // Loop through course data to build form.
        $sectioncount = 1;
        foreach ($coursedata as $i => $section) {
            // Loop section's headers.
            // Get mlangfilter to filter text.
            $mlangfilter = $this->_customdata['mlangfilter'];
            $sectiontext = $mlangfilter->filter($section['section'][0]->text);
            $sectionfield = $section['section'][0]->table . "__" . $section['section'][0]->field;
            // Open section container.
            $mform->addElement('html', "<div class='$sectionfield section-item'>");
            // Section title.
            $mform->addElement('html',
                    "<h3 class='row h4 sectionname course-content-item d-flex align-self-stretch align-items-center mb-0 p-2'>
                    $sectiontext</h3>");
            // Section container.
            $csssectionid = "sectiondata[$i]";
            $mform->addElement('html', "<div id='$csssectionid' class='local_deepler__sectiondata'>");
            // Add sections text fields.
            foreach ($section['section'] as $s) {
                $this->get_formrow($mform, $s, "local_deepler__{$s->hierarchy}");
            }
            $mform->addElement('html', DIV_CLOSE);
            // Loop section's activites.
            $tag = ''; // Temporary store the activity id to build and close the div container.
            foreach ($section['activities'] as $a) {
                $subhierarchy = "local_deepler__{$a->hierarchy}";
                // Identify the activity parent to group activities' text fields.
                $parentactivity = "$a->table[$a->id]";
                $mlangfiltered = $mlangfilter->filter($a->displaytext);
                if ($tag !== $parentactivity) {
                    $activitydivider = '<div class="divider"><hr/></div>';
                    $closeit = $tag === '' ? '' : DIV_CLOSE;// If initial don't add closing div.
                    $mform->addElement('html',
                            "$closeit $activitydivider <div id='$parentactivity' class='activity-item local_deepler__activity
                             $subhierarchy'>");
                    if ($a->iconurl !== null) {
                        $iconclass = $a->purpose ?? '';
                        $parentdivclasses =
                                "activity-icon activityiconcontainer smaller $iconclass courseicon align-self-start mr-2";
                        $activitydesc = ($a->pluginname ?? '') . ': ' . htmlentities($mlangfiltered ?? '');
                        $imageattributes = "class='activityicon' data-region='activity-icon'";
                        // Start icon and title row.
                        $mform->addElement('html', "<div class='row align-items-start py-2 $subhierarchy'>");
                        // Add icon and plugin name plus the tile reminder.
                        $mform->addElement('html',
                                "<div class='col-12 px-0 mt-0 pt-0'>
                                    <span class='$parentdivclasses'><img src='{$a->iconurl}' $imageattributes
                                    alt='icon for {$a->table}'/></span>
                                    <small class='local_deepler__activitydesc'>$activitydesc</small></div>");
                        $mform->addElement('html', DIV_CLOSE);
                    }
                    // Reset the tag.
                    $tag = $parentactivity;
                }

                $this->get_formrow($mform, $a, $subhierarchy);
            }
            // Only add a second closing div if the section had activities.
            $mform->addElement('html', ($tag === '' ? '' : DIV_CLOSE) . DIV_CLOSE);
        }

        // Close form.
        $mform->addElement('html', DIV_CLOSE);
    }

    /**
     * Generate Form Row.
     *
     * @param MoodleQuickForm $mform
     * @param mixed $item
     * @param string $cssclass
     * @return void
     * @throws \coding_exception
     */
    private function get_formrow(MoodleQuickForm $mform, mixed $item, string $cssclass = "") {

        // Get mlangfilter to filter text.
        $mlangfilter = $this->_customdata['mlangfilter'];

        // Build a key for js interaction.
        $key = "$item->table[$item->id][$item->field][$item->cmid]";
        $keyid = "{$item->table}-{$item->id}-{$item->field}-{$item->cmid}";
        // Data status.
        $status = $item->tneeded ? 'needsupdate' : 'updated';
        // Special cases where the content is a db key (should never be translated).
        $isdbkey = strpos($item->table, 'wiki_pages') !== false && $item->field === 'title';
        $rowtitle = $isdbkey ? get_string('translationdisabled', 'local_deepler') : '';
        // Open translation item.
        $mform->addElement('html',
                "<div title='$rowtitle' class='$cssclass row align-items-start py-2' data-row-id='$isdbkey$key'
                    data-status='$status'>");
        // Column 1 settings.
        $sametargetassource = $this->langpack->isrephrase();
        if ($sametargetassource || $this->langpack->targetlang === '') {
            $buttonclass = 'badge-dark';
            $titlestring = get_string('canttranslate', 'local_deepler', $this->langpack->targetlang);
        } else if ($item->tneeded) {
            if (str_contains($item->text, "{mlang " . $this->langpack->targetlang)) {
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
        $mform->addElement('html', "<small class='local_deepler__activityfield lh-sm'>{$item->translatedfieldname}</small><br/>");
        if (!$isdbkey || !$item->id === -1) {
            $mform->addElement('html', $bulletstatus);
            $mform->addElement('html', $checkbox);
        }

        // Add the field names translated.

        $mform->addElement('html', DIV_CLOSE);
        // Column 2 settings.
        // Edit button.
        $editbuttontitle = get_string('editbutton', 'local_deepler');
        $editinplacebutton = $isdbkey ? '<div>&nbsp;</div>' : "<a class='p-2 btn btn-sm btn-outline-info'
                        id='local_deepler__sourcelink' href='{$item->link}' target='_blank'
                            title='$editbuttontitle'>
                            <i class='fa fa-pencil' aria-hidden='true'></i>
                        </a>";
        // Multilanguage tag.
        // Invisible when nothing translated already.
        // Can be as bootstrap info when there is a multilang tag in the source.
        // Will be a danger tag if the content has already an OTHER and the TARGET language tag.
        $hasotherandsourcetag = $this->check_field_has_other_and_sourcetag(trim($item->text));
        $alreadyhasmultilang = $this->has_multilang(trim($item->text));
        $multilangdisabled = $alreadyhasmultilang ? '' : 'disabled';
        $badgeclass = '';
        $titlestring = '';
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
        $rawsourcetext = base64_encode($mlangfilter->filter($item->text) ?? '');
        $trimedtext = trim($item->text);
        $rawunfilterdtext = base64_encode($trimedtext ?? '');
        $mlangfiltered = $mlangfilter->filter($item->displaytext);
        $sourcetextarea = "<div class='collapse show' data-sourcetext-key='$key'
                data-sourcetext-raw='$rawsourcetext' data-filedtext-raw='$rawunfilterdtext' >$mlangfiltered" . DIV_CLOSE;
        // Collapsible multilang textarea.

        $multilangtextarea = "<div class='collapse' id='$keyid'>";
        $multilangtextarea .= "<div data-key='$key'
            data-action='local_deepler/textarea'>$trimedtext" . DIV_CLOSE;
        $multilangtextarea .= DIV_CLOSE;
        // Column 2 layout.
        $mform->addElement('html', $sourcetextdiv);
        $mform->addElement('html', $editinplacebutton);
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
            data-table='{$item->table}'
            data-cmid='{$item->cmid}'
            data-id='{$item->id}'
            data-field='{$item->field}'
            data-tid='{$item->tid}'>";
            // No wisiwig editor text fields.
            $nowisiwig = "<div
                class='format-{$item->format} border py-2 px-3'
                contenteditable='true'
                data-format='{$item->format}'>" . DIV_CLOSE;
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
            if ($item->format === 0) {
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
     * Checks if the multilang tag OTHER and the current/source language is already there to warn the user that the tags will be
     * overridden and deleted.
     *
     * @param string $t
     * @return bool
     */
    private function check_field_has_other_and_sourcetag(string $t): bool {
        return str_contains($t, '{mlang other}') && str_contains($t, "{mlang {$this->langpack->currentlang}");
    }

    /**
     * As the title says.
     *
     * @param string $t
     * @return bool
     */
    private function has_multilang(string $t): bool {
        return str_contains($t, '{mlang}');
    }

    /**
     * Specificy translation Access.
     *
     * @return void
     */
    public function require_access() {
        require_capability('local/deepler:edittranslations', context_system::instance());
    }

}
