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

namespace local_deepler\editor;

use MoodleQuickForm_editor;

/**
 * Translation ATTO Editor.
 *
 * Provides a custom editor for translation editing.
 *
 * @package    local_deepler
 * @copyright  2022 Kaleb Heitzman <kaleb@jamfire.io>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * TODO MDL-0 allow user to change default editor.
 */
class MoodleQuickForm_cteditor extends MoodleQuickForm_editor {

    /**
     * Course Translation Editor Setup.
     *
     * Intercept options in order to disable autosave, specify toolbar, etc.
     *
     * @param string $elementName
     * @param string $elementLabel
     * @param array $attributes
     * @param object $options
     */
    public function __construct($elementName = null, $elementLabel = null, $attributes = null, $options = null) { // phpcs:ignore
        // Set custom options.
        $this->_options['subdirs'] = true;
        $this->_options['maxbytes'] = 10240;
        $this->_options['maxfiles'] = EDITOR_UNLIMITED_FILES;
        $this->_options['noclean'] = true;
        $this->_options['trusttext'] = true;
        $this->_options['enable_filemanagement'] = true;
        $this->_options['atto:toolbar'] = 'collapse = collapse
        style1 = title, bold, italic
        list = unorderedlist, orderedlist, indent
        links = link
        files = emojipicker, image, media, recordrtc, managefiles, h5p
        style2 = underline, strike, subscript, superscript
        align = align
        insert = table, clear
        undo = false
        accessibility = accessibilitychecker, accessibilityhelper
        other = html';
        $this->_options['autosave'] = false;
        $this->_options['removeorphaneddrafts'] = true;

        // phpcs:ignore
        parent::__construct($elementName, $elementLabel, $attributes, $options);
    }
}
