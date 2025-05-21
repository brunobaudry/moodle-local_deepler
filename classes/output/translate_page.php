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

use local_deepler\local\data\course;
use local_deepler\local\services\lang_helper;
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
     * @var string
     */
    private string $version;
    /**
     * The data of the course parsed from mod_info.
     *
     * @var \local_deepler\local\data\course
     */
    private course $coursedata;
    /**
     * The current multilang filter object.
     *
     * @var mixed
     */
    private mixed $mlangfilter;
    /**
     * @var array|mixed
     */
    private mixed $langpacks;
    /**
     * The form to display the row UI.
     *
     * @var translateform
     */
    private translateform $mform;
    /**
     * @var \renderer_base
     */
    private renderer_base $output;

    /**
     * Class Construct.
     *
     * @param \local_deepler\local\data\course $coursedata
     * @param mixed $mlangfilter
     * @param lang_helper $languagepack
     * @param string $version
     */
    public function __construct(course $coursedata, mixed $mlangfilter, lang_helper $languagepack,
            string $version) {
        $this->version = $version;
        $this->coursedata = $coursedata;
        $this->langpacks = $languagepack;
        $this->mlangfilter = $mlangfilter;
        // Moodle Form.
        $mform = new translateform(null, ['coursedata' => $coursedata, 'mlangfilter' => $mlangfilter,
                'langpack' => $languagepack,
        ]);
        $this->mform = $mform;
    }

    /**
     * Export Data to Template.
     *
     * @param renderer_base $output
     * @return object
     * @throws \DeepL\DeepLException
     */
    public function export_for_template(renderer_base $output) {
        $this->output = $output;
        $data = new stdClass();
        // Data for mustache template.
        $data->langstrings = $this->langpacks->preparestrings();
        $data->targethtmloptions = $this->langpacks->preparehtmltagets();
        $data->targetlangs = $this->langpacks->preparetargetsoptionlangs();
        $data->sourcelangs = $this->langpacks->preparesourcesoptionlangs();

        // Hacky fix but the only way to adjust html...
        // This could be overridden in css and I might look at that fix for the future.
        $renderedform = $this->mform->render();
        $renderedform = str_replace('col-md-9', 'col-md-12', $renderedform);
        $data->mform = $renderedform;
        $data->codes = $this->mform->get_langcodes();
        // Set langs.
        $data->current_lang = $this->langpacks->currentlang;
        $data->deeplsource = $this->langpacks->get_deeplsourcelang();
        $data->target_lang = $this->langpacks->targetlang === '' ? '?' : $this->langpacks->targetlang;
        $data->notarget = $this->langpacks->targetlang === '';
        $data->mlangfilter = $this->mlangfilter;
        $data->escapelatexbydefault = get_config('local_deepler', 'latexescapeadmin') ? 'checked' : '';
        $data->escapeprebydefault = get_config('local_deepler', 'preescapeadmin') ? 'checked' : '';
        $data->hideiframesdefault = get_config('local_deepler', 'hideiframesadmin') ? 'checked' : '';
        $data->canimprove = $this->langpacks->get_canimprove();
        $data->supportedlangs = implode(', ', $this->langpacks->get_deeplrephraselangs());
        $data->rephrasesymbol = lang_helper::REPHRASESYMBOL;
        $data->hidecompatible = count($this->langpacks->findcompatiblelangs()) < 2;
        $data->compatiblelangs = array_map('strval', $this->langpacks->findcompatiblelangs());
        $data->showhiddenforstudents = get_string('showhiddenforstudents', 'local_deepler', get_string('hiddenfromstudents'));
        // Pass data.
        $data->version = $this->version;
        return $data;
    }
}
