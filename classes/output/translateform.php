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

use local_deepler\local\data\field;
use local_deepler\local\data\multilanger;
use local_deepler\local\services\lang_helper;
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
    protected lang_helper $langpack;

    /** @var string */
    protected string $editor;
    /**
     * Define Moodle Form.
     *
     * @return void
     * @throws \coding_exception
     */
    public function definition(): void {
        parent::definition();
        global $CFG;
        $this->editor = $this->_customdata['editor'];
        $this->langpack = $this->_customdata['langpack'];
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
        $this->_form->addElement('html', DIV_CLOSE);
    }

}
