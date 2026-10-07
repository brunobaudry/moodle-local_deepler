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

/**
 * Unit tests for translateform class.
 *
 * @package    local_deepler
 * @category   test
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_deepler\output\translateform
 */

namespace local_deepler\output;

use advanced_testcase;
use filter_multilang2;
use local_deepler\local\data\course;
use local_deepler\local\data\multilanger;
use local_deepler\local\services\lang_helper;
use ReflectionClass;

/**
 * Test case for translateform class.
 */
final class translateform_test extends advanced_testcase {
    /**
     * Factored test string.
     */
    const string LOCAL_DEEPLER_OUTPUT_MULTILANG_2_TEXT_FILTER = 'local_deepler\\output\\Multilang2TextFilter';
    /** @var mixed */
    protected mixed $course;
    /** @var lang_helper */
    protected $langhelper;
    /** @var \filter_multilang2 */
    protected $mlangfilter;
    /** @var \stdClass */
    protected $user;

    /**
     * Set it up.
     *
     * @return void
     * @throws \DeepL\DeepLException
     * @throws \dml_exception|\PHPUnit\Framework\MockObject\Exception
     */
    public function setUp(): void {
        parent::setUp();
        global $CFG;
        require_once($CFG->dirroot . '/filter/multilang2/filter.php'); // Ensure filter_multilang2 is loaded.

        if (!class_exists(self::LOCAL_DEEPLER_OUTPUT_MULTILANG_2_TEXT_FILTER)) {
            if (class_exists('\\core_filters\\text_filter')) {
                class_alias('\\core_filters\\text_filter', self::LOCAL_DEEPLER_OUTPUT_MULTILANG_2_TEXT_FILTER);
            } else if (class_exists('\\filter_multilang2')) {
                class_alias('\\filter_multilang2', self::LOCAL_DEEPLER_OUTPUT_MULTILANG_2_TEXT_FILTER);
            }
        }
        $this->user = $this->getDataGenerator()->create_user([
                'username' => 'testuser',
                'email' => 'testuser@example.com',
        ]);
        $this->course = $this->getDataGenerator()->create_course();
        $this->mlangfilter = $this->createMock(filter_multilang2::class);
        $this->langhelper = $this->createMock(lang_helper::class);
        $this->langhelper->currentlang = 'en';
        $this->langhelper->targetlang = 'fr';
        $this->langhelper->initdeepl($this->user, 'v1.0');
    }

    /**
     * Test the definition method.
     *
     * @covers \local_deepler\output\translateform::definition
     * @return void
     * @throws \core\exception\moodle_exception
     * @throws \moodle_exception
     */
    public function test_definition(): void {
        $this->resetAfterTest(true);
        $coursedata = new course($this->course);

        // Custom data for the form.
        $customdata = [
                'coursedata' => $coursedata,
                'langpack' => $this->langhelper,
                'mlangfilter' => $this->mlangfilter,
                'editor' => $this->geteditor(),
        ];
        multilanger::$translatedfields = ["course#fullname" => ''];
        // Instantiate the form.
        $form = new translateform(null, $customdata);
        // Use reflection to access the private _mform property.
        $reflection = new ReflectionClass($form);
        $property = $reflection->getProperty('_form');
        $property->setAccessible(true);
        $mform = $property->getValue($form);

        // Check if the form is defined correctly.
        $this->assertNotEmpty($mform);
        $this->assertInstanceOf('MoodleQuickForm', $mform);

        // Check if the form elements are added correctly.
        $elements = $mform->getAttributes();
        $this->assertNotEmpty($elements);
    }

    /**
     * Non Tiny users must get the cteditor element rendered (textarea named "<key>[text]").
     *
     * @covers \local_deepler\output\row_data::export_for_template
     * @return void
     */
    public function test_render_with_non_tiny_editor(): void {
        $this->resetAfterTest(true);
        $this->setUser($this->user);
        global $PAGE;
        $PAGE->set_context(\context_course::instance($this->course->id));
        // Make sure at least one field is a rich text (HTML) one.
        $this->course->summary = '<p>Some course summary long enough to be scanned and translated.</p>';
        $this->course->summaryformat = FORMAT_HTML;
        $coursedata = new course($this->course);
        multilanger::$translatedfields = ["course#fullname" => ''];

        $tinyform = new translateform(null, [
                'coursedata' => $coursedata,
                'langpack' => $this->langhelper,
                'mlangfilter' => $this->mlangfilter,
                'editor' => 'tiny',
        ]);
        $tinyhtml = $tinyform->render();
        $this->assertStringContainsString('id="tiny_', $tinyhtml);
        $this->assertStringNotContainsString('[text]"', $tinyhtml);

        $textareaform = new translateform(null, [
                'coursedata' => $coursedata,
                'langpack' => $this->langhelper,
                'mlangfilter' => $this->mlangfilter,
                'editor' => 'textarea',
        ]);
        $textareahtml = $textareaform->render();
        $this->assertStringNotContainsString('id="tiny_', $textareahtml);
        $this->assertMatchesRegularExpression('/<textarea[^>]*name="[^"]+\[text\]"/', $textareahtml);
    }

    /**
     * Get the site editor. Either the default or users.
     *
     * @return string
     * @throws \coding_exception
     */
    private function geteditor(): string {
        global $CFG;
        $defaulteditor = strstr($CFG->texteditors, ',', true);
        return get_user_preferences()['htmleditor'] ?? $defaulteditor;
    }
}
