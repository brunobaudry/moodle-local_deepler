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

/**
 * Abstract base class for translation providers.
 *
 * Provides shared utilities: language code normalization, Moodle-language
 * filtering, and a safe default map_options() implementation.
 * Concrete providers extend this class and implement the full interface.
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class abstract_translation_provider implements translation_provider_interface {
    /**
     * API key (or access token) for this provider.
     *
     * @var string
     */
    protected string $apikey;

    /**
     * Provider-specific configuration (base URL, timeouts, …).
     *
     * @var array
     */
    protected array $config;

    /**
     * Cached source languages fetched from the provider.
     *
     * @var translation_language[]|null
     */
    protected ?array $sourcelanguages = null;

    /**
     * Cached target languages fetched from the provider.
     *
     * @var translation_language[]|null
     */
    protected ?array $targetlanguages = null;

    /**
     * @param string $apikey  API key / access token.
     * @param array  $config  Provider-specific configuration.
     */
    public function __construct(string $apikey, array $config = []) {
        $this->apikey = $apikey;
        $this->config = $config;
    }

    /**
     * Normalizes a language code to the plugin's canonical uppercase-with-dash format.
     *
     * Examples:
     *   'en_gb'  → 'EN-GB'
     *   'pt-br'  → 'PT-BR'
     *   'EN'     → 'EN'
     *
     * @param string $code  Raw language code from any source.
     * @return string
     */
    protected function normalize_lang_code(string $code): string {
        return strtoupper(str_replace('_', '-', trim($code)));
    }

    /**
     * Strips the regional variant from a language code, returning only the base code.
     *
     * Examples:
     *   'EN-GB' → 'EN'
     *   'PT-BR' → 'PT'
     *   'DE'    → 'DE'
     *
     * @param string $code  Normalized language code.
     * @return string
     */
    protected function remove_regional_variant(string $code): string {
        $parts = explode('-', $code, 2);
        return $parts[0];
    }

    /**
     * Filters a list of translation_language objects to only those whose code
     * matches at least one installed Moodle language.
     *
     * @param translation_language[] $langs        Provider language list.
     * @param array                  $moodlelangs  Map of Moodle lang codes → display names
     *                                              (from get_string_manager()->get_list_of_translations()).
     * @return translation_language[]
     */
    protected function filter_langs_for_moodle(array $langs, array $moodlelangs): array {
        return array_values(array_filter($langs, function (translation_language $lang) use ($moodlelangs): bool {
            $providercode = strtolower($lang->code);
            foreach (array_keys($moodlelangs) as $moodlecode) {
                $normalized = strtolower(str_replace('_', '-', $moodlecode));
                if (stripos($providercode, $normalized) !== false) {
                    return true;
                }
            }
            return false;
        }));
    }

    /**
     * Checks whether a normalized language code appears in a language list.
     *
     * @param string               $lang   Normalized code to look for.
     * @param translation_language[] $list   List to search.
     * @return bool
     */
    protected function lang_in_list(string $lang, array $list): bool {
        $needle = strtolower($lang);
        foreach ($list as $item) {
            if (strtolower($item->code) === $needle) {
                return true;
            }
            // Also match on base code (EN matches EN-GB, EN-US).
            if (strtolower($this->remove_regional_variant($item->code)) === $needle) {
                return true;
            }
        }
        return false;
    }

    /**
     * Default options-mapping implementation: strips all keys.
     *
     * Concrete providers override this to pass through or map the options they understand.
     *
     * @param array $options
     * @return array
     */
    public function map_options(array $options): array {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function is_language_supported(string $lang): bool {
        $sources = $this->get_source_languages();
        return $this->lang_in_list($lang, $sources);
    }
}
