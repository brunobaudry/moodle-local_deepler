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

use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use core_filters\text_filter;
use local_deepler\local\data\field;
use local_deepler\local\data\module;
use local_deepler\local\services\lang_helper;
use local_deepler\local\services\utils;
use local_deepler\output\translate_data;

/**
 * Module data.
 *
 * @copyright 2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class module_data extends translate_data implements templatable, renderable {
    /**
     * @var \local_deepler\local\data\module
     */
    private module $module;

    /**
     * Construct.
     *
     * @param \local_deepler\local\data\module $module
     * @param \local_deepler\local\services\lang_helper $languagepack
     * @param \local_deepler\output\Multilang2TextFilter|\core_filters\text_filter $mlangfilter
     * @param string $editor
     */
    public function __construct(module $module, lang_helper $languagepack,
            Multilang2TextFilter|text_filter $mlangfilter,
            string $editor) {
        parent::__construct($languagepack, $mlangfilter, $editor);
        $this->module = $module;
    }

    /**
     * @inheritDoc
     */
    public function export_for_template(renderer_base $output) {
        global $PAGE;
        $renderer = $PAGE->get_renderer('local_deepler', 'translate');
        // TODO: Implement export_for_template() method.
        $activitydesc = $this->makeactivitydesc($this->module);
        $fields = $this->module->getfields();
        $fieldsrendered = '';
        if (!empty($fields)) {
            /** @var field $field */
            foreach ($fields as $f) {
                $rowdata = new row_data($f, $this->languagepack, $this->mlangfilter, $this->editor);
                $fieldsrendered .= $renderer->makefieldrow($rowdata);
            }
        }

        /** @var field $f */
        foreach ($fields as $f) {
            try {
                $rowdata = new row_data($f, $this->languagepack, $this->mlangfilter, $this->editor);
                $childs .= $renderer->makefieldrow($rowdata);
            } catch (Exception $e) {
                continue;
            }
        }
        $childs = $this->module->getchilds();
        $childsrendered = '';
        foreach ($childs as $c) {
            try {
                $childdata = new child_data($c, $this->languagepack, $this->mlangfilter, $this->editor);
                $childsrendered .= $renderer->makechild($childdata);
            } catch (Exception $e) {
                continue;
            }
        }
        return [
                'childs ' => $childsrendered,
                'fields ' => $fieldsrendered,
                'activitydesc' => $activitydesc,
                'link' => $this->module->getlink(),
                'id' => Utils::makehtmlid($activitydesc),
                'itempurpose' => $this->module->getpurpose(),
                'icon' => $this->module->geticon(),
                'pluginname' => $this->module->getpluginname(),
                'visibilityclass' => 'local_deepler' . ($this->module
                                ->isvisible() ? 'visible' : 'invisible'),
        ];
    }
}
