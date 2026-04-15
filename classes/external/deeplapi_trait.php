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

use local_deepler\local\services\lang_helper;
use local_deepler\local\translation\interfaces\translation_provider_interface;

/**
 * Shared trait for external API classes that need a configured translation provider.
 *
 * Provides:
 *   set_provider()     – creates and returns the configured translation provider.
 *   setdeeplapikey()   – deprecated alias kept for backward compatibility.
 *   chunk_payload()    – provider-agnostic payload chunker.
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait deeplapi_trait {

    /**
     * Creates and returns the configured translation provider for the current user.
     *
     * Initialises lang_helper (which resolves the API key via token-pool matching
     * and instantiates the provider via translation_provider_factory) and returns
     * the provider instance.
     *
     * @param string $version  Plugin version string forwarded to the provider.
     * @return translation_provider_interface|null  null if initialisation failed.
     * @throws \dml_exception
     */
    public static function set_provider(string $version): ?translation_provider_interface {
        global $USER;
        $languagepack = new lang_helper();
        $initok = $languagepack->init_provider($USER, $version);
        if ($initok) {
            return $languagepack->get_provider();
        }
        return null;
    }

    /**
     * Deprecated alias for set_provider().
     *
     * Returns the underlying DeepLClient for legacy callers that type-hint it.
     * Returns null if the active provider is not DeepL.
     *
     * @param string $version
     * @return \DeepL\DeepLClient|null
     * @throws \dml_exception
     * @deprecated Use set_provider() instead.
     */
    public static function setdeeplapikey(string $version) {
        global $USER;
        $languagepack = new lang_helper();
        $initok = $languagepack->init_provider($USER, $version);
        if ($initok) {
            return $languagepack->gettranslator();
        }
        // Fallback: try to build a provider from global config for tests.
        $configkey = get_config('local_deepler', 'apikey');
        if (!$configkey) {
            $configkey = getenv('DEEPL_APIKEY') ? getenv('DEEPL_APIKEY') : '';
        }
        if (empty($configkey)) {
            return null;
        }
        try {
            $provider = \local_deepler\local\translation\translation_provider_factory::make('deepl', $configkey, $version);
            if ($provider instanceof \local_deepler\local\translation\providers\deepl_provider) {
                return $provider->get_client();
            }
        } catch (\Throwable $e) {
            return null;
        }
        return null;
    }

    /**
     * Creates an AppInfo object for the DeepL SDK.
     *
     * @param string $version  Plugin version string.
     * @return \DeepL\AppInfo
     * @deprecated Kept for backward compat with tests. Logic moved to deepl_provider constructor.
     */
    public static function setdeeplappinfo(string $version): \DeepL\AppInfo {
        return new \DeepL\AppInfo('Moodle-Deepler', $version);
    }

    /**
     * Splits texts into chunks respecting a maximum payload size limit.
     *
     * Used by get_translation and get_rephrase to avoid hitting provider
     * request-body size limits. The logic is provider-agnostic.
     *
     * @param array $items        Array of items with 'text' and 'key'.
     * @param array $staticparts  Static parts of the payload (options, lang codes, …).
     * @return array  Array of chunks, each chunk being an array of items.
     * @todo MDL-0000 Make maxbytes and buffer admin settings.
     */
    protected static function chunk_payload(array $items, array $staticparts): array {
        $chunks      = [];
        $chunk       = [];
        $maxbytes    = 100000;
        $bufferbytes = 1024 * 16;
        $basepayload = implode('', array_map(function ($part) {
            return json_encode($part);
        }, $staticparts));

        $basebytes  = strlen(mb_convert_encoding($basepayload, 'UTF-8')) + $bufferbytes;
        $chunkbytes = $basebytes;

        foreach ($items as $item) {
            $textbytes = strlen(mb_convert_encoding($item['text'], 'UTF-8'));

            if ($chunkbytes + $textbytes > $maxbytes && !empty($chunk)) {
                $chunks[] = $chunk;
                $chunk    = [$item];
                $chunkbytes = $basebytes + $textbytes;
            } else {
                $chunk[]    = $item;
                $chunkbytes += $textbytes;
            }
        }

        if (!empty($chunk)) {
            $chunks[] = $chunk;
        }

        return $chunks;
    }
}
