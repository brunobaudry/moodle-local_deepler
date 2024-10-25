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

use local_deepler\local\data\lang_helper;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Translate Page Output.
 *
 * Provides output class for /local/deepler/translate.php
 *
 * @package    local_deepler
 * @copyright  2022 Kaleb Heitzman <kaleb@jamfire.io>
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class translate_page implements renderable, templatable {
    /**
     * The course in translation.
     *
     * @var object
     */
    private object $course;
    /**
     * The data of the course parsed from mod_info.
     *
     * @var array
     */
    private array $coursedata;
    /**
     * The current multilang filter object.
     *
     * @var \filter_multilang2
     */
    private object $mlangfilter;
    /**
     * @var array|mixed
     */
    private mixed $langpacks;
    /**
     * The form to display the row UI.
     *
     * @var translate_form
     * TODO MDL-0 change this to mustache.
     */
    private translate_form $mform;

    /**
     * Class Construct.
     *
     * @param \stdClass $course
     * @param array $coursedata
     * @param \filter_multilang2 $mlangfilter
     * @param lang_helper $languagepack
     * @throws \moodle_exception
     */
    public function __construct(\stdClass $course, array $coursedata, \filter_multilang2 $mlangfilter, lang_helper $languagepack) {
        $this->course = $course;
        $this->coursedata = $coursedata;
        $this->langpacks = $languagepack;
        $this->mlangfilter = $mlangfilter;
        // Moodle Form.
        $mform = new translate_form(null, ['course' => $course, 'coursedata' => $coursedata, 'mlangfilter' => $mlangfilter,
                'langpack' => $languagepack,
        ]);
        $this->mform = $mform;
    }

    /**
     * Export Data to Template.
     *
     * @param renderer_base $output
     * @return object
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        // Data for mustache template.
        $data->course = $this->course;
        $data->target_langs = $this->langpacks->prepareoptionlangs(false, true);
        $data->langs = $this->langpacks->prepareoptionlangs(true, true);

        // Hacky fix but the only way to adjust html...
        // This could be overridden in css and I might look at that fix for the future.
        $renderedform = $this->mform->render();
        $renderedform = str_replace('col-md-9', 'col-md-12', $renderedform);
        $data->mform = $renderedform;

        // Set langs.
        $data->current_lang = mb_strtoupper($this->langpacks->currentlang);
        $data->target_lang = mb_strtoupper($this->langpacks->targetlang);
        $data->mlangfilter = $this->mlangfilter;
        // Pass data.
        $data->course = $this->course;
        $data->coursedata = $this->coursedata;
        return $data;
    }
}
