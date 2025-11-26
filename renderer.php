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

use plugin_renderer_base;

/**
 * Local Course Translator Renderer.
 *
 * @package    local_deepler
 * @copyright  2022 Kaleb Heitzman <kaleb@jamfire.io>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/Output_API
 */
class renderer extends plugin_renderer_base {
    /**
     * Render Translate Content Page.
     *
     * @param object $page
     * @return string
     * @throws \core\exception\moodle_exception
     */
    public function render_translate_page($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_deepler/translate_page', $data);
    }

    /**
     * Render MLANG remover.
     *
     * @param object $page
     * @return string
     * @throws \core\exception\moodle_exception
     */
    public function render_remove_mlangs_page($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_deepler/remove_mlangs_page', $data);
    }
}
