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

namespace local_deepler\external;

use DeepL\AppInfo;

/**
 * Simple trait to reuse Deepl api key settings.
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait deeplapi_trait {
    /**
     * Set the key string.
     * If empty, it will try to get it from the .env useful for tests runs.
     *
     * @param string $version
     * @return void
     * @throws \dml_exception
     */
    public static function setdeeplapi(string $version): void {
        self::$appinfo = new AppInfo('Moodle-Deepler', $version);
        $configkey = get_config('local_deepler', 'apikey');
        if ($configkey === '') {
            $configkey = getenv('DEEPL_APIKEY') ? getenv('DEEPL_APIKEY') : '';
        }
        self::$apikey = $configkey;
    }
}
