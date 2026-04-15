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

namespace local_deepler\local\translation;

use local_deepler\local\translation\interfaces\translation_provider_interface;
use local_deepler\local\translation\providers\deepl_provider;
use local_deepler\local\translation\providers\libretranslate_provider;

/**
 * Factory that creates the configured translation provider.
 *
 * Reads local_deepler/provider from Moodle config (defaults to 'deepl') and
 * instantiates the appropriate provider class with the resolved API key.
 *
 * New providers only need to:
 *  1. Be added to PROVIDERS map below.
 *  2. Implement translation_provider_interface (and optional capability interfaces).
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class translation_provider_factory {
    /**
     * Map of provider ID → display name for the admin settings select.
     * Add future providers here.
     */
    const PROVIDERS = [
        'deepl'           => 'DeepL',
        'libretranslate'  => 'LibreTranslate',
    ];

    /**
     * Returns the map of available providers suitable for admin_setting_configselect.
     *
     * @return string[]  ['id' => 'Display name', …]
     */
    public static function get_available_providers(): array {
        return self::PROVIDERS;
    }

    /**
     * Creates the provider configured for the current site, using the appropriate
     * API key for the given user (respects DeepL token-pool mapping).
     *
     * The token-pool resolution is handled by the caller (lang_helper) before
     * calling this method, and the resolved API key is passed in.
     *
     * @param string $providerid   Provider identifier from config (e.g. 'deepl').
     * @param string $apikey       Resolved API key / access token for this user.
     * @param string $version      Plugin version string (used in SDK AppInfo header).
     * @param array  $extraconfig  Additional provider-specific config (e.g. base URLs).
     * @return translation_provider_interface
     */
    public static function make(
        string $providerid,
        string $apikey,
        string $version = '',
        array $extraconfig = []
    ): translation_provider_interface {
        switch ($providerid) {
            case 'libretranslate':
                $baseurl = $extraconfig['base_url']
                    ?? get_config('local_deepler', 'libretranslate_url')
                    ?: 'https://libretranslate.com';
                return new libretranslate_provider($apikey, ['base_url' => $baseurl]);

            case 'deepl':
            default:
                return new deepl_provider($apikey, ['version' => $version]);
        }
    }

    /**
     * Returns the provider ID configured in Moodle admin settings.
     * Defaults to 'deepl' for backward compatibility.
     *
     * @return string
     */
    public static function get_configured_provider_id(): string {
        $id = get_config('local_deepler', 'provider');
        if (!$id || !array_key_exists($id, self::PROVIDERS)) {
            return 'deepl';
        }
        return $id;
    }
}
