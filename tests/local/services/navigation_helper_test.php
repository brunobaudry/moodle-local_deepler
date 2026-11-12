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
use core\hook\navigation\secondary_extend;
use core\navigation\views\secondary;
use moodle_page;
use navigation_node;
use settings_navigation;

/**
 * Unit tests for navigation_helper.
 *
 * @package    local_deepler
 * @copyright  2026 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class navigation_helper_test extends advanced_testcase {
    /**
     * Set up tests.
     */
    protected function setUp(): void {
        global $PAGE;
        parent::setUp();
        $this->resetAfterTest(true);
        $PAGE = new moodle_page();
    }

    /**
     * Test get_translate_url URL construction.
     *
     * @covers \local_deepler\local\services\navigation_helper::get_translate_url
     */
    public function test_get_translate_url(): void {
        // Course level URL.
        $urlcourse = navigation_helper::get_translate_url(2, -99, -1, 'en');
        $this->assertStringContainsString('/local/deepler/translate.php', $urlcourse->get_path());
        $this->assertEquals(2, $urlcourse->get_param('courseid'));
        $this->assertEquals('en', $urlcourse->get_param('lang'));
        $this->assertNull($urlcourse->get_param('section_id'));
        $this->assertNull($urlcourse->get_param('activity_id'));

        // Section level URL.
        $urlsection = navigation_helper::get_translate_url(2, 105, -1, 'fr');
        $this->assertEquals(2, $urlsection->get_param('courseid'));
        $this->assertEquals('fr', $urlsection->get_param('lang'));
        $this->assertEquals(105, $urlsection->get_param('section_id'));
        $this->assertNull($urlsection->get_param('activity_id'));

        // Activity level URL.
        $urlactivity = navigation_helper::get_translate_url(2, 105, 42, 'de');
        $this->assertEquals(2, $urlactivity->get_param('courseid'));
        $this->assertEquals('de', $urlactivity->get_param('lang'));
        $this->assertEquals(105, $urlactivity->get_param('section_id'));
        $this->assertEquals(42, $urlactivity->get_param('activity_id'));
    }

    /**
     * Test capability check.
     *
     * @covers \local_deepler\local\services\navigation_helper::can_translate_context
     */
    public function test_can_translate_context(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);

        $manager = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();

        $managerrole = $DB->get_record('role', ['shortname' => 'manager']);
        role_assign($managerrole->id, $manager->id, $context->id);

        // Manager should have capability.
        $this->setUser($manager);
        $this->assertTrue(navigation_helper::can_translate_context($context));

        // Student should not have capability.
        $this->setUser($student);
        $this->assertFalse(navigation_helper::can_translate_context($context));
    }

    /**
     * Test extend_settings_navigation for activity/module.
     *
     * @covers \local_deepler\local\services\navigation_helper::extend_settings_navigation
     * @covers ::local_deepler_extend_settings_navigation
     */
    public function test_extend_settings_navigation_activity(): void {
        global $PAGE, $CFG, $DB;

        require_once($CFG->dirroot . '/local/deepler/lib.php');

        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('page', $page->id, $course->id);
        $context = context_module::instance($cm->id);

        $manager = $this->getDataGenerator()->create_user();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager']);
        role_assign($managerrole->id, $manager->id, context_course::instance($course->id)->id);
        $this->setUser($manager);

        $PAGE->set_course($course);
        $PAGE->set_cm($cm);
        $PAGE->set_context($context);
        $PAGE->set_url(new \moodle_url('/mod/page/view.php', ['id' => $cm->id]));

        $settingsnav = new settings_navigation($PAGE);
        $settingsnav->initialise();

        $node = $settingsnav->find('deepler_activity_translate', navigation_node::TYPE_SETTING);
        $this->assertNotNull($node);
        $this->assertNotFalse($node);
        $this->assertInstanceOf(navigation_node::class, $node);
        $this->assertStringContainsString('/local/deepler/translate.php', $node->action->get_path());
        $this->assertEquals($course->id, $node->action->get_param('courseid'));
        $this->assertEquals($cm->section, $node->action->get_param('section_id'));
        $this->assertEquals($cm->id, $node->action->get_param('activity_id'));

        // Test without capability (student).
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);

        $settingsnavstudent = new settings_navigation($PAGE);
        $settingsnavstudent->initialise();
        $this->assertFalse($settingsnavstudent->find('deepler_activity_translate', navigation_node::TYPE_SETTING));
    }

    /**
     * Test extend_secondary_navigation hook for section view page.
     *
     * @covers \local_deepler\local\services\navigation_helper::extend_secondary_navigation
     * @covers \local_deepler\hook\navigation_hook_callbacks::extend_secondary_navigation
     */
    public function test_extend_secondary_navigation_section(): void {
        global $PAGE;

        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course(['numsections' => 2]);
        $modinfo = get_fast_modinfo($course);
        $section = $modinfo->get_section_info(1);

        $PAGE->set_course($course);
        $PAGE->set_context(context_course::instance($course->id));
        $PAGE->set_url('/course/section.php', ['id' => $section->id]);

        $this->assertGreaterThan(0, $section->id);
        $this->assertEquals(CONTEXT_COURSE, $PAGE->context->contextlevel);
        $this->assertEquals($course->id, $PAGE->course->id);
        $this->assertTrue(navigation_helper::can_translate_context($PAGE->context));
        $this->assertEquals($section->id, navigation_helper::detect_current_section_id());

        $secondaryview = new secondary($PAGE);
        $hook = new secondary_extend($secondaryview);

        navigation_helper::extend_secondary_navigation($hook);

        $this->assertEquals(1, $secondaryview->children->count());
        $node = $secondaryview->find('deepler_section_translate', navigation_node::TYPE_CUSTOM);
        $this->assertNotNull($node);
        $this->assertNotFalse($node);
        $this->assertInstanceOf(navigation_node::class, $node);
        $this->assertStringContainsString('/local/deepler/translate.php', $node->action->get_path());
        $this->assertEquals($course->id, $node->action->get_param('courseid'));
        $this->assertEquals($section->id, $node->action->get_param('section_id'));
    }

    /**
     * Test that secondary navigation has only one translate link when settingsnav is also populated.
     *
     * @covers \local_deepler\local\services\navigation_helper::extend_secondary_navigation
     * @covers \local_deepler\local\services\navigation_helper::extend_settings_navigation
     */
    public function test_secondary_navigation_no_duplicate_on_section(): void {
        global $PAGE, $CFG;
        require_once($CFG->dirroot . '/local/deepler/lib.php');

        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course(['numsections' => 2]);
        $modinfo = get_fast_modinfo($course);
        $section = $modinfo->get_section_info(1);

        $PAGE->set_course($course);
        $PAGE->set_context(context_course::instance($course->id));
        $PAGE->set_url('/course/editsection.php', ['id' => $section->id]);

        $secondaryview = new secondary($PAGE);
        $secondaryview->initialise();

        // Count how many deepler/translate nodes exist in secondary navigation.
        $count = 0;
        foreach ($secondaryview->children as $child) {
            if ($child->key === 'deepler_section_translate' || $child->key === 'translate') {
                $count++;
            }
        }
        $this->assertEquals(1, $count);
    }

    /**
     * Test extend_settings_navigation for section settings page.
     *
     * @covers \local_deepler\local\services\navigation_helper::extend_settings_navigation
     * @covers \local_deepler\local\services\navigation_helper::detect_current_section_id
     * @covers ::local_deepler_extend_settings_navigation
     */
    public function test_extend_settings_navigation_section_edit(): void {
        global $PAGE, $CFG;

        require_once($CFG->dirroot . '/local/deepler/lib.php');

        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course(['numsections' => 2]);
        $modinfo = get_fast_modinfo($course);
        $section = $modinfo->get_section_info(1);

        $PAGE->set_course($course);
        $PAGE->set_context(context_course::instance($course->id));
        $PAGE->set_url('/course/editsection.php', ['id' => $section->id]);

        $this->assertEquals($section->id, navigation_helper::detect_current_section_id());

        $settingsnav = new settings_navigation($PAGE);
        $settingsnav->initialise();

        $node = $settingsnav->find('translate', navigation_node::TYPE_CUSTOM)
            ?: $settingsnav->find('deepler_section_translate', navigation_node::TYPE_SETTING);
        $this->assertNotNull($node);
        $this->assertNotFalse($node);
        $this->assertInstanceOf(navigation_node::class, $node);
        $this->assertStringContainsString('/local/deepler/translate.php', $node->action->get_path());
        $this->assertEquals($course->id, $node->action->get_param('courseid'));
        $this->assertEquals($section->id, $node->action->get_param('section_id'));
    }

    /**
     * Test extend_navigation_course.
     *
     * @covers \local_deepler\local\services\navigation_helper::extend_navigation_course
     * @covers ::local_deepler_extend_navigation_course
     */
    public function test_extend_navigation_course(): void {
        global $PAGE, $CFG, $DB;
        require_once($CFG->dirroot . '/local/deepler/lib.php');

        $course = $this->getDataGenerator()->create_course(['numsections' => 2]);
        $modinfo = get_fast_modinfo($course);
        $section = $modinfo->get_section_info(1);

        $manager = $this->getDataGenerator()->create_user();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager']);
        role_assign($managerrole->id, $manager->id, context_course::instance($course->id)->id);
        $this->setUser($manager);

        // 1. General course view without specific section.
        $PAGE->set_course($course);
        $PAGE->set_context(context_course::instance($course->id));
        $PAGE->set_url('/course/view.php', ['id' => $course->id]);

        $nav = new navigation_node('root');
        local_deepler_extend_navigation_course($nav, $course);

        $node = $nav->find('translate', navigation_node::TYPE_CUSTOM);
        $this->assertNotNull($node);
        $this->assertNotFalse($node);
        $this->assertInstanceOf(navigation_node::class, $node);
        $this->assertStringContainsString('/local/deepler/translate.php', $node->action->get_path());
        $this->assertEquals($course->id, $node->action->get_param('courseid'));
        $this->assertNull($node->action->get_param('section_id'));

        // 2. On section edit page.
        $PAGE->set_url('/course/editsection.php', ['id' => $section->id]);
        $navsection = new navigation_node('root');
        local_deepler_extend_navigation_course($navsection, $course);

        $nodesection = $navsection->find('translate', navigation_node::TYPE_CUSTOM);
        $this->assertNotNull($nodesection);
        $this->assertEquals($course->id, $nodesection->action->get_param('courseid'));
        $this->assertEquals($section->id, $nodesection->action->get_param('section_id'));
    }
}
