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

use context_course;
use context_module;
use html_writer;
use core\output\pix_icon;
use core_plugin_manager;
use Exception;
use local_deepler\local\data\field;

/**
 * Utilitarian statics.
 *
 * @package local_deepler
 * @copyright 2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {
    /**
     * API Key validator.
     */
    const DEEPL_API_REGEX = '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}(:fx|:pro)?$/i';
    /**
     * List of bg colors for highlighting.
     */
    const COLORS = [
            'FloralWhite',
            'Lavender',
            'LightYellow',
            'MintCream',
            'Honeydew',
            'AliceBlue', 'GhostWhite',
            'Ivory',
    ];
    /**
     * Generate a color index for a given array.
     *
     * @param array $tab
     * @return array
     */
    public static function makecolorindex(array $tab): array {
        $t = [];
        foreach ($tab as $i => $v) {
            $t[] = ['key' => $v, 'value' => self::COLORS[$i % count(self::COLORS)]];
        }
        return $t;
    }
    /**
     * Unified file URL resolver with context-aware processing.
     *
     * @param \local_deepler\local\data\field $field
     * @return string Processed text with valid URLs
     * @throws \dml_exception
     */
    public static function resolve_pluginfiles(field $field): string {
        $text = $field->get_displaytext();
        try {
            $contextinfo = self::get_context_info($field->get_table(), $field->get_id(), $field->get_cmid());
            $fs = get_file_storage();
            $filearea = $field->get_tablefield();
            // Get first valid file for metadata.
            $files = $fs->get_area_files(
                    $contextinfo['contextid'],
                    $contextinfo['component'],
                    $contextinfo['filearea'] ?? $filearea,
                    $contextinfo['itemid'],
                    'id',
                    false
            );

            if ($files) {
                $firstfile = reset($files);
                return file_rewrite_pluginfile_urls(
                        $text,
                        'pluginfile.php',
                        $firstfile->get_contextid(),
                        $firstfile->get_component(),
                        $firstfile->get_filearea(),
                        $firstfile->get_itemid()
                );
            } else {
                // Fallback to context-based rewrite.
                switch ($filearea) {
                    case 'intro' :
                        $contextinfo['itemid'] = null;
                        break;
                }
                return file_rewrite_pluginfile_urls(
                        $text,
                        'pluginfile.php',
                        $contextinfo['contextid'],
                        $contextinfo['component'],
                        $contextinfo['filearea'] ?? $filearea,
                        $contextinfo['itemid']
                );
            }

        } catch (Exception $e) {
            return $text; // Fail gracefully.
        }
    }

    /**
     * Context resolution optimized for Moodle's hierarchy.
     *
     * @param string $table
     * @param int $itemid
     * @param int $cmid
     * @return array
     * @throws \dml_exception
     */
    public static function get_context_info(string $table, int $itemid, int $cmid = 0): array {
        global $DB;
        switch ($table) {
            case 'course':
                $context = context_course::instance($itemid);
                return [
                        'context' => $context,
                        'contextid' => $context->id,
                        'component' => 'course',
                        'itemid' => null,
                ];

            case 'course_sections':
                $courseid = $DB->get_field('course_sections', 'course', ['id' => $itemid]);
                return [
                        'context' => context_course::instance($courseid),
                        'contextid' => context_course::instance($courseid)->id,
                        'component' => 'course',
                        'filearea' => 'section',
                        'itemid' => $itemid,
                ];
            case 'question':
                return [
                        'context' => context_module::instance($cmid),
                        'contextid' => $cmid,
                        'component' => 'qtype_' . $table,
                        'itemid' => $itemid,
                ];
            default: // Activity modules.
                $context = context_module::instance($cmid);
                return [
                        'context' => $context,
                        'contextid' => $context->id,
                        'component' => 'mod_' . $table,
                        'itemid' => $cmid,
                ];
        }
    }

    /**
     * Helper to make sure attributes generated from text are compatible.
     *
     * @param string $text
     * @return string
     */
    public static function makehtmlid(string $text): string {
        // Convert to lowercase.
        $text = strtolower($text);

        // Replace spaces and underscores with hyphens.
        $text = preg_replace('/[\s_]+/', '-', $text);

        // Remove all characters that are not alphanumeric or hyphens.
        $text = preg_replace('/[^a-z0-9\-]/', '', $text);

        // Ensure it doesn't start with a digit.
        if (preg_match('/^[0-9]/', $text)) {
            $text = 'id-' . $text;
        }

        // Trim hyphens from start and end.
        $text = trim($text, '-');

        return $text;
    }

    /**
     * Returns an array of standard user fields for token mapping.
     *
     * @return array
     * @throws \coding_exception
     */
    public static function standard_user_fields(): array {
        return [
                'username' => get_string('username'),
                'email' => get_string('email'),
                'firstname' => get_string('firstname'),
                'lastname' => get_string('lastname'),
                'city' => get_string('city'),
                'country' => get_string('country'),
                'institution' => get_string('institution'),
                'department' => get_string('department'),
                'phone1' => get_string('phone1'),
                'phone2' => get_string('phone2'),
                'address' => get_string('address'),
                'idnumber' => get_string('idnumber'),
        ];
    }

    /**
     * Returns an array of all available user fields (standard + custom profile fields).
     *
     * @return array
     * @throws \coding_exception
     */
    public static function all_user_fields(): array {
        global $DB;
        $fields = self::standard_user_fields();

        // Add custom profile fields.
        foreach ($DB->get_records('user_info_field') as $field) {
            $fields['profile_field_' . $field->shortname] =
                    $field->name . ' (' . get_string('customprofilefield', 'admin') . ')';
        }
        return $fields;
    }

    /**
     * Checks if a value matches a pattern with SQL-style wildcards (% and _).
     *
     * @param string $pattern
     * @param string $value
     * @return bool
     */
    public static function wildcard_match(string $pattern, string $value) {
        // Convert * to % for user convenience.
        $pattern = str_replace('*', '%', $pattern);
        // Escape regex special chars except % and _.
        $regex = preg_quote($pattern, '/');
        // Convert SQL wildcards to regex.
        $regex = str_replace(['%', '_'], ['.*', '.'], $regex);
        // Match full string (case-insensitive).
        return (bool) preg_match('/^' . $regex . '$/i', $value);
    }

    /**
     * Returns the absolute path to the plugin root directory.
     *
     * @return string
     */
    public static function get_plugin_root(): string {
        $pluginman = core_plugin_manager::instance();
        $plugininfo = $pluginman->get_plugin_info('local_deepler');
        return $plugininfo->rootdir;
    }

    /**
     * Alternative for v > 405.
     *
     * @param string $icon
     * @param string $alt
     * @param string $component
     * @param array $attributes
     * @return \core\output\pix_icon|string
     */
    public static function local_deepler_get_pix_icon($icon, $alt, $component = 'core', $attributes = []) {
        global $CFG;

        // Check if the class exists (Moodle >= 4.0.4).
        if (class_exists('\core\output\pix_icon')) {
            return new pix_icon($icon, $alt, $component, $attributes);
        } else {
            // Fallback for older Moodle versions.
            return html_writer::empty_tag('img', array_merge([
                    'src' => $CFG->wwwroot . "/pix/$component/$icon.png",
                    'alt' => $alt,
                    'class' => 'icon',
            ], $attributes));
        }
    }

}
