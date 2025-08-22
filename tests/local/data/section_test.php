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
use core_courseformat\base;
use course_modinfo;

/**
 * PHPUnit test for the section class.
 *
 * @package    local_deepler
 * @copyright  2025 bruno.baudry@bfh.ch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_deepler\local\data\section
 */
final class section_test extends advanced_testcase {
    /**
     * @var course_modinfo|null
     */
    protected ?course_modinfo $course;
    /** @var array|\section_info[] */
    protected array $sectioninfos;
    /** @var \core_courseformat\base */
    protected base $courseformat;

    /**
     * Setup function for the tests.
     *
     * @return void
     * @throws \moodle_exception
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_course_section(['course' => $course, 'section' => 1],
                        ['_name' => 'Test Section']);
        $this->course = get_fast_modinfo($course);
        $this->sectioninfos = $this->course->get_section_info_all();
        $this->courseformat = $this->createMock(base::class);
    }

    /**
     * Test the constructor.
     *
     * @covers \local_deepler\local\data\section::__construct
     * @return void
     * @throws \core\exception\moodle_exception
     */
    public function test_constructor(): void {
        $this->resetAfterTest(true);

        $section = new section($this->sectioninfos[0], $this->courseformat, -1);

        $this->assertInstanceOf(section::class, $section);
    }

    /**
     * Test isvisible method.
     *
     * @covers \local_deepler\local\data\section::isvisible
     * @return void
     */
    public function test_isvisible(): void {
        $this->resetAfterTest(true);

        $section = new section($this->sectioninfos[0], $this->courseformat, -1);

        $this->assertTrue($section->isvisible());
    }

    /**
     * Test getsectionname method.
     *
     * @covers \local_deepler\local\data\section::getsectionname
     * @return void
     * @todo MDL-0000 find a way to test this method with proper name.
     */
    public function test_getsectionname(): void {
        $this->resetAfterTest(true);

        $section = new section($this->sectioninfos[1], $this->courseformat, -1);

        $this->assertEquals('', $section->getsectionname());
    }

    /**
     * Test getfields method.
     *
     * @covers \local_deepler\local\data\section::getfields
     * @return void
     */
    public function test_getfields(): void {
        $this->resetAfterTest(true);

        $section = new section($this->sectioninfos[0], $this->courseformat, -1);

        $fields = $section->getfields();
        $this->assertIsArray($fields);
    }

    /**
     * Test getmodules method.
     *
     * @covers \local_deepler\local\data\section::populatemodules
     * @return void
     */
    public function test_getmodules(): void {
        $this->resetAfterTest(true);

        $section = new section($this->sectioninfos[0], $this->courseformat, -1);

        $modules = $section->populatemodules();
        $this->assertIsArray($modules);
    }

    /**
     * Test getlink method.
     *
     * @covers \local_deepler\local\data\section::getlink
     * @return void
     * @throws \core\exception\moodle_exception
     */
    public function test_getlink(): void {
        $this->resetAfterTest(true);

        global $CFG;
        $CFG->wwwroot = 'http://localhost';
        $sectionid = $this->sectioninfos[0]->id;
        $section = new section($this->sectioninfos[0], $this->courseformat, -1);

        $link = $section->getlink();
        $this->assertEquals('http://localhost/course/editsection.php?id=' . $sectionid, $link);
    }

    /**
     * Test getid method.
     *
     * @covers \local_deepler\local\data\section::getid
     * @return void
     */
    public function test_getid(): void {
        $this->resetAfterTest(true);
        $sectionid = $this->sectioninfos[0]->id;
        $sectionid1 = $this->sectioninfos[1]->id;
        $section = new section($this->sectioninfos[0], $this->courseformat, -1);
        $section1 = new section($this->sectioninfos[1], $this->courseformat, -1);

        $this->assertEquals($sectionid, $section->getid());
        $this->assertEquals($sectionid1, $section1->getid());
    }
}
