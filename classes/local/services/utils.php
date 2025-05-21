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
}
