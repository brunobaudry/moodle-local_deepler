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

namespace local_deepler\lib;

use admin_setting_configtext;
use lang_string;
use local_deepler\local\services\utils;

/**
 * Wrapper calss to add UUID key validation.
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_deeplapikey_configtext extends admin_setting_configtext {
    /**
     * Validate data before storage
     *
     * @param string $data
     * @return string|\lang_string|true true if ok string if error found
     */
    public function validate($data): string|lang_string|true {
        // Skip validation during install/bootstrap phases.
        if (function_exists('during_initial_install') && during_initial_install()) {
            return true;
        }
        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            return true;
        }

        $allowfallbackkey = get_config('local_deepler', 'allowfallbackkey');
        if (empty($data) && $allowfallbackkey) {
            return get_string('missingmainapikey', 'local_deepler');
        } else if (!preg_match(utils::DEEPL_API_REGEX, $data) || trim($data) === '') {
            return get_string('tokenerror_invaliduuid', 'local_deepler');
        }
        return true;
    }
}
