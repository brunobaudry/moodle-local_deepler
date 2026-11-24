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
use context_user;
use local_deepler\local\data\field;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test cases.
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/PHPUnit
 */
final class utils_test extends advanced_testcase {
    /**
     * Color index test.
     *
     * @covers \local_deepler\local\services\utils::makecolorindex
     * @return void
     */
    public function test_makecolorindex_returns_correct_colors(): void {
        $input = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
        $result = utils::makecolorindex($input);
        $this->assertCount(8, $result);
        $this->assertEquals('a', $result[0]['key']);
        $this->assertEquals('FloralWhite', $result[0]['value']);
        $this->assertEquals('Ivory', $result[7]['value']); // Last color in list.
    }

    /**
     * Test make HTML id.
     *
     * @covers \local_deepler\local\services\utils::makehtmlid
     * @return void
     */
    public function test_makehtmlid_transforms_text_correctly(): void {
        $input = 'Hello World_123!';
        $expected = 'hello-world-123';
        $this->assertEquals($expected, utils::makehtmlid($input));
    }

    /**
     * Test make HTML id.
     *
     * @covers \local_deepler\local\services\utils::makehtmlid
     * @return void
     */
    public function test_makehtmlid_prefixes_digit_start(): void {
        $input = '123abc';
        $expected = 'id-123abc';
        $this->assertEquals($expected, utils::makehtmlid($input));
    }

    /**
     * Test wildcard_match.
     *
     * @covers \local_deepler\local\services\utils::wildcard_match
     * @return void
     */
    public function test_wildcard_match_basic_patterns(): void {
        $this->assertTrue(utils::wildcard_match('he_lo', 'hello'));
        $this->assertTrue(utils::wildcard_match('he%', 'hello'));
        $this->assertFalse(utils::wildcard_match('hi%', 'hello'));
    }

    /**
     * Test standard_user_fields.
     *
     * @covers \local_deepler\local\services\utils::standard_user_fields
     * @return void
     * @throws \coding_exception
     */
    public function test_standard_user_fields_contains_expected_keys(): void {
        $fields = utils::standard_user_fields();
        $expectedkeys =
            ['username', 'email', 'firstname', 'lastname', 'city', 'country', 'institution', 'department', 'phone1', 'phone2',
                'address', 'idnumber'];
        foreach ($expectedkeys as $key) {
            $this->assertArrayHasKey($key, $fields);
        }
    }

    /**
     * Test get_plugin_root.
     *
     * @covers \local_deepler\local\services\utils::get_plugin_root
     * @return void
     */
    public function test_get_plugin_root_returns_valid_path(): void {
        $path = utils::get_plugin_root();
        $this->assertStringContainsString('local/deepler', $path);
        $this->assertDirectoryExists($path);
    }

    /**
     * Test get user fields.
     *
     * @covers \local_deepler\local\services\utils::all_user_fields
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_all_user_fields_returns_combined_fields(): void {
        global $DB;

        $this->resetAfterTest(true);

        // Create a test user context.
        $user = $this->getDataGenerator()->create_user();
        $context = context_user::instance($user->id);

        // Insert a mock custom profile field.
        $fielddata = (object) [
            'shortname' => 'customfield1',
            'name' => 'Custom Field One',
            'datatype' => 'text',
        ];
        $fielddata->id = $DB->insert_record('user_info_field', $fielddata);

        // Call the method.
        $fields = utils::all_user_fields($context);

        // Check standard fields.
        $this->assertArrayHasKey('username', $fields);
        $this->assertArrayHasKey('email', $fields);

        // Check custom field.
        $this->assertArrayHasKey('profile_field_customfield1', $fields);
        $this->assertEquals('Custom Field One', $fields['profile_field_customfield1']);
    }

    /**
     * Data provider for {@see test_pluginfile_url_itemid()}.
     *
     * @return array
     */
    public static function pluginfile_url_itemid_provider(): array {
        return [
            'module intro' => ['mod_page', 'intro', 0, null],
            'module intro, any itemid' => ['mod_forum', 'intro', 12, null],
            'course summary' => ['course', 'summary', 0, null],
            'course overviewfiles' => ['course', 'overviewfiles', 0, null],
            'course section keeps section id' => ['course', 'section', 42, 42],
            'page content keeps revision' => ['mod_page', 'content', 0, 0],
            'resource content keeps itemid' => ['mod_resource', 'content', 3, 3],
            'null stays null' => ['mod_book', 'chapter', null, null],
            'workshop instructauthors' => ['mod_workshop', 'instructauthors', 0, null],
            'workshop instructreviewers' => ['mod_workshop', 'instructreviewers', 0, null],
            'workshop conclusion' => ['mod_workshop', 'conclusion', 0, null],
            'assign activityattachment keeps itemid' => ['mod_assign', 'activityattachment', 0, 0],
        ];
    }

