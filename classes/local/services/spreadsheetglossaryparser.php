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

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

/**
 * Reads CSV/TSV/XLSX/XLS/ODS and returns a UTF-8 CSV string with two columns (source, target).
 * Detects header row if the first non-empty row contains “source” and “target” (case-insensitive).
 * Otherwise, uses the first two non-empty columns.
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class spreadsheetglossaryparser {
    /** @var string[] public list of supported extensions (lowercase, without dot) */
    public static array $supportedextensions = ['csv', 'tsv', 'xlsx', 'xls', 'ods'];

    /**
     * Map the extension array to a valid mimetype string.
     *
     * @return string
     */
    public static function mapextentions_to_mimetype(): string {
        // Map extension.
        return implode(',', array_map(fn($ext) => '.' . $ext, self::$supportedextensions));
    }

    /**
     * Parse a given spreadsheet into a normalized two-column CSV (UTF-8, comma-delimited).
     * Optionally detect and consume the first row as language codes if it contains two-letter codes.
     *
     * @param string $filepath absolute path to the uploaded file
     * @param string $ext file extension (e.g., csv, tsv, xlsx, xls, ods)
     * @param bool $useheaderlang whether to interpret the first row as language codes if possible
     * @param array|null $languages output param: ['source' => 'en', 'target' => 'fr'] if detected
     * @return string                      utf-8 csv with two columns: source,target
     */
    public function parse_to_csv(string $filepath, string $ext, bool $useheaderlang = true, ?array &$languages = null): string {
        $ext = strtolower(ltrim($ext, '.'));
        if (!self::is_supported($ext)) {
            throw new RuntimeException('unsupported file type: ' . $ext);
        }

        $reader = $this->create_reader($ext);
        $spreadsheet = $reader->load($filepath);
        $sheet = $spreadsheet->getSheet(0);

        $languages = null;
        $rows = $this->read_sheet_rows($sheet);
        $startindex = $this->detect_header($rows, $useheaderlang, $languages);

        return $this->rows_to_csv($rows, $startindex);
    }

    /**
     * Read and collect non-empty rows from the worksheet.
     *
     * @param Worksheet $sheet
     * @return array
     */
    private function read_sheet_rows(Worksheet $sheet): array {
        $rows = [];
        $maxrow = $sheet->getHighestDataRow();
        $maxcol = $sheet->getHighestDataColumn();
        $maxcolindex = Coordinate::columnIndexFromString($maxcol);

        for ($r = 1; $r <= $maxrow; $r++) {
            $row = $this->read_row_cells($sheet, $r, $maxcolindex);
            if ($this->row_has_data($row)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Read and normalize cells for a single row index.
     *
     * @param Worksheet $sheet
     * @param int $rowindex
     * @param int $maxcolindex
     * @return array
     */
    private function read_row_cells(Worksheet $sheet, int $rowindex, int $maxcolindex): array {
        $row = [];
        for ($c = 1; $c <= $maxcolindex; $c++) {
            $value = $sheet->getCellByColumnAndRow($c, $rowindex)->getCalculatedValue();
            $row[] = $this->normalize_cell($value);
        }
        return $row;
    }

    /**
     * Detect header information (language header or legacy source/target header) and determine starting row index.
     *
     * @param array $rows
     * @param bool $useheaderlang
     * @param array|null $languages
     * @return int
     */
    private function detect_header(array $rows, bool $useheaderlang, ?array &$languages): int {
        if (empty($rows)) {
            return 0;
        }

        if ($useheaderlang && $this->detect_header_languages($rows[0], $languages)) {
            return 1;
        }

        if ($this->is_legacy_source_target_header($rows[0])) {
            return 1;
        }

        return 0;
    }

    /**
     * Detect two-letter language codes in the first row.
     *
     * @param array $firstrow
     * @param array|null $languages
     * @return bool
     */
    private function detect_header_languages(array $firstrow, ?array &$languages): bool {
        [$cell1, $cell2] = $this->first_two_non_empty($firstrow);
        if ($cell1 === null || $cell2 === null) {
            return false;
        }

        if ($this->is_two_letter_code($cell1) && $this->is_two_letter_code($cell2)) {
            $languages = [
                'source' => strtolower($cell1),
                'target' => strtolower($cell2),
            ];
            return true;
        }

        return false;
    }

    /**
     * Check if the first row represents a legacy 'source'/'target' header.
     *
     * @param array $firstrow
     * @return bool
     */
    private function is_legacy_source_target_header(array $firstrow): bool {
        [$cell1, $cell2] = $this->first_two_non_empty($firstrow);
        if ($cell1 === null || $cell2 === null) {
            return false;
        }

        return strtolower($cell1) === 'source' && strtolower($cell2) === 'target';
    }

    /**
     * Convert rows starting from the given index into a 2-column CSV string.
     *
     * @param array $rows
     * @param int $startindex
     * @return string
     * @throws RuntimeException
     */
    private function rows_to_csv(array $rows, int $startindex): string {
        $fh = fopen('php://temp', 'w+');
        if ($fh === false) {
            throw new RuntimeException('unable to open temp stream');
        }

        for ($i = $startindex; $i < count($rows); $i++) {
            $this->write_row_to_csv($fh, $rows[$i]);
        }

        rewind($fh);
        $csv = stream_get_contents($fh) ?: '';
        fclose($fh);

        return $csv;
    }

    /**
     * Write a single row's first two non-empty cells to the temp CSV stream.
     *
     * @param resource $fh
     * @param array $row
     * @return void
     */
    private function write_row_to_csv($fh, array $row): void {
        [$src, $tgt] = $this->first_two_non_empty($row);
        if ($this->is_empty($src) || $this->is_empty($tgt)) {
            return;
        }

        $src = $this->to_utf8($src);
        $tgt = $this->to_utf8($tgt);

        fputcsv($fh, [$src, $tgt]);
    }

    /**
     * Check if the extension is supported.
     *
     * @param string $ext
     * @return bool
     */
    public static function is_supported(string $ext): bool {
        $ext = strtolower(ltrim($ext, '.'));
        return in_array($ext, self::$supportedextensions, true);
    }

    /**
     * Create a reader.
     *
     * @param string $ext
     * @return \PhpOffice\PhpSpreadsheet\Reader\IReader
     */
    private function create_reader(string $ext): ireader {
        if ($ext === 'csv' || $ext === 'tsv') {
            $reader = new csvreader();
            if ($ext === 'tsv') {
                $reader->setDelimiter("\t");
            }
            $reader->setEnclosure('"');
            $reader->setInputEncoding('UTF-8'); // Fallback; PhpSpreadsheet will try to detect, but we normalize later.
            return $reader;
        }
        return IOFactory::createReader(ucfirst($ext));
    }

    /**
     * Normalize cell.
     *
     * @param mixed $value
     * @return string
     */
    private function normalize_cell(mixed $value): string {
        if ($value === null) {
            return '';
        }
        if (is_float($value)) {
            // Avoid locale issues, enforce consistent string.
            $value = rtrim(rtrim((string) $value, '0'), '.');
        }
        return trim((string) $value);
    }

    /**
     * Checks if row is blank.
     *
     * @param array $row
     * @return bool
     */
    private function row_has_data(array $row): bool {
        foreach ($row as $cell) {
            if (!$this->is_empty($cell)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if celle is empty.
     *
     * @param string|null $s
     * @return bool
     */
    private function is_empty(?string $s): bool {
        return $s === null || trim($s) === '';
    }

    /**
     * Checks if 1st 2 are empty.
     *
     * @param array $row
     * @return array|null[]
     */
    private function first_two_non_empty(array $row): array {
        $cells = [];
        foreach ($row as $cell) {
            if (!$this->is_empty($cell)) {
                $cells[] = $cell;
                if (count($cells) === 2) {
                    break;
                }
            }
        }
        return [$cells[0] ?? null, $cells[1] ?? null];
    }

    /**
     * Validate lang code.
     *
     * @param string $s
     * @return bool
     */
    private function is_two_letter_code(string $s): bool {
        // ISO 639-1 style, strictly two ASCII letters.
        return (bool) preg_match('/^[a-z]{2}$/i', $s);
    }

    /**
     * Converts the given string to UTF-8 encoding if it is not already in UTF-8.
     *
     * @param string $s The input string to be checked and converted.
     * @return string The string encoded in UTF-8.
     */
    private function to_utf8(string $s): string {
        // Normalize to UTF-8 if needed.
        if (!mb_detect_encoding($s, 'UTF-8', true)) {
            $s = mb_convert_encoding($s, 'UTF-8');
        }
        return $s;
    }
}
