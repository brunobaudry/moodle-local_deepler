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
use core\output\renderer_base;
use templatable;
use core_filters\text_filter;
use local_deepler\local\data\field;
use local_deepler\local\services\lang_helper;
use local_deepler\local\services\utils;

/**
 * Child data.
 *
 * @package local_deepler
 * @copyright 2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class child_data extends translate_data implements renderable, templatable {
    /**
     * @var \local_deepler\local\data\interfaces\translatable_interface
     */
    private mixed $child;

    /**
     * Construct.
     *
     * @param mixed $child
     * @param \local_deepler\local\services\lang_helper $languagepack
     * @param \local_deepler\output\Multilang2TextFilter|\core_filters\text_filter $mlangfilter
     * @param string $editor
     */
    public function __construct(mixed $child, lang_helper $languagepack,
            Multilang2TextFilter|text_filter $mlangfilter,
            string $editor) {
        parent::__construct($languagepack, $mlangfilter, $editor);
        $this->child = $child;
    }

    /**
     * Function to export the renderer data in a format that is suitable for a
     * mustache template. This means:
     * 1. No complex types - only stdClass, array, int, string, float, bool
     * 2. Any additional info that is required for the template is pre-calculated (e.g. capability checks).
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        global $PAGE;
        $renderer = $PAGE->get_renderer('local_deepler', 'translate');
        $fields = $this->child->getfields();
        $interfaces = class_implements($this->child);
        $isiconic = in_array('local_deepler\local\data\interfaces\iconic_interface', $interfaces);
        $iseditable = in_array('local_deepler\local\data\interfaces\editable_interface', $interfaces);
        $istranslatable = in_array('local_deepler\local\data\interfaces\translatable_interface', $interfaces);
        $childs = '';
        $activitydesc = $istranslatable ? $this->makeactivitydesc($this->child) : '';

        /** @var field $f */
        foreach ($fields as $f) {
            try {
                $rowdata = new row_data($f, $this->languagepack, $this->mlangfilter, $this->editor);
                $childs .= $renderer->makefieldrow($rowdata);
            } catch (Exception $e) {
                continue;
            }
        }
        return [
                'hasicon' => true,
                'level' => '5',
                'hasheader' => $isiconic && $iseditable,
                'id' => Utils::makehtmlid($activitydesc),
                'link' => $iseditable ? $this->child->getlink() : '',
                'itempurpose' => $isiconic ? $this->child->getpurpose() : '',
                'icon' => $isiconic ? $this->child->geticon() : '',
                'pluginname' => $isiconic && $istranslatable ? $this->child->getpluginname() : '',
                'activitydesc' => $activitydesc,
                'childs' => $childs,
        ];
    }

}
