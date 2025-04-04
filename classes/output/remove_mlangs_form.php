<?php

namespace local_mlangremover\output;
define('DIV_CLOSE', '</div>');

use local_mlangremover\local\data\multilangfield;
use local_mlangremover\local\data\textfield;

class remove_mlangs_form extends \moodleform {
    /** @var \filter_multilang2 $mlangfilter */
    private $mlangfilter;

    /**
     * @inheritDoc
     */
    protected function definition() {

        $this->mlangfilter = $this->_customdata['mlangfilter'];
        // All fileds
        $coursedata = $this->_customdata['coursedata'];
        // Start moodle form.
        $mform = $this->_form;
        $mform->disable_form_change_checker();
        // Open Form.
        $mform->addElement('html', '<div class="container-fluid local_deepler__form">');
        // Loop through course data to build form.
        $sectioncount = 1;
        /**
         * @var int $i
         * @var multilangfield $data
         */
        foreach ($coursedata as $i => $data) {
            /** @var array<multilangfield> $sections */
            $sections = $data['sections'];
            $activities = $data['activities'];
            $sectionclass = $sections[0]->get_textfield()->get_table() . "__" . $sections[0]->get_textfield()->get_field();
            // Open main section tag.
            $mform->addElement('html', "<div class='$sectionclass '>");
            // Main Section title including mlang tags.
            $alllangtitle = $sections[0]->get_textfield()->get_text();
            // Filtered main section title.
            $currentlangtitle = $this->mlangfilter->filter($alllangtitle);
            $sectiontitlelangs = implode(' ', $sections[0]->get_languages());
            $mform->addElement('html',
                    "<h3 class='row h4 sectionname course-content-item d-flex align-self-stretch align-items-center mb-0 p-2'>
                     $currentlangtitle&nbsp;<em>$sectiontitlelangs</em></h3>");
            // Open Section container.
            $mform->addElement('html', "<div id='sectiondata[$i]' class='local_mlangremover__sectiondata'>");
            // Add sections text fields.
            foreach ($sections as $s) {
                $this->get_formrow($mform, $s);
                $mform->addElement('html', "<hr/>");
            }
            // Close section field container
            $mform->addElement('html', DIV_CLOSE);
            // Loop section's activites.
            $tag = ''; // Temporary store the activity id to build and close the div container.
            /** @var multilangfield $a */
            foreach ($activities as $a) {
                $mform->addElement('html', "<hr/>");
                // Identify the activity parent to group activities' text fields.
                $parentactivity = "{$a->get_textfield()->get_table()}[{$a->get_textfield()->get_id()}]";
                $mlangfiltered = $this->mlangfilter->filter($a->get_textfield()->get_text());
                if ($tag !== $parentactivity) {
                    $closeit = $tag === '' ? '' : DIV_CLOSE;// If initial don't add closing div.
                    $mform->addElement('html',
                            "$closeit<div id='$parentactivity' class='activity-item local_mlangremover__activity'>");
                    // Reset the tag.
                    $tag = $parentactivity;
                }
                $this->get_formrow($mform, $a);
            }

            // Close main section tag. Only add a second closing div if the section had activities.
            $mform->addElement('html', ($tag === '' ? '' : DIV_CLOSE) . DIV_CLOSE);
            //$sectiontext = $section->get_textfield()->get_text();
            //$sectionfield = $section['section'][0]->table . "__" . $section['section'][0]->field;
            // Open section container.
            //$mform->addElement('html', "<div class='$sectionfield'>");
        }
        // Close form.
        $mform->addElement('html', DIV_CLOSE);
    }

    private function get_formrow(\MoodleQuickForm $mform, multilangfield $item) {
        /** @var textfield $field */
        $field = $item->get_textfield();
        $fieldtext = $field->get_text();
        $fieldtextfiltered = $this->mlangfilter->filter($fieldtext);
        $languages = implode(' ', $item->get_languages());
        // Build a key for js interaction.
        $key = "{$field->get_table()}[{$field->get_id()}][{$field->get_field()}][{$field->get_cmid()}]";
        $keyid = "{$field->get_table()}-{$field->get_id()}-{$field->get_field()}-{$field->get_cmid()}";
        // Open translation item.
        $mform->addElement('html',
                "<div class='row align-items-start py-2' data-row-id='$key'>");
        // The checkbox to select items for batch actions.
        $disablecheckbox = count($item->get_languages()) > 0 ? '' : 'disabled';
        $checkbox =
                "<input type='checkbox' data-key='$key' class='mx-2' data-action='local_mlangremover/checkbox' $disablecheckbox/>";
        // Column 1 layout.
        $mform->addElement('html', '<div class="col-12 px-1">');
        // Add the checkbox.
        $mform->addElement('html', $checkbox);
        $mform->addElement('html', "<em style='background-color: yellow'>$languages</em>");
        // Close columns 1
        $mform->addElement('html', DIV_CLOSE);
        // Column 2 text.
        //$textdiv = "<div class='col-11 px-0 pr-5 local_mlangremover__source-text' data-key='$key'>";
        $encodedtext = base64_encode($fieldtext);
        $textarea =
                "<div class='col-6 p-1 local_mlangremover__source' data-sourcetext-key='$key' data-sourcetext-raw='$encodedtext' >$fieldtext&nbsp;" .
                DIV_CLOSE;

        $result = "<div class='col-6 p-1 local_mlangremover__result' data-result-key='$key'></div>";

        // Column 2 layout.
        $mform->addElement('html', $textarea . $result);
        // Close translation item.
        $mform->addElement('html', DIV_CLOSE);
    }
}
