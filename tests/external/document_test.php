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
use local_deepler\local\services\file_scanner;
use local_deepler\local\services\lang_helper;
use local_deepler\output\files_page;

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
        $uploadparams = upload_document::execute_parameters();
        $this->assertInstanceOf(external_function_parameters::class, $uploadparams);
        $this->assertArrayHasKey('options', $uploadparams->keys);
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

    /**
     * Test files_page export_for_template with multilang filter and options.
     */
    public function test_files_page_export_multilang_and_options(): void {
        global $PAGE, $DB;
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        // Create course with multilang tags.
        $course = $generator->create_course([
            'fullname' => '{mlang other}Course English{mlang}{mlang fr}Cours Francais{mlang}',
        ]);

        $resource = $generator->create_module('resource', [
            'course' => $course->id,
            'name' => '{mlang other}Handout En{mlang}{mlang fr}Support Fr{mlang}',
        ]);
        $cmresource = get_coursemodule_from_instance('resource', $resource->id, $course->id);
        $contextres = context_module::instance($cmresource->id);

        $fs = get_file_storage();
        $file = $fs->create_file_from_string([
            'contextid' => $contextres->id,
            'component' => 'mod_resource',
            'filearea' => 'content',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'handout.docx',
        ], 'Handout DOCX Content');

        // Insert a completed translation job for this file.
        $DB->insert_record('local_deepler_files', [
            'courseid' => $course->id,
            'cmid' => $cmresource->id,
            'fileid' => $file->get_id(),
            'contenthash' => $file->get_contenthash(),
            'sourcelang' => 'en',
            'targetlang' => 'fr',
            'status' => 'done',
            'documentid' => 'doc_done_1',
            'documentkey' => 'key_done_1',
            'newcmid' => 999,
            'billedcharacters' => 500,
            'userid' => 2,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $scanner = new file_scanner($course);
        $files = $scanner->get_course_files();

        $mockclient = $this->createMock(\DeepL\DeepLClient::class);
        $mockusage = $this->createMock(\DeepL\Usage::class);
        $mockusage->method('anyLimitReached')->willReturn(false);
        $mockclient->method('getUsage')->willReturn($mockusage);
        $mockclient->method('getSourceLanguages')->willReturn([
            (object) ['code' => 'EN', 'name' => 'English'],
            (object) ['code' => 'FR', 'name' => 'French'],
        ]);
        $mockclient->method('getTargetLanguages')->willReturn([
            (object) ['code' => 'DE', 'name' => 'German'],
            (object) ['code' => 'FR', 'name' => 'French'],
        ]);

        $langpack = new lang_helper(
            $mockclient,
            'mockkey',
            ['en' => 'English', 'fr' => 'French', 'de' => 'German'],
            'en',
            'fr'
        );
        $langpack->initdeepl(get_admin(), 'v1.10.0');

        $page = new files_page($course, $files, $langpack, 'v1.10.0');
        $renderer = $PAGE->get_renderer('core');
        $data = $page->export_for_template($renderer);

        $this->assertTrue($data->hasfiles);
        // Verify multilang tags are filtered out and not present in exported names.
        $this->assertStringNotContainsString('{mlang', $data->coursename);
        $this->assertNotEmpty($data->sourcelangs);
        $this->assertNotEmpty($data->targetlangs);
        $this->assertNotEmpty($data->sections);

        $section = $data->sections[0];
        $this->assertNotEmpty($section['rows']);
        $row = $section['rows'][0];
        $this->assertStringNotContainsString('{mlang', $row['activityname']);
        $this->assertTrue($row['hastranslations']);
        $this->assertEquals(1, $row['translationscount']);
        $this->assertEquals('FR', $row['translations'][0]['targetlang']);
        $this->assertEquals('done', $row['translations'][0]['status']);
    }
}
