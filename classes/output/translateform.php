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
global $CFG;

use core_filters\text_filter;
use local_deepler\local\data\field;
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
    protected lang_helper $langpack;

    /** @var string */
    protected string $editor;
    /**
     * Define Moodle Form.
     *
     * @return void
     * @throws \coding_exception
     */
    /**
     * @var string[]
     */
    protected array $langcodes;
    /**
     * @var course
     */
    protected mixed $coursedata;
    /**
     * @var text_filter|Multilang2TextFilter
     */
    protected text_filter|Multilang2TextFilter $mlangfilter;

    /**
     * Main data definition function.
     *
     * @return void
     */
    protected function definition(): void {
        global $CFG;
        $this->editor = $this->_customdata['editor'];
        $this->langpack = $this->_customdata['langpack'];
        // Get course data.
        $this->coursedata = $this->_customdata['coursedata'];
        // Get mlangfilter to filter text.
        $this->mlangfilter = $this->_customdata['mlangfilter'];
        field::$targetlangdeepl = $this->langpack->targetlang;
        // Start moodle form.
        $this->_form->disable_form_change_checker();
        MoodleQuickForm::registerElementType('cteditor', "$CFG->libdir/form/editor.php",
                '\local_deepler\editor\MoodleQuickForm_cteditor');
        // Open Form local_deepler__form.
        $this->_form->addElement('html', '<div class="container-fluid local_deepler__form">');
        // Create course settings section only if no section is selected.
        if ($this->coursedata->get_loadedsection() < 0) {
            $this->makecoursesetting(
                    get_string('settings'),
                    $this->coursedata->getlink(),
                    $this->coursedata->getfields());
        }
        // Create sections.
        $this->makesections($this->coursedata->getsections());
        // Close form.
        $this->_form->addElement('html', '</div>');

    }

    /**
     * Course first section (Course settings block).
     *
     * @param string $title Header title
     * @param string $link Edit link URL
     * @param array $settingfields Field items to render
     * @return void
     */
    protected function makecoursesetting(string $title, string $link, array $settingfields): void {
        global $PAGE;
        $renderer = $PAGE->get_renderer('local_deepler', 'translate');
        $data = new coursesettings_data($title, $link, $settingfields, $this->langpack, $this->mlangfilter, $this->editor);
        $this->_form->addElement('html', $renderer->makecoursesetting($data));
    }

    /**
     * Create sections.
     *
     * @param array $sections
     * @return void
     * @throws \coding_exception
     */
    protected function makesections(array $sections) {
        foreach ($sections as $section) {
            $this->makesection($section);
        }
    }

    /**
     * Create a section
     *
     * @param \local_deepler\local\data\section $section
     * @return void
     * @throws \coding_exception
     */
    protected function makesection(section $section): void {
        if (!$section->is_empty()) {
            global $PAGE;
            $sectiondata = new section_data($section, $this->langpack,
                    $this->mlangfilter,
                    $this->editor);
            $renderer = $PAGE->get_renderer('local_deepler', 'translate');
            $this->_form->addElement('html', $renderer->makesection($sectiondata));
        }
    }
}
