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

namespace local_deepler\local\services;

use advanced_testcase;
use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

/**
 * Test cases.
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/PHPUnit
 */
final class spreadsheetglossaryparser_test extends advanced_testcase {
    /**
     * Creates a fake spreadsheet, save and test parsing.
     *
     * @covers \local_deepler\local\services\spreadsheetglossaryparser
     * @return void
     */
    public function test_convert_xlsx_to_csv(): void {
        $this->resetAfterTest(true);
        // Build an XLSX in temp.
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'source');
        $sheet->setCellValue('B1', 'target');
        $sheet->setCellValue('A2', 'Hello');
        $sheet->setCellValue('B2', 'Bonjour');

        $tmp = tempnam(sys_get_temp_dir(), 'glx');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmp);

        $parser = new spreadsheetglossaryparser();
        $csv = $parser->parse_to_csv($tmp, 'xlsx');

        $this->assertNotEmpty($csv);
        $this->assertStringContainsString("Hello,Bonjour", $csv);

        @unlink($tmp);
    }

    /**
     * This method is called before each test.
     *
     * @codeCoverageIgnore
     */
    protected function setUp(): void {
        parent::setUp();

        // Remember the current Zip class, if available.
        if (method_exists(Settings::class, 'getZipClass')) {
            $this->originalzipclass = Settings::getZipClass();
        }

        // Prefer ZipArchive to avoid ZipStream API differences on older PHP.
        if (class_exists(ZipArchive::class) && defined(Settings::class . '::ZIPARCHIVE')) {
            Settings::setZipClass(Settings::ZIPARCHIVE);
        } else {
            $this->markTestSkipped('ZipArchive (ext-zip) is not available; skipping XLSX writer test.');
        }
    }

    /**
     * This method is called after each test.
     *
     * @codeCoverageIgnore
     */
    protected function tearDown(): void {
        // Restore the previous Zip class setting if we changed it.
        if ($this->originalzipclass !== null && method_exists(Settings::class, 'setZipClass')) {
            Settings::setZipClass($this->originalzipclass);
        }
        parent::tearDown();
    }
    /** @var mixed */
    private $originalzipclass = null;
}
