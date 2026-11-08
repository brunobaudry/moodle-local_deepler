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
use context_course;
use context_module;

/**
 * Tests for file_scanner service.
 *
 * @package    local_deepler
 * @category   test
 * @copyright  2026 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_deepler\local\services\file_scanner
 */
final class file_scanner_test extends advanced_testcase {
    /**
     * Setup test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * Test is_supported helper.
     */
    public function test_is_supported(): void {
        $fs = get_file_storage();
        $filerecord = [
            'contextid' => context_course::instance(SITEID)->id,
            'component' => 'course',
            'filearea' => 'summary',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'document.docx',
        ];
        $file = $fs->create_file_from_string($filerecord, 'sample docx content');

        $this->assertTrue(file_scanner::is_supported($file));
        $this->assertEquals('', file_scanner::get_unsupported_reason($file));

        $ziprecord = [
            'contextid' => context_course::instance(SITEID)->id,
            'component' => 'course',
            'filearea' => 'summary',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'archive.zip',
        ];
        $zipfile = $fs->create_file_from_string($ziprecord, 'sample zip content');
        $this->assertFalse(file_scanner::is_supported($zipfile));
        $this->assertEquals('unsupported_extension', file_scanner::get_unsupported_reason($zipfile));

        // Test maxbytes limit.
        $this->assertFalse(file_scanner::is_supported($file, 5));
        $this->assertEquals('file_too_big', file_scanner::get_unsupported_reason($file, 5));
    }

    /**
     * Test scanning course files across activities and course level.
     */
    public function test_get_course_files(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['fullname' => 'Test Course Scanner']);

        // 1. Course overview file.
        $fs = get_file_storage();
        $coursecontext = context_course::instance($course->id);
        $coursefile = $fs->create_file_from_string([
            'contextid' => $coursecontext->id,
            'component' => 'course',
            'filearea' => 'overviewfiles',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'syllabus.pdf',
        ], 'PDF syllabus content');

        // 2. Resource module with docx.
        $resource = $generator->create_module('resource', ['course' => $course->id, 'name' => 'Handout Doc']);
        $cmresource = get_coursemodule_from_instance('resource', $resource->id, $course->id);
        $contextres = context_module::instance($cmresource->id);
        $fs->create_file_from_string([
            'contextid' => $contextres->id,
            'component' => 'mod_resource',
            'filearea' => 'content',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'handout.docx',
        ], 'Word handout content');

        // 3. Folder module with 2 files.
        $folder = $generator->create_module('folder', ['course' => $course->id, 'name' => 'Extra materials']);
        $cmfolder = get_coursemodule_from_instance('folder', $folder->id, $course->id);
        $contextfolder = context_module::instance($cmfolder->id);
        $fs->create_file_from_string([
            'contextid' => $contextfolder->id,
            'component' => 'mod_folder',
            'filearea' => 'content',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'guide.pptx',
        ], 'PPTX content');
        $fs->create_file_from_string([
            'contextid' => $contextfolder->id,
            'component' => 'mod_folder',
            'filearea' => 'content',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'data.zip',
        ], 'ZIP content');

        // 4. Assign module with introattachment.
        $assign = $generator->create_module('assign', ['course' => $course->id, 'name' => 'Assignment 1']);
        $cmassign = get_coursemodule_from_instance('assign', $assign->id, $course->id);
        $contextassign = context_module::instance($cmassign->id);
        $fs->create_file_from_string([
            'contextid' => $contextassign->id,
            'component' => 'mod_assign',
            'filearea' => 'introattachment',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'brief.txt',
        ], 'Text brief content');

        // Add a mock existing job in local_deepler_files.
        $DB->insert_record('local_deepler_files', [
            'courseid' => $course->id,
            'cmid' => 0,
            'fileid' => $coursefile->get_id(),
            'contenthash' => $coursefile->get_contenthash(),
            'sourcelang' => 'en',
            'targetlang' => 'fr',
            'status' => 'done',
            'newcmid' => 0,
            'newfileid' => 999,
            'billedcharacters' => 120,
            'userid' => 2,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $scanner = new file_scanner($course);
        $files = $scanner->get_course_files();

        $filenames = array_map(fn($f) => $f->file->get_filename(), $files);
        // Assert our created files are present.
        $this->assertContains('syllabus.pdf', $filenames);
        $this->assertContains('handout.docx', $filenames);
        $this->assertContains('guide.pptx', $filenames);
        $this->assertContains('data.zip', $filenames);
        $this->assertContains('brief.txt', $filenames);

        // Check the coursefile has jobs attached.
        $foundcoursefile = null;
        foreach ($files as $f) {
            if ($f->file->get_filename() === 'syllabus.pdf') {
                $foundcoursefile = $f;
                break;
            }
        }
        $this->assertNotNull($foundcoursefile);
        $this->assertTrue($foundcoursefile->supported);
        $this->assertCount(1, $foundcoursefile->jobs);
        $this->assertEquals('fr', $foundcoursefile->jobs[0]->targetlang);

        // Check unsupported zip file.
        $foundzip = null;
        foreach ($files as $f) {
            if ($f->file->get_filename() === 'data.zip') {
                $foundzip = $f;
                break;
            }
        }
        $this->assertNotNull($foundzip);
        $this->assertFalse($foundzip->supported);
        $this->assertEquals('unsupported_extension', $foundzip->reason);

        // 5. Test that translated files / duplicated activities are excluded from scanner.
        $dupresource = $generator->create_module('resource', ['course' => $course->id, 'name' => 'Handout Doc (FR)']);
        $cmdupresource = get_coursemodule_from_instance('resource', $dupresource->id, $course->id);
        $contextdupres = context_module::instance($cmdupresource->id);
        $translatedfile = $fs->create_file_from_string([
            'contextid' => $contextdupres->id,
            'component' => 'mod_resource',
            'filearea' => 'content',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'handout_fr.docx',
        ], 'Translated Word handout content');

        $DB->insert_record('local_deepler_files', [
            'courseid' => $course->id,
            'cmid' => $cmresource->id,
            'fileid' => $files[1]->file->get_id(),
            'contenthash' => $files[1]->file->get_contenthash(),
            'sourcelang' => 'en',
            'targetlang' => 'fr',
            'status' => 'done',
            'newcmid' => $cmdupresource->id,
            'newfileid' => $translatedfile->get_id(),
            'billedcharacters' => 50,
            'userid' => 2,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $filesafter = $scanner->get_course_files();
        $filenamesafter = array_map(fn($f) => $f->file->get_filename(), $filesafter);
        $this->assertNotContains('handout_fr.docx', $filenamesafter);
    }
}
