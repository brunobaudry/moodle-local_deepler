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
use filter_multilang2\text_filter as Multilang2TextFilter;
use local_deepler\local\data\field;
use local_deepler\local\data\interfaces\iconic_interface;
use local_deepler\local\data\interfaces\visibility_interface;
use local_deepler\local\data\module;
use local_deepler\local\data\section;
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
     * Getter for langcodes.
     *
     * @return array
     */
    public function get_langcodes(): array {
        return $this->langcodes;
    }

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
     * List of langcodes.
     *
     * @param array $codes
     * @return void
     */
    protected function gatherlangcodes(array $codes): void {
        foreach ($codes as $code) {
            if (!in_array($code, $this->langcodes)) {
                $this->langcodes[] = $code;
            }
        }
    }
    /**
     * Course first section
     *
     * @param string $header
     * @param array $settingfields
     * @return void
     * @throws \coding_exception
     */
    protected function makecoursesetting(string $header, array $settingfields): void {
        // Open section container for the course settings course__settings section-item.
        $this->_form->addElement('html', "<div class='section-item'>");
        // Open header div.
        $this->_form->addElement('html', "<div class='course-section-header d-flex'>");
        $this->_form->addElement('html', $header);
        $this->_form->addElement('html', DIV_CLOSE); // Close header div.
        $this->makesettings($settingfields, 0);
        // Close section container for the course settings course__settings section-item.
        $this->_form->addElement('html', DIV_CLOSE);
    }

    /**
     * First section of the form for course's settings.
     *
     * @param array $settingfields
     * @param string $index
     * @return void
     * @throws \coding_exception
     */
    public function makesettings(array $settingfields, string $index): void {
        // Open course settings section.
        $this->_form->addElement('html', "<div id='sectiondata[$index]' class='local_deepler__sectiondata'>");
        /** @var field $field */
        foreach ($settingfields as $field) {
            $this->makefieldrow($field);
        }
        // Close course settings section.
        $this->_form->addElement('html', DIV_CLOSE);
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
        $sectionmodules = $section->getmodules();
        if (!empty($sectionmodules) || !empty($sectionfields)) {
            // Open section container for the course settings course__settings section-item.
            $visibilityclass = $this->getitemvisibilityclass($section);
            $this->_form->addElement('html', "<div id='local_deepler__section{$section->getid()}'
                        class='section-item $visibilityclass'>");
            $this->buildhiddenftomstudent();
            // Open header div.
            $this->_form->addElement('html', "<div class='course-section-header d-flex'>");
            $this->_form->addElement('html',
                    $this->makeheader($this->mlangfilter->filter($section->getsectionname()), $section->getlink(), 3));
            $this->_form->addElement('html', DIV_CLOSE); // Close header div.
            // Section fields.
            $this->makesettings($sectionfields, $section->getid());
            // Section's modules.
            $this->makemodules($sectionmodules);
            // Close section container for the course settings course__settings section-item.
            $this->_form->addElement('html', DIV_CLOSE);
            // Close section container for the course settings course__settings section-item.
        }
    }

    /**
     * Write the visibility class for the item for js filtering.
     *
     * @param \local_deepler\local\data\interfaces\visibility_interface $item
     * @return string
     */
    protected function getitemvisibilityclass(visibility_interface $item): string {
        return 'local_deepler' . ($item->isvisible() ? 'visible' : 'invisible');
    }

    /**
     * Create a hidden from students badge.
     *
     * @return void
     * @throws \coding_exception
     */
    protected function buildhiddenftomstudent(): void {
        $hiddenfromstudents = get_string('hiddenfromstudents');
        $this->_form->addElement('html',
                "<small class='badge rounded-pill bg-secondary text-dark'
                data-action='local_deepler__hiddenfromstudents'>
                <i class='fa fa-eye-slash' aria-hidden='true'></i>&nbsp;<small>$hiddenfromstudents</small></small>");
    }

    /**
     * Write a header for the section, module or sub.
     *
     * @param string $title
     * @param string $link
     * @param int $level
     * @param string $icon
     * @return string
     * @throws \coding_exception
     */
    protected function makeheader(string $title, string $link, int $level, string $icon = ''): string {
        $class = "h$level sectionname course-content-item d-flex align-self-stretch align-items-center mb-0 p-2";
        return "<span id='$title' class='$class'>$icon $title {$this->makeeditbutton($link)}</span>";
    }

    /**
     * Create an edit in place button for each item.
     *
     * @param string $link
     * @return string
     * @throws \coding_exception
     */
    protected function makeeditbutton(string $link): string {
        // Edit button.
        $editbuttontitle = get_string('editbutton', 'local_deepler');
        return "<a class='small p-2'
                    id='local_deepler__sourcelink'
                    href='{$link}'
                    target='_blank'
                    title='$editbuttontitle'>
                    <i class='icon fa fa-pen fa-fw' aria-hidden='true'></i>
                    </a>";
    }

    /**
     * Make modules.
     *
     * @param array $sectionmodules
     * @return void
     * @throws \coding_exception
     */
    protected function makemodules(array $sectionmodules): void {
        /** @var \local_deepler\local\data\module $module */
        foreach ($sectionmodules as $module) {
            $this->_form->addElement('html', '<div class="divider"><hr/></div>');
            $this->makemodule($module);
        }
    }

    /**
     * Make single module.
     *
     * @param \local_deepler\local\data\module $module
     * @return void
     * @throws \coding_exception
     */
    protected function makemodule(module $module): void {
        $this->_form->addElement('html',
                "<div id='{$module->getpluginname()}'
                    class='activity-item local_deepler__activity py-2 {$this->getitemvisibilityclass($module)}'>");
        $this->buildhiddenftomstudent();
        $icon = $this->makeicon($module, "class='activityicon' data-region='activity-icon'");
        $header = $this->makeheader($this->makeactivitydesc($module), $module->getlink(), 4, $icon);
        $this->_form->addElement('html', $header);
        $fields = $module->getfields();
        $childs = $module->getchilds();
        // Basic common fields.
        if (!empty($fields)) {
            /** @var field $field */
            foreach ($fields as $field) {
                $this->makefieldrow($field);
            }
        }
        // Childs (like book pages or quiz questions).
        if (!empty($childs)) {
            /** @var \local_deepler\local\data\interfaces\translatable_interface $child */
            foreach ($childs as $child) {
                $interfaces = class_implements($child);
                $isiconic = in_array('local_deepler\local\data\interfaces\iconic_interface', $interfaces);
                $iseditable = in_array('local_deepler\local\data\interfaces\editable_interface', $interfaces);
                // Open section container for the course settings course__settings section-item.
                $this->_form->addElement('html', "<div class='section-item'>");
                if ($isiconic && $iseditable) {
                    // Open header div.
                    $this->_form->addElement('html', "<div class='course-section-header d-flex'>");
                    // Add a header for the child.
                    $this->_form->addElement('html',
                            $this->makeheader($this->makeactivitydesc($child), $child->getlink(), 5, $this->makeicon($child)));

                    $this->_form->addElement('html', DIV_CLOSE);
                }
                /** @var field $f */
                foreach ($child->getfields() as $f) {
                    $this->makefieldrow($f);
                }
                // Close section container.
                $this->_form->addElement('html', DIV_CLOSE);
            }
        }
        $this->_form->addElement('html', DIV_CLOSE);
    }

    /**
     * Build icon the Moodle way.
     *
     * @param \local_deepler\local\data\interfaces\iconic_interface $item
     * @param string $imageattributes
     * @return string
     */
    protected function makeicon(iconic_interface $item, string $imageattributes = ''): string {
        return "<span class='activity-icon activityiconcontainer smaller {$item->getpurpose()} courseicon align-self-start mr-2'>
                                    <img src='{$item->geticon()}' $imageattributes
                                    alt='icon for {$item->getpluginname()}'/></span>";
    }

    /**
     * Build the header line.
     *
     * @param editable_interface|iconic_interface $item
     * @return string
     */
    protected function makeactivitydesc(iconic_interface|editable_interface $item): string {
        return $item->getpluginname() . ': ' . $this->mlangfilter->filter($item->getfields()[0]->get_text());
    }
}
