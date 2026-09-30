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
 * Upgrade helper functions for local_deepler.
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Merge the bundled additional_conf.json defaults into the admin's stored additionalconf.
 *
 * Rules (the stored/admin config always has priority):
 * - Any component, table, field or clause present in the bundled file but missing in the stored
 *   config is added.
 * - Entries only present in the stored config (custom additions of the admin) are kept untouched.
 * - When both define the same key with array values, they are merged recursively.
 * - When both define the same key with scalar values, the stored value is kept.
 * - When the stored value is null (no clauses defined) and the bundled file now provides clauses
 *   (e.g. {"editable": false}), the bundled clauses are taken as the admin never expressed any.
 *
 * @param array $stored the decoded admin config
 * @param array $bundled the decoded bundled default config
 * @return array
 */
function local_deepler_merge_additionalconf(array $stored, array $bundled): array {
    foreach ($bundled as $key => $value) {
        if (!array_key_exists($key, $stored)) {
            $stored[$key] = $value;
            continue;
        }
        if (is_array($value) && is_array($stored[$key])) {
            $stored[$key] = local_deepler_merge_additionalconf($stored[$key], $value);
        } else if ($stored[$key] === null && is_array($value) && !empty($value)) {
            $stored[$key] = $value;
        }
        // Otherwise the stored (admin) value wins.
    }
    return $stored;
}

/**
 * Smartly update the stored additionalconf setting with the bundled additional_conf.json.
 *
 * - No/empty/invalid stored config: seed it from the bundled file.
 * - Valid stored config: merge the bundled defaults into it (see local_deepler_merge_additionalconf)
 *   and save only when the result differs.
 *
 * @param string|null $jsonfile path to the bundled json file, defaults to the plugin's additional_conf.json
 * @return bool true when the stored config was changed
 * @throws \dml_exception
 */
function local_deepler_sync_additionalconf(?string $jsonfile = null): bool {
    $jsonfile = $jsonfile ?? __DIR__ . '/../additional_conf.json';
    if (!file_exists($jsonfile)) {
        return false;
    }
    $bundledraw = file_get_contents($jsonfile);
    $bundled = json_decode($bundledraw, true);
    if (!is_array($bundled)) {
        return false;
    }

    $stored = get_config('local_deepler', 'additionalconf');
    $decoded = ($stored === false || $stored === '') ? null : json_decode($stored, true);
    if (!is_array($decoded)) {
        set_config('additionalconf', $bundledraw, 'local_deepler');
        return true;
    }

    $merged = local_deepler_merge_additionalconf($decoded, $bundled);
    if ($merged === $decoded) {
        return false;
    }
    set_config(
        'additionalconf',
        json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'local_deepler'
    );
    return true;
}
