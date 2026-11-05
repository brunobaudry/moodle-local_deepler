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
 * Tests for activity_duplicator service.
 *
 * @package    local_deepler
 * @category   test
 * @copyright  2026 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class activity_duplicator_test extends advanced_testcase {
    /**
     * Set up.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * Test duplicating a resource activity with a translated file.
     */
    public function test_duplicate_with_file_resource(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['fullname' => 'Duplicator Test Course']);

        // Create original resource.
        $resource = $generator->create_module('resource', [
            'course' => $course->id,
            'name' => 'Handout English',
            'intro' => '<p>Intro English text</p>',
        ]);
        $cmresource = get_coursemodule_from_instance('resource', $resource->id, $course->id);
        $contextres = context_module::instance($cmresource->id);

        $fs = get_file_storage();
        $originalfile = $fs->create_file_from_string([
            'contextid' => $contextres->id,
            'component' => 'mod_resource',
            'filearea' => 'content',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'handout.docx',
        ], 'Original English File Content');

        // Prepare dummy translated file.
        $tempdir = make_request_directory();
        $temppath = $tempdir . '/translated.docx';
        file_put_contents($temppath, 'Translated French File Content');

        $duplicator = new activity_duplicator('v1.10.0');
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->cms[$cmresource->id];

        [$newcm, $newstoredfile, $restricted, $warning] = $duplicator->duplicate_with_file(
            $cm,
            $temppath,
            $originalfile,
            'fr',
            'en',
            'Handout French'
        );

        $this->assertNotEmpty($newcm);
        $this->assertNotEquals($cm->id, $newcm->id);
        $this->assertEquals('Handout French', $newcm->name);

        // Check new stored file in new context.
        $newcontext = context_module::instance($newcm->id);
        $this->assertEquals($newcontext->id, $newstoredfile->get_contextid());
        $this->assertEquals('handout_fr.docx', $newstoredfile->get_filename());
        $this->assertEquals('Translated French File Content', $newstoredfile->get_content());

        // Check original file is untouched.
        $reloadedorig = $fs->get_file_by_id($originalfile->get_id());
        $this->assertEquals('Original English File Content', $reloadedorig->get_content());
    }

    /**
     * Test duplicating a resource activity without providing newname appends language to copy name.
     */
    public function test_duplicate_with_file_auto_name_appends_language(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['fullname' => 'Duplicator Auto Name Course']);

        $resource = $generator->create_module('resource', [
            'course' => $course->id,
            'name' => 'Handout English',
            'intro' => '<p>Intro English text</p>',
        ]);
        $cmresource = get_coursemodule_from_instance('resource', $resource->id, $course->id);
        $contextres = context_module::instance($cmresource->id);

        $fs = get_file_storage();
        $originalfile = $fs->create_file_from_string([
            'contextid' => $contextres->id,
            'component' => 'mod_resource',
            'filearea' => 'content',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'handout.docx',
        ], 'Original English File Content');

        $tempdir = make_request_directory();
        $temppath = $tempdir . '/translated.docx';
        file_put_contents($temppath, 'Translated German File Content');

        $duplicator = new activity_duplicator('v1.10.0');
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->cms[$cmresource->id];

        [$newcm, $newstoredfile] = $duplicator->duplicate_with_file(
            $cm,
            $temppath,
            $originalfile,
            'de',
            'en'
        );

        $this->assertNotEmpty($newcm);
        $this->assertStringEndsWith('(DE)', $newcm->name);
        $this->assertEquals('handout_de.docx', $newstoredfile->get_filename());
    }

    /**
     * Test store_course_file for course-level files.
     */
    public function test_store_course_file(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $coursecontext = context_course::instance($course->id);

        $fs = get_file_storage();
        $originalfile = $fs->create_file_from_string([
            'contextid' => $coursecontext->id,
            'component' => 'course',
            'filearea' => 'overviewfiles',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'syllabus.pdf',
        ], 'English Syllabus Content');

        $tempdir = make_request_directory();
        $temppath = $tempdir . '/translated.pdf';
        file_put_contents($temppath, 'French Syllabus Content');

        $duplicator = new activity_duplicator('v1.10.0');
        $stored = $duplicator->store_course_file($originalfile, $temppath, 'fr');

        $this->assertEquals('syllabus_fr.pdf', $stored->get_filename());
        $this->assertEquals('French Syllabus Content', $stored->get_content());
        $this->assertEquals($coursecontext->id, $stored->get_contextid());
    }
}
