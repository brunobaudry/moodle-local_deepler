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

/**
 * Unit tests for MoodleQuickForm_cteditor class.
 *
 * @covers \local_deepler\editor\MoodleQuickForm_cteditor
 * @package    local_deepler
 * @category   test
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_deepler\editor;

use advanced_testcase;

/**
 * Unit tests for MoodleQuickForm_cteditor class.
 */
final class moodlequickformcteditor_test extends advanced_testcase {

    /**
     * Test the constructor of MoodleQuickForm_cteditor.
     *
     * @covers \local_deepler\editor\MoodleQuickForm_cteditor
     * @return void
     */
    public function test_constructor(): void {
        global $CFG;
        require_once($CFG->dirroot . '/lib/form/editor.php');

        $elementname = 'testelement';
        $elementlabel = 'Test Element';
        $attributes = [];
        $editorsoptions = new \stdClass();

        $editor = new MoodleQuickForm_cteditor($elementname, $elementlabel, $attributes, $editorsoptions);
        $this->assertTrue($editor->getSubdirs() === 1);
        $this->assertEquals(10240, $editor->getMaxbytes());
        $this->assertEquals(EDITOR_UNLIMITED_FILES, $editor->getMaxfiles());
        $editor->setValue(['text' => 'hello']);
        $this->assertEquals('hello', $editor->get_text());
        $reflection = new \ReflectionClass($editor);
        $optionsproperty = $reflection->getProperty('_options');
        $optionsproperty->setAccessible(true);
        $options = $optionsproperty->getValue($editor);
        $this->assertTrue($options['enable_filemanagement']);
        $this->assertEquals('collapse = collapse
        style1 = title, bold, italic
        list = unorderedlist, orderedlist, indent
        links = link
        files = emojipicker, image, media, recordrtc, managefiles, h5p
        style2 = underline, strike, subscript, superscript
        align = align
        insert = table, clear
        undo = false
        accessibility = accessibilitychecker, accessibilityhelper
        other = html', $options['atto:toolbar']);
        $this->assertFalse($options['autosave']);
        $this->assertTrue($options['removeorphaneddrafts']);
    }

    /**
     * Test that the editor is properly initialized with custom attributes.
     *
     * @covers \local_deepler\editor\MoodleQuickForm_cteditor::getAttributes()
     */
    public function test_customattributes(): void {

        $customattributes = ['rows' => 10, 'cols' => 50];
        $editor = new \local_deepler\editor\MoodleQuickForm_cteditor('test_editor', 'Test Editor', $customattributes);

        $this->assertEquals(10, $editor->getAttributes()['rows']);
        $this->assertEquals(50, $editor->getAttributes()['cols']);
    }
}
