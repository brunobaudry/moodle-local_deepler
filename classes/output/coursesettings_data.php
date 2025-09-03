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
use local_deepler\local\services\lang_helper;
use local_deepler\local\services\utils;

/**
 * Course settings block data for translate page.
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursesettings_data extends translate_data implements renderable, templatable {
    /** @var string */
    private string $title;
    /** @var string */
    private string $link;

    /** @var field[] */
    private array $fields;

    /**
     * Construct.
     *
     * @param string $title Header title
     * @param string $link Edit link
     * @param field[] $fields
     * @param lang_helper $languagepack
     * @param Multilang2TextFilter|text_filter $mlangfilter
     * @param string $editor
     */
    public function __construct(
            string $title,
            string $link,
            array $fields,
            lang_helper $languagepack,
            Multilang2TextFilter|text_filter $mlangfilter,
            string $editor) {
        parent::__construct($languagepack, $mlangfilter, $editor);
        $this->title = $title;
        $this->link = $link;
        $this->fields = $fields;
    }

    /**
     * Prepare data for the translate_coursesettings mustache template.
     *
     * @param renderer_base $output
     * @return array
     * @throws \core\exception\moodle_exception
     */
    public function export_for_template(renderer_base $output): array {
        global $PAGE;
        $renderer = $PAGE->get_renderer('local_deepler', 'translate');

        $fieldsrendered = '';
        foreach ($this->fields as $f) {
            try {
                $rowdata = new row_data($f, $this->languagepack, $this->mlangfilter, $this->editor);
                $fieldsrendered .= $renderer->makefieldrow($rowdata);
            } catch (Exception $e) {
                continue;
            }
        }

        return [
                'hasheader' => true,
                'hasicon' => false,
                'level' => '3',
                'activitydesc' => $this->title,
            'link' => $this->link,
            'id' => utils::makehtmlid($this->title),
                'index' => '0',
            'fields' => $fieldsrendered,
        ];
    }
}
