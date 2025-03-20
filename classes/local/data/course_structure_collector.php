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

namespace local_deepler\local\data;

use local_deepler\local\data\interfaces\editable_interface;
use local_deepler\local\data\interfaces\iconic_interface;
use stdClass;

/**
 * Entry point class that collects need data from courses.
 * And map it to our needs building sections of activities containing textfields.
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class course_structure_collector {
    /**
     * @var course
     */
    private course $course;
    /**
     * @var array
     */
    private array $modules;

    /**
     * Constructor.
     *
     * @param \stdClass $course course object coming from db call.
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function __construct(stdClass $course) {
        // Set the default filed size for the text fields.
        field::$mintxtfieldsize = get_config('local_deepler', 'scannedfieldsize');
        // Load the course object.
        $this->course = new course($course);
        $this->modules = [];

        echo "<a href='{$this->course->getlink()}'>{$this->course->getlink()}</a>";
        $coursefileds = $this->course->getfields();
        foreach ($this->course->getsections() as $section) {
            $section->getfields();
            $modules = $section->getmodules();

            echo '<br/>******<br/>';
            echo "<h2>{$section->getsectionname()}</h2>";
            echo "<a href='{$section->getlink()}'>{$section->getlink()}</a>";
            if ($modules) {
                foreach ($modules as $module) {
                    //$m = $module->getmainfields();
                    $subs = $module->getfields();
                    $this->modules[] = $module;
                    echo '<br/>######<br/>';
                    echo "<a href='{$module->getlink()}'>{$module->getlink()}</a>";
                    echo '<br/>';
                    echo "<p class='{$module->getpurpose()}'><img src='{$module->geticon()}'> {$module->getpluginname()}</p>";
                    echo '<br/>';

                    if ($module->haschilds()) {
                        echo '<br/>###### CHILDS #####<br/>';
                        foreach ($module->getchilds() as $child) {

                            echo '<br/>######<br/>';
                            if ($child instanceof iconic_interface) {
                                $icon = $child->geticon();
                                echo "<p class='{$child->getpurpose()}'><img src='$icon'> {$child->getpluginname()}</p>";
                            }
                            if ($child instanceof editable_interface) {
                                echo "<a href='{$child->getlink()}'>{$child->getlink()}</a>";
                                echo '<br/>';
                            }
                            $subs = array_merge($subs, $child->getfields());
                        }
                    }
                }
            }
        }
    }
}