    /**
     * Test which file areas get an itemid in their pluginfile URL.
     *
     * @dataProvider pluginfile_url_itemid_provider
     * @covers \local_deepler\local\services\utils::pluginfile_url_itemid
     * @param string $component
     * @param string $filearea
     * @param int|null $itemid
     * @param int|null $expected
     * @return void
     */
    public function test_pluginfile_url_itemid(string $component, string $filearea, ?int $itemid, ?int $expected): void {
        $this->assertSame($expected, utils::pluginfile_url_itemid($component, $filearea, $itemid));
    }

    /**
     * Module intro: the URL must not contain an itemid, with or without stored files.
     *
     * @covers \local_deepler\local\services\utils::resolve_pluginfiles
     * @return void
     */
    public function test_resolve_pluginfiles_module_intro(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $context = context_module::instance($page->cmid);
        $text = '<p><img src="@@PLUGINFILE@@/pic.png"></p>';
        $expected = "/pluginfile.php/{$context->id}/mod_page/intro/pic.png";

        // Without stored file.
        $field = new field($page->id, $text, 1, 'intro', 'page', $page->cmid);
        $this->assertStringContainsString($expected, $field->get_displaytext());

        // With a stored file (itemid 0 in the files table).
        $this->create_test_file($context->id, 'mod_page', 'intro', 0);
        $field = new field($page->id, $text, 1, 'intro', 'page', $page->cmid);
        $this->assertStringContainsString($expected, $field->get_displaytext());
        $this->assertStringNotContainsString('/intro/0/', $field->get_displaytext());
        $this->assertStringNotContainsString('@@PLUGINFILE@@', $field->get_displaytext());
    }

