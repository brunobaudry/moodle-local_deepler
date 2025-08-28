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

use renderable;
use renderer_base;
use templatable;
use core_filters\text_filter;
use Exception;
use filter_multilang2\text_filter as Multilang2TextFilter;
use local_deepler\local\data\field;
use local_deepler\local\data\module;
use local_deepler\local\data\section;
use local_deepler\local\services\lang_helper;
use local_deepler\local\services\utils;

/**
 * Section data for translate page.
 *
 * @package local_deepler
 * @copyright 2025 Bruno Baudry
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section_data extends translate_data implements renderable, templatable {
    /** @var section */
    private section $section;

    /**
     * Construct.
     *
     * @param section $section
     * @param lang_helper $languagepack
     * @param Multilang2TextFilter|text_filter $mlangfilter
     * @param string $editor
     */
    public function __construct(section $section,
            lang_helper $languagepack,
            Multilang2TextFilter|text_filter $mlangfilter,
            string $editor) {
        parent::__construct($languagepack, $mlangfilter, $editor);
        $this->section = $section;
    }

    /**
     * Prepare data for the translate_section mustache template.
     *
     * @param renderer_base $output
     * @return array
     * @throws \core\exception\moodle_exception
     */
    public function export_for_template(renderer_base $output): array {
        global $PAGE;
        $renderer = $PAGE->get_renderer('local_deepler', 'translate');

        // Render section fields.
        $fieldsrendered = '';
        /** @var field $f */
        foreach ($this->section->getfields() as $f) {
            try {
                $rowdata = new row_data($f, $this->languagepack, $this->mlangfilter, $this->editor);
                $fieldsrendered .= $renderer->makefieldrow($rowdata);
            } catch (Exception $e) {
                continue;
            }
        }

        // Render section modules with divider, similar to deeplerform::makesection.
        $modulesrendered = '';
        /** @var module $m */
        foreach ($this->section->get_modules() as $m) {
            try {
                $modulesrendered .= '<div class="divider"><hr/></div>';
                $modulesrendered .= $renderer->makemodule(new module_data($m, $this->languagepack, $this->mlangfilter,
                        $this->editor));
            } catch (Exception $e) {
                continue;
            }
        }

        $sectionid = (string) $this->section->getid();
        $title = $this->mlangfilter->filter($this->section->getsectionname());

        return [
                'hasicon' => false,
                'hasheader' => true,
                'level' => '3',
                'id' => 'local_deepler__section' . $sectionid,
                'sectionid' => $sectionid,
                'activitydesc' => $title,
                'link' => $this->section->getlink(),
                'visibilityclass' => 'local_deepler' . ($this->section->isvisible() ? 'visible' : 'invisible'),
                'fields' => $fieldsrendered,
                'modules' => $modulesrendered,
        ];
    }
}
