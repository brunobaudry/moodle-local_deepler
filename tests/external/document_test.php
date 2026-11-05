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

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/base_external.php');

use context_course;
use context_module;
use core_external\external_function_parameters;
use core_external\external_single_structure;

/**
 * PHPUnit tests for document translation external services.
 *
 * @package    local_deepler
 * @category   test
 * @copyright  2026 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class document_test extends base_external {
    /**
     * Test parameter and return definitions for the 3 externals.
     */
    public function test_external_definitions(): void {
        $this->assertInstanceOf(external_function_parameters::class, upload_document::execute_parameters());
        $this->assertInstanceOf(external_single_structure::class, upload_document::execute_returns());

        $this->assertInstanceOf(external_function_parameters::class, get_document_status::execute_parameters());
        $this->assertInstanceOf(external_single_structure::class, get_document_status::execute_returns());

        $this->assertInstanceOf(external_function_parameters::class, finalize_document::execute_parameters());
        $this->assertInstanceOf(external_single_structure::class, finalize_document::execute_returns());
    }

    /**
     * Test upload_document capability enforcement and unsupported file rejection.
     */
    public function test_upload_document_validation(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id, 'student');
        $this->setUser($user);

        $fs = get_file_storage();
        $coursecontext = context_course::instance($course->id);
        $file = $fs->create_file_from_string([
            'contextid' => $coursecontext->id,
            'component' => 'course',
            'filearea' => 'overviewfiles',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'doc.pdf',
        ], 'PDF content');

        // Student lacks translatefiles capability.
        $this->expectException(\required_capability_exception::class);
        upload_document::execute($file->get_id(), 'en', 'fr', [], 'v1.10.0');
    }

    /**
     * Test upload_document with unsupported file extension.
     */
    public function test_upload_document_unsupported_file(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $coursecontext = context_course::instance($course->id);

        $fs = get_file_storage();
        $zipfile = $fs->create_file_from_string([
            'contextid' => $coursecontext->id,
            'component' => 'course',
            'filearea' => 'overviewfiles',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'archive.zip',
        ], 'ZIP content');

        $result = upload_document::execute($zipfile->get_id(), 'en', 'fr', [], 'v1.10.0');
        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('not supported', $result['error']);
    }

    /**
     * Test get_document_status when job is done.
     */
    public function test_get_document_status_done(): void {
        global $DB;
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        $jobid = $DB->insert_record('local_deepler_files', [
            'courseid' => $course->id,
            'cmid' => 0,
            'fileid' => 123,
            'contenthash' => 'hash',
            'sourcelang' => 'en',
            'targetlang' => 'de',
            'status' => 'done',
            'documentid' => 'doc123',
            'documentkey' => 'key123',
            'billedcharacters' => 250,
            'userid' => 2,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $status = get_document_status::execute($jobid, 'v1.10.0');
        $this->assertEquals('done', $status['status']);
        $this->assertEquals(250, $status['billedcharacters']);
    }
}
