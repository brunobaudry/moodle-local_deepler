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

use core_filters\text_filter;
use local_deepler\local\services\lang_helper;
use filter_multilang2\text_filter as Multilang2TextFilter;

/**
 * Base class for translate page renderables
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class translate_data {
    /**
     * @var \local_deepler\local\services\lang_helper
     */
    protected lang_helper $languagepack;
    /**
     * @var text_filter|Multilang2TextFilter
     */
    protected text_filter|Multilang2TextFilter $mlangfilter;
    /** @var string */
    protected string $editor;

    /**
     * @param \local_deepler\local\services\lang_helper $languagepack
     * @param text_filter|Multilang2TextFilter $mlangfilter
     * @param string $editor
     */
    public function __construct(lang_helper $languagepack,
            text_filter|Multilang2TextFilter $mlangfilter,
            string $editor) {
        $this->languagepack = $languagepack;
        $this->mlangfilter = $mlangfilter;
        $this->editor = $editor;
    }

    /**
     * Build the header line.
     *
     * @param mixed $item
     * @return string
     */
    protected function makeactivitydesc(mixed $item): string {
        $fields = $item->getfields();
        $pluginname = $item->getpluginname();
        if (count($fields) > 0) {
            // If the item has at least a field. We get the first one as description.
            return $pluginname . ': ' . $this->mlangfilter->filter($fields[0]->get_text());
        }
        return $pluginname;
    }
}
