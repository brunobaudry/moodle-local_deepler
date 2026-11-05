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

use cm_info;
use context_course;
use context_module;
use core_component;
use core_courseformat\local\cmactions;
use local_deepler\external\get_translation;
use moodle_url;
use stdClass;
use stored_file;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Service to duplicate course activities, replace files, and apply language restrictions.
 *
 * @package    local_deepler
 * @copyright  2026 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_duplicator {
    /** @var string Plugin version. */
    protected string $version;

    /**
     * Constructor.
     *
     * @param string $version Plugin release version.
     */
    public function __construct(string $version = '') {
        $this->version = $version;
    }

    /**
     * Translate text using Deepler get_translation service with fallback.
     *
     * @param string $text
     * @param string $sourcelang
     * @param string $targetlang
     * @param bool $ishtml
     * @return string
     */
    public function translate_text(string $text, string $sourcelang, string $targetlang, bool $ishtml = false): string {
        if (trim($text) === '') {
            return $text;
        }

        $options = [
            'target_lang' => $targetlang,
        ];
        if ($ishtml) {
            $options['tag_handling'] = 'html';
        }

        $translations = [
            [
                'text' => $text,
                'key' => 'item_1',
                'source_lang' => $sourcelang,
                'options' => [],
            ],
        ];

        try {
            $results = get_translation::execute($translations, $options, $this->version);
            if (!empty($results) && empty($results[0]['error']) && !empty($results[0]['translated_text'])) {
                return $results[0]['translated_text'];
            }
        } catch (\Throwable $e) {
            // Fallback handled by caller.
        }

        return '';
    }

    /**
     * Generate translated filename with target language code appended.
     *
     * @param string $filename Original filename.
     * @param string $targetlang Target language code.
     * @return string
     */
    public function get_translated_filename(string $filename, string $targetlang): string {
        $pathinfo = pathinfo($filename);
        $basename = $pathinfo['filename'] ?? 'file';
        $ext = !empty($pathinfo['extension']) ? ('.' . $pathinfo['extension']) : '';
        return $basename . '_' . strtolower($targetlang) . $ext;
    }

    /**
     * Duplicate a course module and replace its file with the translated version.
     *
     * @param cm_info $cm Source course module.
     * @param string $translatedpath Filesystem path to the translated file.
     * @param stored_file $original Original stored file.
     * @param string $targetlang Target language code (e.g. 'fr').
     * @param string $sourcelang Source language code.
     * @param string $newname Optional pre-calculated new name.
     * @return array [cm_info $newcm, stored_file $newfile, bool $restricted, string $warning]
     * @throws \moodle_exception
     */
    public function duplicate_with_file(
        cm_info $cm,
        string $translatedpath,
        stored_file $original,
        string $targetlang,
        string $sourcelang = '',
        string $newname = ''
    ): array {
        global $DB;

        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

        // 1. Determine translated name.
        if (empty($newname)) {
            $translatedname = $this->translate_text($cm->name, $sourcelang, $targetlang, false);
            $basename = !empty($translatedname) ? $translatedname : $cm->name;
            $newname = $basename . ' (' . strtoupper($targetlang) . ')';
        }

        // 2. Duplicate activity using cmactions::duplicate or duplicate_module.
        $newcmid = 0;
        if (class_exists('core_courseformat\\local\\cmactions')) {
            $actions = new cmactions($course);
            $duplicatedcm = $actions->duplicate((int)$cm->id, null, $newname);
            $newcmid = $duplicatedcm ? (int)$duplicatedcm->id : 0;
        } else {
            $newcm = duplicate_module($course, $cm);
            if ($newcm) {
                $newcmid = (int)$newcm->id;
                // Move duplicate right after the source.
                $section = $DB->get_record('course_sections', ['id' => $cm->section]);
                if ($section) {
                    moveto_module($newcm, $section, $cm);
                }
            }
        }

        if (!$newcmid) {
            throw new \moodle_exception('Could not duplicate course module ' . $cm->id);
        }

        // Refresh modinfo to get the duplicated cm_info.
        $modinfo = get_fast_modinfo($course, true);
        $newcminfo = $modinfo->cms[$newcmid] ?? null;
        if (!$newcminfo) {
            throw new \moodle_exception('Could not locate duplicated cm_info ' . $newcmid);
        }

        $modname = $newcminfo->modname;

        // 3. Update module instance name and translate intro if present.
        $instance = $DB->get_record($modname, ['id' => $newcminfo->instance], '*', MUST_EXIST);
        $instance->name = $newname;

        if (!empty($instance->intro)) {
            $translatedintro = $this->translate_text($instance->intro, $sourcelang, $targetlang, true);
            if (!empty($translatedintro)) {
                $instance->intro = $translatedintro;
            }
        }
        $DB->update_record($modname, $instance);

        // 4. Replace file in the duplicate module context.
        $newcontext = context_module::instance($newcminfo->id);
        $fs = get_file_storage();
        $newfilename = $this->get_translated_filename($original->get_filename(), $targetlang);

        // Find existing copy of original file in new context.
        $existingfile = $fs->get_file(
            $newcontext->id,
            $original->get_component(),
            $original->get_filearea(),
            $original->get_itemid(),
            $original->get_filepath(),
            $original->get_filename()
        );

        if ($existingfile) {
            $existingfile->delete();
        }

        $existingnewfile = $fs->get_file(
            $newcontext->id,
            $original->get_component(),
            $original->get_filearea(),
            $original->get_itemid(),
            $original->get_filepath(),
            $newfilename
        );

        if ($existingnewfile) {
            $existingnewfile->delete();
        }

        // Create the replacement stored_file with the translated filename.
        $filerecord = [
            'contextid' => $newcontext->id,
            'component' => $original->get_component(),
            'filearea' => $original->get_filearea(),
            'itemid' => $original->get_itemid(),
            'filepath' => $original->get_filepath(),
            'filename' => $newfilename,
            'userid' => $original->get_userid(),
        ];
        $newstoredfile = $fs->create_file_from_pathname($filerecord, $translatedpath);

        // 5. Apply language restriction.
        $restricted = $this->apply_language_restriction($newcminfo->id, $targetlang, (int)$course->id);
        $warning = '';
        if (!$restricted) {
            $warning = get_string('files:missingavailabilitywarning', 'local_deepler');
        }

        return [$newcminfo, $newstoredfile, $restricted, $warning];
    }

    /**
     * Store translated course-level file in Moodle file storage.
     *
     * @param stored_file $original Original stored_file.
     * @param string $translatedpath Local filesystem path to translated file.
     * @param string $targetlang Target language code.
     * @return stored_file
     */
    public function store_course_file(stored_file $original, string $translatedpath, string $targetlang): stored_file {
        $fs = get_file_storage();
        $filename = $this->get_translated_filename($original->get_filename(), $targetlang);

        $existing = $fs->get_file(
            $original->get_contextid(),
            $original->get_component(),
            $original->get_filearea(),
            $original->get_itemid(),
            $original->get_filepath(),
            $filename
        );
        if ($existing) {
            $existing->delete();
        }

        $filerecord = [
            'contextid' => $original->get_contextid(),
            'component' => $original->get_component(),
            'filearea' => $original->get_filearea(),
            'itemid' => $original->get_itemid(),
            'filepath' => $original->get_filepath(),
            'filename' => $filename,
            'userid' => $original->get_userid(),
        ];

        return $fs->create_file_from_pathname($filerecord, $translatedpath);
    }

    /**
     * Apply language restriction to a course module using availability_language.
     *
     * @param int $cmid Course module ID.
     * @param string $targetlang Target language code.
     * @param int $courseid Course ID.
     * @return bool True if restriction was applied, false if plugin is unavailable.
     */
    public function apply_language_restriction(int $cmid, string $targetlang, int $courseid = 0): bool {
        global $DB;

        // Check if availability_language is installed and enabled.
        $plugindir = core_component::get_plugin_directory('availability', 'language');
        $disabled = get_config('availability_language', 'disabled');

        if (!$plugindir || $disabled) {
            return false;
        }

        $json = json_encode([
            'op' => '&',
            'c' => [
                [
                    'type' => 'language',
                    'id' => strtolower($targetlang),
                ],
            ],
            'showc' => [true],
        ]);

        $DB->set_field('course_modules', 'availability', $json, ['id' => $cmid]);

        if ($courseid > 0) {
            rebuild_course_cache($courseid, true);
        }

        return true;
    }
}
