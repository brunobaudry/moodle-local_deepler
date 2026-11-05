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

namespace local_deepler\external;

use advanced_testcase;

/**
 * Tests for detect_source_language external function.
 *
 * @package    local_deepler
 * @copyright  2026 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_deepler\external\detect_source_language
 */
final class detect_source_language_test extends advanced_testcase {

    public function test_autodetect_disabled_returns_empty(): void {
        $this->resetAfterTest();
        set_config('autodetect_sourcelang', 0, 'local_deepler');

        $result = detect_source_language::execute('Bonjour tout le monde', 'row_1');
        $this->assertFalse($result['enabled']);
        $this->assertEquals('', $result['detected_lang']);
        $this->assertEquals('row_1', $result['row_id']);
    }

    public function test_empty_text_returns_empty(): void {
        $this->resetAfterTest();
        set_config('autodetect_sourcelang', 1, 'local_deepler');

        $result = detect_source_language::execute('   ', 'row_2');
        $this->assertTrue($result['enabled']);
        $this->assertEquals('', $result['detected_lang']);
        $this->assertEquals('row_2', $result['row_id']);
    }
}
