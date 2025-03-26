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

use advanced_testcase;
use cm_info;
use moodle_url;
use ReflectionClass;

/**
 * Unit tests for the module class.
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_deepler\local\data\module
 */
final class module_test extends advanced_testcase {
    /** @var \cm_info */
    protected cm_info $cm;
    /** @var \cm_info */
    protected cm_info $cm2;

    public function setUp(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $m = $generator->create_module('quiz', ['course' => $course->id, 'name' => 'Test Quiz', 'description' => 'Test intro']);
        $courseinfo = get_fast_modinfo($course);
        $this->cm = $courseinfo->get_cm($m->cmid);
    }

    /**
     * Test the constructor of the module class.
     *
     * @covers \local_deepler\local\data\module::__construct
     */
    public function test_constructor() {
        $this->resetAfterTest(true);

        $module = new module($this->cm);

        $this->assertInstanceOf(module::class, $module);
        $this->assertEquals('Quiz', $module->getpluginname());
        $this->assertEquals($this->cm->get_icon_url()->out(), $module->geticon());
        $this->assertTrue($module->isvisible());
    }

    /**
     * Test the getlink method.
     *
     * @covers \local_deepler\local\data\module::getlink
     * @covers \local_deepler\local\data\module::buildlink
     */
    public function test_buildlink() {
        $this->resetAfterTest(true);
        $module = new module($this->cm);
        $modulereflexion = new ReflectionClass($module);
        $cm = $modulereflexion->getProperty('cm');
        $cm->setAccessible(true);
        $expectedlink = new moodle_url('/course/modedit.php', ['update' => $cm->getValue($module)->id]);
        $this->assertEquals($expectedlink->out(), $module->getlink());
    }

    /**
     * Test the isvisible method.
     *
     * @covers \local_deepler\local\data\module::isvisible
     */
    public function test_isvisible() {
        $this->resetAfterTest(true);
        $module = new module($this->cm);

        $this->assertTrue($module->isvisible());
    }

    /**
     * Test the getchilds method.
     *
     * @covers \local_deepler\local\data\module::getchilds
     */
    public function test_getchilds() {
        $this->resetAfterTest(true);
        $module = new module($this->cm);

        $childs = $module->getchilds();
        $this->assertIsArray($childs);
    }

    /**
     * Test the haschilds method.
     *
     * @covers \local_deepler\local\data\module::haschilds
     */
    public function test_haschilds() {
        $this->resetAfterTest(true);
        $module = new module($this->cm);

        $this->assertFalse($module->haschilds());
    }

    /**
     * Test the geticon method.
     *
     * @covers \local_deepler\local\data\module::geticon
     */
    public function test_geticon() {
        $this->resetAfterTest(true);
        $module = new module($this->cm);

        $this->assertEquals($this->cm->get_icon_url()->out(), $module->geticon());
    }

    /**
     * Test the getpurpose method.
     *
     * @covers \local_deepler\local\data\module::getpurpose
     */
    public function test_getpurpose() {
        $this->resetAfterTest(true);
        $module = new module($this->cm);

        $this->assertEquals(call_user_func('quiz_supports', FEATURE_MOD_PURPOSE), $module->getpurpose());
    }

    /**
     * Test the getpluginname method.
     *
     * @covers \local_deepler\local\data\module::getpluginname
     */
    public function test_getpluginname() {
        $this->resetAfterTest(true);
        $module = new module($this->cm);

        $this->assertEquals(get_string('pluginname', 'quiz'), $module->getpluginname());
    }
}
