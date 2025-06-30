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
     * @return void
     */
    public function test_constructor(): void {
        $status = new status(1, 'table', 'field', 'en');
        $statusreflexion = new ReflectionClass($status);
        $tid = $statusreflexion->getProperty('t_id');
        $ttable = $statusreflexion->getProperty('t_table');
        $tfield = $statusreflexion->getProperty('t_field');
        $tlang = $statusreflexion->getProperty('t_lang');
        $tlastmodified = $statusreflexion->getProperty('t_lastmodified');
        $slastmodified = $statusreflexion->getProperty('s_lastmodified');
        $this->assertEquals(0, $status->get_id());
        $this->assertEquals(1, $tid->getValue($status));
        $this->assertEquals('table', $ttable->getValue($status));
        $this->assertEquals('field', $tfield->getValue($status));
        $this->assertEquals('en', $tlang->getValue($status));

        $this->assertEquals('0', $tlastmodified->getValue($status));
        $this->assertEquals('0', $slastmodified->getValue($status));
    }

    /**
     * Test the getupdate method.
     *
     * @covers \local_deepler\local\data\status::getupdate
     * @return void
     */
    public function test_getupdate(): void {
        global $DB;
        $this->resetAfterTest(true);

        $status = new status(1, 'table', 'field', 'en');
        $statusreflexion = new ReflectionClass($status);
        $tlastmodified = $statusreflexion->getProperty('t_lastmodified');
        $slastmodified = $statusreflexion->getProperty('s_lastmodified');
        $tlastmodified->setAccessible(true);
        $slastmodified->setAccessible(true);
        $status->getupdate();

        $record = $DB->get_record(status::$dtable, ['id' => $status->get_id()]);
        $this->assertNotEmpty($record);
        $this->assertEquals($status->get_id(), $record->id);
        $this->assertEquals($tlastmodified->getValue($status), $record->s_lastmodified);
        $this->assertEquals($slastmodified->getValue($status), $record->t_lastmodified);
    }

    /**
     * Test the istranslationneeded method.
     *
     * @covers \local_deepler\local\data\status::istranslationneeded
     * @return void
     */
    public function test_istranslationneeded(): void {
        $status = new status(1, 'table', 'field', 'en');
        $statusreflexion = new ReflectionClass($status);
        $tlastmodified = $statusreflexion->getProperty('t_lastmodified');
        $slastmodified = $statusreflexion->getProperty('s_lastmodified');
        $tlastmodified->setAccessible(true);
        $slastmodified->setAccessible(true);
        $slastmodified->setValue($status, 50); // Source timestamp.
        $tlastmodified->setValue($status, 100); // Translation timestamp.
        $this->assertFalse($status->istranslationneeded());

        $slastmodified->setValue($status, 150);
        $this->assertTrue($status->istranslationneeded());
    }

    /**
     * Test the isready method.
     *
     * @covers \local_deepler\local\data\status::isready
     * @return void
     */
    public function test_isready(): void {
        $status = new status(1, 'table', 'field', '');
        $this->assertFalse($status->isready());
        $statusreflexion = new ReflectionClass($status);
        $tlang = $statusreflexion->getProperty('t_lang');
        $tlang->setAccessible(true);
        $tlang->setValue($status, 'en');
        $this->assertTrue($status->isready());
    }
}
