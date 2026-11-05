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

use context;
use context_course;
use core\hook\navigation\secondary_extend;
use moodle_url;
use navigation_node;
use settings_navigation;
use stdClass;

/**
 * Navigation helper for DeepLer translator navigation extensions.
 *
 * @package    local_deepler
 * @copyright  2026 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class navigation_helper {
    /**
     * Capability constant.
     */
    public const CAPABILITY_EDITTRANSLATIONS = 'local/deepler:edittranslations';

    /**
     * Check if the current user has capability to edit translations in the given context.
     *
     * @param context $context
     * @return bool
     */
    public static function can_translate_context(context $context): bool {
        return has_capability(self::CAPABILITY_EDITTRANSLATIONS, $context);
    }

    /**
     * Builds the translation URL for course, section, or activity.
     *
     * @param int $courseid
     * @param int $sectionid
     * @param int $activityid
     * @param string|null $lang
     * @return moodle_url
     */
    public static function get_translate_url(
        int $courseid,
        int $sectionid = -99,
        int $activityid = -1,
        ?string $lang = null
    ): moodle_url {
        $lang = $lang ?? current_language();
        $params = [
            'courseid' => $courseid,
            'lang' => $lang,
        ];
        if ($sectionid !== -99) {
            $params['section_id'] = $sectionid;
        }
        if ($activityid !== -1) {
            $params['activity_id'] = $activityid;
        }
        return new moodle_url('/local/deepler/translate.php', $params);
    }

    /**
     * Extends settings navigation for activity/resource and section pages.
     *
     * @param settings_navigation $settingsnav
     * @param context $context
     * @return void
     */
    public static function extend_settings_navigation(settings_navigation $settingsnav, context $context): void {
        global $PAGE;

        if (!self::can_translate_context($context)) {
            return;
        }

        // 1. Module / Activity / Resource context.
        if ($context->contextlevel === CONTEXT_MODULE) {
            $cm = $PAGE->cm ?? null;
            if (!$cm) {
                return;
            }

            $url = self::get_translate_url((int)$cm->course, (int)$cm->section, (int)$cm->id);
            $title = get_string('pluginname', 'local_deepler');
            $node = navigation_node::create(
                $title,
                $url,
                navigation_node::TYPE_SETTING,
                $title,
                'deepler_activity_translate'
            );

            $modulesettings = $settingsnav->find('modulesettings', navigation_node::TYPE_SETTING);
            if ($modulesettings) {
                $modulesettings->add_node($node);
            } else {
                $settingsnav->add_node($node);
            }
            return;
        }

        // 2. Course context on single section view page.
        if ($context->contextlevel === CONTEXT_COURSE && $PAGE->course && $PAGE->course->id > SITEID) {
            $sectionid = self::detect_current_section_id();
            if ($sectionid > 0) {
                // If courseadmin already has the 'translate' node from extend_navigation_course,
                // update its action to point to the section instead of adding a duplicate node.
                $courseadmin = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE);
                $url = self::get_translate_url((int)$PAGE->course->id, $sectionid);
                $existingnode = $courseadmin ? $courseadmin->get('translate') : null;
                if ($existingnode) {
                    $existingnode->action = $url;
                } else if (!$settingsnav->find('deepler_section_translate', navigation_node::TYPE_SETTING)) {
                    $title = get_string('pluginname', 'local_deepler');
                    $node = navigation_node::create(
                        $title,
                        $url,
                        navigation_node::TYPE_SETTING,
                        $title,
                        'deepler_section_translate'
                    );
                    if ($courseadmin) {
                        $courseadmin->add_node($node);
                    } else {
                        $settingsnav->add_node($node);
                    }
                }
            }
        }
    }

    /**
     * Extends secondary navigation via modern secondary_extend hook.
     *
     * @param secondary_extend $hook
     * @return void
     */
    public static function extend_secondary_navigation(secondary_extend $hook): void {
        global $PAGE;

        $context = $PAGE->context ?? null;
        if (!$context || !self::can_translate_context($context)) {
            return;
        }

        $secondaryview = $hook->get_secondaryview();
        $title = get_string('pluginname', 'local_deepler');

        // 1. Inside an activity / resource.
        if ($context->contextlevel === CONTEXT_MODULE && $PAGE->cm) {
            $cm = $PAGE->cm;
            $url = self::get_translate_url((int)$cm->course, (int)$cm->section, (int)$cm->id);
            $hasnode = $secondaryview->get('deepler_activity_translate')
                || $secondaryview->find('deepler_activity_translate', navigation_node::TYPE_SETTING)
                || $secondaryview->find('deepler_activity_translate', navigation_node::TYPE_CUSTOM);
            if (!$hasnode) {
                $node = navigation_node::create(
                    $title,
                    $url,
                    navigation_node::TYPE_CUSTOM,
                    $title,
                    'deepler_activity_translate'
                );
                $secondaryview->add_node($node);
            }
            return;
        }

        // 2. On a section view page.
        if ($context->contextlevel === CONTEXT_COURSE && $PAGE->course && $PAGE->course->id > SITEID) {
            $sectionid = self::detect_current_section_id();
            if ($sectionid > 0) {
                $url = self::get_translate_url((int)$PAGE->course->id, $sectionid);
                $hasnode = $secondaryview->get('translate')
                    || $secondaryview->get('deepler_section_translate')
                    || $secondaryview->find('deepler_section_translate', navigation_node::TYPE_SETTING)
                    || $secondaryview->find('deepler_section_translate', navigation_node::TYPE_CUSTOM)
                    || $secondaryview->find('translate', navigation_node::TYPE_SETTING)
                    || $secondaryview->find('translate', navigation_node::TYPE_CUSTOM);
                if (!$hasnode) {
                    $node = navigation_node::create(
                        $title,
                        $url,
                        navigation_node::TYPE_CUSTOM,
                        $title,
                        'deepler_section_translate'
                    );
                    $secondaryview->add_node($node);
                }
            }
        }
    }

    /**
     * Detects if the current page is viewing or editing a specific course section and returns its section ID.
     *
     * @return int Section ID or 0 if not on a single section view or edit page.
     */
    public static function detect_current_section_id(): int {
        global $PAGE;

        $sectionid = 0;
        $path = $PAGE->url ? $PAGE->url->get_path() : '';

        // 1. Direct section view or edit section pages where 'id' parameter is the section ID.
        if (
            $path && (
            strpos($path, '/course/section.php') !== false ||
            strpos($path, '/course/editsection.php') !== false)
        ) {
            $sectionid = (int)($PAGE->url->get_param('id') ?? optional_param('id', 0, PARAM_INT));
        }

        // 2. Direct sectionid URL parameter (e.g. on course/view.php?sectionid=X).
        if (!$sectionid && $PAGE->url) {
            $sectionid = (int)($PAGE->url->get_param('sectionid') ?? 0);
        }
        if (!$sectionid) {
            $sectionid = optional_param('sectionid', 0, PARAM_INT);
        }

        // 3. Section number URL parameter (e.g. on course/view.php?section=X).
        if (!$sectionid && $PAGE->course && $PAGE->course->id > SITEID) {
            $sectionnum = 0;
            if ($PAGE->url) {
                $sectionnum = (int)($PAGE->url->get_param('section') ?? 0);
            }
            if (!$sectionnum) {
                $sectionnum = optional_param('section', 0, PARAM_INT);
            }
            if ($sectionnum > 0) {
                try {
                    $modinfo = get_fast_modinfo($PAGE->course);
                    $sectioninfo = $modinfo->get_section_info($sectionnum);
                    if ($sectioninfo) {
                        $sectionid = (int)$sectioninfo->id;
                    }
                } catch (\Exception $e) {
                    $sectionid = 0;
                }
            }
        }
        return $sectionid;
    }

    /**
     * Extend course navigation node.
     *
     * @param navigation_node $navigation
     * @param stdClass $course
     * @return void
     */
    public static function extend_navigation_course(navigation_node $navigation, stdClass $course): void {
        $context = context_course::instance($course->id);
        if (self::can_translate_context($context)) {
            $sectionid = self::detect_current_section_id();
            $url = self::get_translate_url((int)$course->id, $sectionid > 0 ? $sectionid : -99);
            $title = get_string('pluginname', 'local_deepler');
            $translatecontent = navigation_node::create(
                $title,
                $url,
                navigation_node::TYPE_CUSTOM,
                $title,
                'translate'
            );
            $navigation->add_node($translatecontent);
            $navigation->showinflatnavigation = true;
        }

        // Add Deepler: Files link if enabled and user has capability.
        if (get_config('local_deepler', 'filetranslation') && has_capability('local/deepler:translatefiles', $context)) {
            $filesurl = new moodle_url('/local/deepler/files.php', ['courseid' => (int)$course->id]);
            $filestitle = get_string('files:menu', 'local_deepler');
            $filesnode = navigation_node::create(
                $filestitle,
                $filesurl,
                navigation_node::TYPE_CUSTOM,
                $filestitle,
                'deepler_files'
            );
            $navigation->add_node($filesnode);
            $navigation->showinflatnavigation = true;
        }
    }
}