    /**
     * Course section summary: files live in course/section with the section id as itemid.
     *
     * @covers \local_deepler\local\services\utils::resolve_pluginfiles
     * @return void
     */
    public function test_resolve_pluginfiles_course_section(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['numsections' => 1]);
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 1], '*', MUST_EXIST);
        $context = context_course::instance($course->id);
        $this->create_test_file($context->id, 'course', 'section', (int) $section->id);

        $text = '<p><img src="@@PLUGINFILE@@/pic.png"></p>';
        $field = new field($section->id, $text, 1, 'summary', 'course_sections');

        $this->assertStringContainsString(
            "/pluginfile.php/{$context->id}/course/section/{$section->id}/pic.png",
            $field->get_displaytext()
        );
    }

    /**
     * Course summary: no itemid in the URL.
     *
     * @covers \local_deepler\local\services\utils::resolve_pluginfiles
     * @return void
     */
    public function test_resolve_pluginfiles_course_summary(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $this->create_test_file($context->id, 'course', 'summary', 0);

        $field = new field($course->id, '<img src="@@PLUGINFILE@@/pic.png">', 1, 'summary', 'course');

        $this->assertStringContainsString(
            "/pluginfile.php/{$context->id}/course/summary/pic.png",
            $field->get_displaytext()
        );
    }

    /**
     * Page content: files are stored with itemid 0, which the URL uses as revision.
     *
     * @covers \local_deepler\local\services\utils::resolve_pluginfiles
     * @return void
     */
    public function test_resolve_pluginfiles_page_content(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $context = context_module::instance($page->cmid);
        $this->create_test_file($context->id, 'mod_page', 'content', 0);

        $field = new field($page->id, '<img src="@@PLUGINFILE@@/pic.png">', 1, 'content', 'page', $page->cmid);

        $this->assertStringContainsString(
            "/pluginfile.php/{$context->id}/mod_page/content/0/pic.png",
            $field->get_displaytext()
        );
    }

    /**
     * Text without placeholder or with a plain-text format is left untouched.
     *
     * @covers \local_deepler\local\services\utils::resolve_pluginfiles
     * @return void
     */
    public function test_resolve_pluginfiles_leaves_other_text_untouched(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);

        $field = new field($page->id, '<p>No files here</p>', 1, 'content', 'page', $page->cmid);
        $this->assertSame('<p>No files here</p>', $field->get_displaytext());

        $field = new field($page->id, '@@PLUGINFILE@@/pic.png', 0, 'content', 'page', $page->cmid);
        $this->assertSame('@@PLUGINFILE@@/pic.png', $field->get_displaytext());
    }

    /**
     * Module sub-tables and remapped main-record columns resolve to the areas core stores files in.
     *
     * @covers \local_deepler\local\services\utils::get_context_info
     * @return void
     */
    public function test_get_context_info_modules(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        $book = $generator->create_module('book', ['course' => $course->id]);
        $chapter = $generator->get_plugin_generator('mod_book')->create_chapter(['bookid' => $book->id]);
        $info = utils::get_context_info('book_chapters', (int) $chapter->id, (int) $book->cmid, 'content');
        $this->assert_context_info(context_module::instance($book->cmid)->id, 'mod_book', 'chapter', (int) $chapter->id, $info);

        $forum = $generator->create_module('forum', ['course' => $course->id]);
        $user = $generator->create_user();
        $discussion = $generator->get_plugin_generator('mod_forum')->create_discussion(
            ['course' => $course->id, 'forum' => $forum->id, 'userid' => $user->id]
        );
        $info = utils::get_context_info('forum_posts', (int) $discussion->firstpost, (int) $forum->cmid, 'message');
        $this->assert_context_info(
            context_module::instance($forum->cmid)->id,
            'mod_forum',
            'post',
            (int) $discussion->firstpost,
            $info
        );

        $this->setAdminUser();
        $wiki = $generator->create_module('wiki', ['course' => $course->id]);
        $page = $generator->get_plugin_generator('mod_wiki')->create_first_page($wiki);
        $info = utils::get_context_info('wiki_pages', (int) $page->id, (int) $wiki->cmid, 'cachedcontent');
        $this->assert_context_info(
            context_module::instance($wiki->cmid)->id,
            'mod_wiki',
            'attachments',
            (int) $page->subwikiid,
            $info
        );

        $assign = $generator->create_module('assign', ['course' => $course->id]);
        $info = utils::get_context_info('assign', (int) $assign->id, (int) $assign->cmid, 'activity');
        $this->assert_context_info(context_module::instance($assign->cmid)->id, 'mod_assign', 'activityattachment', 0, $info);
        $info = utils::get_context_info('assign', (int) $assign->id, (int) $assign->cmid, 'intro');
        $this->assert_context_info(context_module::instance($assign->cmid)->id, 'mod_assign', 'intro', 0, $info);
    }

    /**
     * Question data lives in the question bank category context, with the areas core stores files in.
     *
     * @covers \local_deepler\local\services\utils::get_context_info
     * @return void
     */
    public function test_get_context_info_questions(): void {
        global $DB;
        $this->resetAfterTest();
        /** @var \core_question_generator $qgen */
        $qgen = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $qgen->create_question_category();
        $ctxid = (int) $cat->contextid;

        $mc = $qgen->create_question('multichoice', 'one_of_four', ['category' => $cat->id]);
        $info = utils::get_context_info('question', (int) $mc->id, 0, 'questiontext');
        $this->assert_context_info($ctxid, 'question', 'questiontext', (int) $mc->id, $info);

        $answer = $DB->get_records('question_answers', ['question' => $mc->id], 'id', '*', 0, 1);
        $answer = reset($answer);
        $info = utils::get_context_info('question_answers', (int) $answer->id, 0, 'feedback');
        $this->assert_context_info($ctxid, 'question', 'answerfeedback', (int) $answer->id, $info);

        $options = $DB->get_record('qtype_multichoice_options', ['questionid' => $mc->id], '*', MUST_EXIST);
        $info = utils::get_context_info('qtype_multichoice_options', (int) $options->id, 0, 'correctfeedback');
        $this->assert_context_info($ctxid, 'question', 'correctfeedback', (int) $mc->id, $info);

        $match = $qgen->create_question('match', 'foursubq', ['category' => $cat->id]);
        $subq = $DB->get_records('qtype_match_subquestions', ['questionid' => $match->id], 'id', '*', 0, 1);
        $subq = reset($subq);
        $info = utils::get_context_info('qtype_match_subquestions', (int) $subq->id, 0, 'questiontext');
        $this->assert_context_info($ctxid, 'qtype_match', 'subquestion', (int) $subq->id, $info);

        $essay = $qgen->create_question('essay', 'editor', ['category' => $cat->id]);
        $essayoptions = $DB->get_record('qtype_essay_options', ['questionid' => $essay->id], '*', MUST_EXIST);
        $info = utils::get_context_info('qtype_essay_options', (int) $essayoptions->id, 0, 'graderinfo');
        $this->assert_context_info($ctxid, 'qtype_essay', 'graderinfo', (int) $essay->id, $info);
    }

    /**
     * A failed context lookup (here a module field without cmid) leaves the text unchanged.
     *
     * @covers \local_deepler\local\services\utils::resolve_pluginfiles
     * @return void
     */
    public function test_resolve_pluginfiles_failed_lookup_returns_text(): void {
        $this->resetAfterTest();
        $text = '<img src="@@PLUGINFILE@@/pic.png">';
        $field = new field(1, $text, 1, 'content', 'page', 0);
        $this->assertSame($text, $field->get_displaytext());
    }

    /**
     * Question text is rewritten to a local_deepler preview URL, with the course as preview context.
     *
     * @covers \local_deepler\local\services\utils::resolve_pluginfiles
     * @return void
     */
    public function test_resolve_pluginfiles_question_preview_url(): void {
        $this->resetAfterTest();
        [$course, $quiz, $question, $filecontextid] = $this->create_quiz_question_with_file();
        $coursecontext = context_course::instance($course->id);

        $field = new field($question->id, '<img src="@@PLUGINFILE@@/pic.png">', 1, 'questiontext', 'question', $quiz->cmid);

        $this->assertStringContainsString(
            "/pluginfile.php/{$filecontextid}/question/questiontext/preview/{$coursecontext->id}/local_deepler/"
                . "{$question->id}/{$question->id}/pic.png",
            $field->get_displaytext()
        );
    }

    /**
     * The preview file lookup serves the question's own files to translators only.
     *
     * @covers \local_deepler\local\services\utils::get_question_preview_file
     * @return void
     */
    public function test_get_question_preview_file(): void {
        $this->resetAfterTest();
        [$course, , $question, $filecontextid] = $this->create_quiz_question_with_file();
        $coursecontext = context_course::instance($course->id);
        $filecontext = \core\context::instance_by_id($filecontextid);
        $args = [$question->id, 'pic.png'];

        $this->setAdminUser();
        $file = utils::get_question_preview_file($coursecontext, $question->id, $filecontext, 'question', 'questiontext', $args);
        $this->assertInstanceOf(\stored_file::class, $file);
        $this->assertSame('pic.png', $file->get_filename());

        // Missing file, foreign component, foreign context.
        $this->assertNull(utils::get_question_preview_file(
            $coursecontext,
            $question->id,
            $filecontext,
            'question',
            'questiontext',
            [$question->id, 'nope.png']
        ));
        $this->assertNull(utils::get_question_preview_file(
            $coursecontext,
            $question->id,
            $filecontext,
            'mod_page',
            'questiontext',
            $args
        ));
        $this->assertNull(utils::get_question_preview_file(
            $coursecontext,
            $question->id,
            $coursecontext,
            'question',
            'questiontext',
            $args
        ));

        // A student cannot use it.
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $this->expectException(\required_capability_exception::class);
        utils::get_question_preview_file($coursecontext, $question->id, $filecontext, 'question', 'questiontext', $args);
    }

    /**
     * Create a quiz with a question whose question text embeds a stored file.
     *
     * @return array [course, quiz, question, question file context id]
     */
    private function create_quiz_question_with_file(): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $quiz = $generator->create_module('quiz', ['course' => $course->id]);
        /** @var \core_question_generator $qgen */
        $qgen = $generator->get_plugin_generator('core_question');
        $cat = $qgen->create_question_category();
        $question = $qgen->create_question('multichoice', 'one_of_four', ['category' => $cat->id]);
        $this->create_test_file((int) $cat->contextid, 'question', 'questiontext', (int) $question->id);
        return [$course, $quiz, $question, (int) $cat->contextid];
    }

    /**
     * Assert the storage part of a get_context_info() result.
     *
     * @param int $contextid
     * @param string $component
     * @param string $filearea
     * @param int $itemid
     * @param array $info
     * @return void
     */
    private function assert_context_info(int $contextid, string $component, string $filearea, int $itemid, array $info): void {
        $this->assertSame(
            ['contextid' => $contextid, 'component' => $component, 'filearea' => $filearea, 'itemid' => $itemid],
            array_intersect_key($info, array_flip(['contextid', 'component', 'filearea', 'itemid']))
        );
    }

    /**
     * Store a small image file for the tests.
     *
     * @param int $contextid
     * @param string $component
     * @param string $filearea
     * @param int $itemid
     * @return void
     */
    private function create_test_file(int $contextid, string $component, string $filearea, int $itemid): void {
        get_file_storage()->create_file_from_string([
            'contextid' => $contextid,
            'component' => $component,
            'filearea' => $filearea,
            'itemid' => $itemid,
            'filepath' => '/',
            'filename' => 'pic.png',
        ], 'not really a png');
    }
}
