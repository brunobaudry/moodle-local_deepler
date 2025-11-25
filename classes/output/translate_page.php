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

use core_courseformat\base;
use core_filters\text_filter;
use local_deepler\local\data\course;
use local_deepler\local\data\section;
use local_deepler\local\services\lang_helper;
use renderable;
use renderer_base;
use stdClass;
use templatable;
if (class_exists('\\core_filters\\text_filter')) {
    class_alias('\\core_filters\\text_filter', 'local_deepler\\output\\Multilang2TextFilter');
} else {
    class_alias('\\moodle_text_filter', 'local_deepler\\output\\Multilang2TextFilter');
}
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
     * @var text_filter|Multilang2TextFilter
     */
    protected text_filter|Multilang2TextFilter $mlangfilter;

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
    /** @var string */
    private string $editor;

    /**
     * Class Construct.
     *
     * @param \local_deepler\local\data\course $coursedata
     * @param mixed $mlangfilter
     * @param lang_helper $languagepack
     * @param string $version
     * @param string $editor
     */
    public function __construct(course $coursedata, mixed $mlangfilter, lang_helper $languagepack, string $version,
            string $editor) {
        $this->version = $version;
        $this->coursedata = $coursedata;
        $this->langpacks = $languagepack;
        $this->mlangfilter = $mlangfilter;
        // Moodle Form.
        $mform = new translateform(null, ['coursedata' => $coursedata, 'mlangfilter' => $mlangfilter,
                'langpack' => $languagepack, 'editor' => $editor,
        ]);
        $this->mform = $mform;
    }

    /**
     * Export Data to Template.
     *
     * @param renderer_base $output
     * @return object
     * @throws \DeepL\DeepLException
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function export_for_template(renderer_base $output): stdClass {

        $this->output = $output;
        $data = new stdClass();
        // Data for mustache template.
        $data->langstrings = $this->langpacks->preparestrings();
        $data->targetlangs = $this->langpacks->preparetargetsoptionlangs();
        $data->sourcelangs = $this->langpacks->preparesourcesoptionlangs();

        // Hacky fix but the only way to adjust html...
        // This could be overridden in css and I might look at that fix for the future.
        $renderedform = $this->mform->render();
        $renderedform = str_replace('col-md-9', 'col-md-12', $renderedform);
        $data->mform = $renderedform;
        // Set langs.
        $loadedsection = $this->coursedata->get_loadedsection();
        $data->nosectionsloaded = $loadedsection === -99;
        $data->allselected = $loadedsection === -1;
        $data->sectionidnames = $this->prepare_sectionmenu($this->coursedata->get_sectioninfoall(), $loadedsection,
                $this->coursedata->get_format());
        $data->hasmodulelist = false;
        $data->modulesidnames = null;
        $data->anymoduleselected = false;
        if ($loadedsection >= 0) {
            $data->hasmodulelist = true;
            $coursedataloadedsectionnum = $this->coursedata->get_loadedsectionnum();
            $coursedatasections = $this->coursedata->getsections();
            $selectedsection = $coursedatasections[$coursedataloadedsectionnum];
            $data->anymoduleselected = $selectedsection->get_loadeddmoduleid() >= 0;
            $data->modulesidnames = $this->prepare_modulemenu($selectedsection->get_sectioncms(),
                    $selectedsection->get_loadeddmoduleid());
        }
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
        // Pass the glossary selector rendered.
        global $PAGE;
        $glorenderer = $PAGE->get_renderer('local_deepler', 'glossary');
        $glossaries = $this->langpacks->getusersglossaries() ?? [];
        $poolglossaries = $this->langpacks->getpoolglossaries($glossaries) ?? [];
        $publicglossaries = $this->langpacks->getpublicglossaries() ?? [];
        $glo = array_merge($glossaries, $publicglossaries, $poolglossaries);
        $data->glossayselector = $glorenderer->glossay_selector_deepl($glo,
                $this->langpacks->getcurrentlang(true), $this->langpacks->gettargetlang(true));
        return $data;
    }

    /**
     * Data for building the option for the section selector.
     *
     * @param \section_info[] $sections
     * @param int $selectedid
     * @param \core_courseformat\base $format
     * @return array
     */
    private function prepare_sectionmenu(array $sections, int $selectedid, base $format): array {
        $menu = [];
        foreach ($sections as $section) {
            $tmp = new section($section, $format, -1);
            if ($tmp->is_empty()) {
                continue;
            }
            unset($tmp);
            $menu[] = [
                    'id' => $section->id,
                    'name' => $this->mlangfilter->filter($section->name ?? $format->get_default_section_name($section)),
                    'selected' => $section->id == $selectedid,
            ];
        }
        return $menu;
    }

    /**
     * Data for building the option for the activity selector.
     *
     * @param \cm_info[] $modules
     * @param int $selectedid
     * @return array
     */
    private function prepare_modulemenu(array $modules, int $selectedid): array {
        $menu = [];
        foreach ($modules as $module) {
            $menu[] =
                    [
                            'id' => $module->id,
                            'name' => $this->mlangfilter->filter($module->name),
                            'selected' => $module->id == $selectedid,
                    ];
        }
        return $menu;
    }
}
