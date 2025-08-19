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
use Exception;
use filter_multilang2\text_filter as Multilang2TextFilter;
use local_deepler\local\data\field;
use local_deepler\local\data\interfaces\iconic_interface;
use local_deepler\local\data\interfaces\visibility_interface;
use local_deepler\local\data\interfaces\translatable_interface;
use local_deepler\local\data\module;
use local_deepler\local\data\section;
use local_deepler\local\services\utils;
use moodleform;

/**
 * Common class for all forms of the plugin.
 *
 * @package local_deepler
 * @copyright 2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class deeplerform extends moodleform {
    /**
     * @var string[]
     */
    protected array $langcodes;
    /**
     * @var mixed
     */
    protected mixed $coursedata;
    /**
     * @var text_filter|Multilang2TextFilter
     */
    protected text_filter|Multilang2TextFilter $mlangfilter;


    /**
     * Main data definition function.
     *
     * @return void
     */
    protected function definition(): void {
        $this->langcodes = [];
        // Get course data.
        $this->coursedata = $this->_customdata['coursedata'];
        // Get mlangfilter to filter text.
        $this->mlangfilter = $this->_customdata['mlangfilter'];
    }
    /**
     * Course first section (Course settings block).
     *
     * @param string $title Header title
     * @param string $link Edit link URL
     * @param array $settingfields Field items to render
     * @param int $level Header level (default 3)
     * @param string $index Sectiondata index (default '0')
     * @return void
     * @throws \coding_exception
     */
    protected function makecoursesetting(string $title, string $link, array $settingfields, int $level = 3,
            string $index = '0'): void {
        global $PAGE;
        $renderer = $PAGE->get_renderer('local_deepler', 'translate');
        $data = new coursesettings_data($title, $link, $settingfields, $this->langpack, $this->mlangfilter, $this->editor, $level,
                $index);
        $this->_form->addElement('html', $renderer->makecoursesetting($data));
    }


    /**
     * Create sections.
     *
     * @param array $sections
     * @return void
     * @throws \coding_exception
     */
    protected function makesections(array $sections) {
        foreach ($sections as $section) {
            $this->makesection($section);
        }
    }

    /**
     * Create a section
     *
     * @param \local_deepler\local\data\section $section
     * @return void
     * @throws \coding_exception
     */
    protected function makesection(section $section): void {
        $sectionfields = $section->getfields();
        $sectionmodules = $section->get_modules();
        if (!empty($sectionmodules) || !empty($sectionfields)) {
            global $PAGE;
            $sectiondata = new section_data($section, $this->langpack,
                    $this->mlangfilter,
                    $this->editor);
            $renderer = $PAGE->get_renderer('local_deepler', 'translate');
            $this->_form->addElement('html', $renderer->makesection($sectiondata));
        }
    }
}
