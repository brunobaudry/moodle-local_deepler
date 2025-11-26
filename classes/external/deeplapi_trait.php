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
use DeepL\AuthorizationException;
use DeepL\DeepLClient;
use DeepL\DeepLException;
use local_deepler\local\services\lang_helper;

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
     * @return \DeepL\DeepLClient|null
     * @throws \DeepL\DeepLException
     * @throws \dml_exception
     */
    public static function setdeeplapikey(string $version): DeepLClient|null {
        global $USER;
        $languagepack = new lang_helper();
        $initok = $languagepack->initdeepl($USER, $version);
        if ($initok) {
            return $languagepack->gettranslator();
        } else {
            $configkey = get_config('local_deepler', 'apikey');
            if ($configkey === '') {
                $configkey = getenv('DEEPL_APIKEY') ? getenv('DEEPL_APIKEY') : '';
            }
            try {
                return new DeepLClient($configkey, [
                        'send_platform_info' => true,
                        'app_info' => self::setdeeplappinfo($version),
                ]);
            } catch (DeepLException $e) {
                return null;
            }
        }
    }

    /**
     * Set the key string.
     * If empty, it will try to get it from the .env useful for tests runs.
     *
     * @param string $version
     * @return \DeepL\AppInfo
     */
    public static function setdeeplappinfo(string $version): AppInfo {
        return new AppInfo('Moodle-Deepler', $version);
    }

    /**
     * Splits texts into chunks respecting DeepL's payload size limit.
     *
     * @param array $items Array of items with 'text' and 'key'.
     * @param array $staticparts Static parts of the payload (e.g. options, lang).
     * @return array Array of chunks.
     * @todo MDL-0000 Make maxbytes and buffer admin settings.
     */
    protected static function chunk_payload(array $items, array $staticparts): array {
        $chunks = [];
        $chunk = [];
        $maxbytes = 100000;
        $bufferbytes = 1024 * 16;
        $basepayload = implode('', array_map(function ($part) {
            return json_encode($part);
        }, $staticparts));

        $basebytes = strlen(mb_convert_encoding($basepayload, 'UTF-8')) + $bufferbytes;
        $chunkbytes = $basebytes;

        foreach ($items as $item) {
            $textbytes = strlen(mb_convert_encoding($item['text'], 'UTF-8'));

            if ($chunkbytes + $textbytes > $maxbytes && !empty($chunk)) {
                $chunks[] = $chunk;
                $chunk = [$item];
                $chunkbytes = $basebytes + $textbytes;
            } else {
                $chunk[] = $item;
                $chunkbytes += $textbytes;
            }
        }

        if (!empty($chunk)) {
            $chunks[] = $chunk;
        }

        return $chunks;
    }
}
