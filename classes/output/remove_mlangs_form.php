<?php

namespace local_deepler\output;

define('DIV_CLOSE', '</div>');

use local_deepler\local\data\course;
use local_deepler\local\data\field;
use local_deepler\local\services\multilanger;
use local_deepler\local\services\utils;
use local_mlangremover\local\data\textfield;
use MoodleQuickForm;

/**
 *
 */
class remove_mlangs_form extends deeplerform {

    /**
     * @var array|string[]
     */
    private array $colors;

    /**
     * Inject the current mlangs into the form.
     * Returns the number of languages found.
     *
     * @param array $langs
     * @return string
     */
    public function makemlangs(array $langs): string {
        $i = 0;
        $s = '';
        foreach ($langs as $l => $lang) {
            $color = $this->colors[$i];
            $i++;
            $s .= "<div class='mlangremover__lang local_deepler__color_$l'>$lang</div>";
        }
        return $s;
    }

    /**
     * Inject the language codes into the form.
     * Returns the number of languages found.
     *
     * @param array $codes
     * @return string
     */
    public function makecodes(array $codes): string {
        $s = '';
        foreach ($codes as $k) {
            $s .= "<span class='col-1  local_deepler__color_$k'><input type='checkbox' class='mlangremover__lang' 
                data-action='local_deepler/checkboxlangcode'>$k</input></span>";
        }
        return $s;
    }

    /**
     * @inheritDoc
     */
    protected function definition(): void {
        parent::definition();
        $this->colors = utils::makecolorindex($this->langcodes);
        $this->mlangfilter = $this->_customdata['mlangfilter'];
        /** @var \local_deepler\local\data\course $coursedata */
        $coursedata = $this->_customdata['coursedata'];
        // Start moodle form.
        $this->_form->disable_form_change_checker();
        // Open Form.
        $this->_form->addElement('html', '<div class="container-fluid local_deepler__form">');
        // Loop through course data to build form.
        $coursefields = $coursedata->getfields();
        // Add the course fields.
        $this->makecoursesetting($this->makeheader(get_string('settings'), $coursedata->getlink(), 3),
                $coursefields);
        // Create sections.
        $this->makesections($this->coursedata->getsections());
        // Close form.
        $this->_form->addElement('html', DIV_CLOSE);
    }

    /**
     * Granular row creation.
     *
     * @param \local_deepler\local\data\field $field
     * @return void
     */
    protected function makefieldrow(field $field) {
        $multillanger = new multilanger($field);
        $this->gatherlangcodes($multillanger->findmlangcodes());
        $langs = $multillanger->findmlangs();
        $countlangs = count($langs);
        $key = $field->getkey();
        if ($countlangs === 0) {
            return;
        }
        // The checkbox to select items for batch actions.
        $checkbox = "<input type='checkbox' data-key='$key'
            class='mx-2'
            data-action='local_deepler/checkbox'
            disabled/>";
        $this->_form->addElement('html', '<div>');
        $this->_form->addElement('html',
                "<div class='row sectionname mb-0 p-2'>");
        $translatedfieldname = multilanger::findfieldstring($field);
        $col1 = "<small class='local_deepler__activityfield lh-sm'>$translatedfieldname</small>";
        $this->makecodes(array_keys($langs));

        $col2 = $this->makecodes(array_keys($langs));
        $this->_form->addElement('html', $checkbox);
        $this->_form->addElement('html', $col1 . $col2);
        $this->_form->addElement('html', '</div>');
        $this->_form->addElement('html', $this->makemlangs($langs));

        $this->_form->addElement('html', DIV_CLOSE);
    }
    /**
     * @param \local_deepler\local\data\course $coursedata
     * @param \MoodleQuickForm $mform
     * @return void
     */
    public function old(course $coursedata, MoodleQuickForm $mform): void {
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

    /**
     * @inheritDoc
     */

    private function get_formrow(MoodleQuickForm $mform, multilangfield $item) {
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
