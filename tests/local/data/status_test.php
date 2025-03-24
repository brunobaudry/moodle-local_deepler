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

namespace local\data;

use advanced_testcase;
use local_deepler\local\data\status;
use ReflectionClass;

/**
 * Unit tests for the status class.
 *
 * @package    local_deepler
 * @category   test
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class status_test extends advanced_testcase {

    /**
     * Test the constructor and initial values.
     *
     * @covers \local_deepler\local\data\status::__construct
     */
    public function test_constructor() {
        $status = new status(1, 'table', 'field', 'en');
        $statusreflexion = new ReflectionClass($status);
        $t_id = $statusreflexion->getProperty('t_id');
        $t_table = $statusreflexion->getProperty('t_table');
        $t_field = $statusreflexion->getProperty('t_field');
        $t_lang = $statusreflexion->getProperty('t_lang');
        $t_lastmodified = $statusreflexion->getProperty('t_lastmodified');
        $s_lastmodified = $statusreflexion->getProperty('s_lastmodified');
        $this->assertEquals(0, $status->get_id());
        $this->assertEquals(1, $t_id->getValue($status));
        $this->assertEquals('table', $t_table->getValue($status));
        $this->assertEquals('field', $t_field->getValue($status));
        $this->assertEquals('en', $t_lang->getValue($status));

        $this->assertEquals('0', $t_lastmodified->getValue($status));
        $this->assertEquals('0', $s_lastmodified->getValue($status));
    }

    /**
     * Test the getupdate method.
     *
     * @covers \local_deepler\local\data\status::getupdate
     */
    public function test_getupdate() {
        global $DB;
        $this->resetAfterTest(true);

        $status = new status(1, 'table', 'field', 'en');
        $statusreflexion = new ReflectionClass($status);
        $t_lastmodified = $statusreflexion->getProperty('t_lastmodified');
        $s_lastmodified = $statusreflexion->getProperty('s_lastmodified');
        $t_lastmodified->setAccessible(true);
        $s_lastmodified->setAccessible(true);
        $status->getupdate();

        $record = $DB->get_record(status::$dtable, ['id' => $status->get_id()]);
        $this->assertNotEmpty($record);
        $this->assertEquals($status->get_id(), $record->id);
        $this->assertEquals($t_lastmodified->getValue($status), $record->s_lastmodified);
        $this->assertEquals($s_lastmodified->getValue($status), $record->t_lastmodified);
    }

    /**
     * Test the istranslationneeded method.
     *
     * @covers \local_deepler\local\data\status::istranslationneeded
     */
    public function test_istranslationneeded() {
        $status = new status(1, 'table', 'field', 'en');
        $statusreflexion = new ReflectionClass($status);
        $t_lastmodified = $statusreflexion->getProperty('t_lastmodified');
        $s_lastmodified = $statusreflexion->getProperty('s_lastmodified');
        $t_lastmodified->setAccessible(true);
        $s_lastmodified->setAccessible(true);
        $s_lastmodified->setValue($status, 50); // Source timestamp.
        $t_lastmodified->setValue($status, 100); // Translation timestamp.
        $this->assertFalse($status->istranslationneeded());

        $s_lastmodified->setValue($status, 150);
        $this->assertTrue($status->istranslationneeded());
    }

    /**
     * Test the isready method.
     *
     * @covers \local_deepler\local\data\status::isready
     */
    public function test_isready() {
        $status = new status(1, 'table', 'field', '');
        $this->assertFalse($status->isready());
        $statusreflexion = new ReflectionClass($status);
        $t_lang = $statusreflexion->getProperty('t_lang');
        $t_lang->setAccessible(true);
        $t_lang->setValue($status, 'en');
        $this->assertTrue($status->isready());
    }
}
