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

namespace local_deepler\local\translation\providers;

use local_deepler\local\translation\abstract_translation_provider;
use local_deepler\local\translation\translation_language;
use local_deepler\local\translation\translation_result;

/**
 * Translation provider for LibreTranslate (https://libretranslate.com).
 *
 * Uses Moodle's built-in curl class — no new Composer dependency.
 * Implements only the core translation_provider_interface.
 * Rephrase, glossary, and usage are not supported.
 *
 * Configuration keys read from Moodle admin settings:
 *   local_deepler/libretranslate_url     → base URL (default: https://libretranslate.com)
 *   local_deepler/libretranslate_apikey  → API key (optional for public instances)
 *
 * LibreTranslate API reference: https://libretranslate.com/docs/
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class libretranslate_provider extends abstract_translation_provider {
    /**
     * Base URL of the LibreTranslate instance (no trailing slash).
     *
     * @var string
     */
    private string $baseurl;

    /**
     * Cached language list from GET /languages.
     *
     * @var array|null
     */
    private ?array $cachedlanguages = null;

    /**
     * @param string $apikey  API key (may be empty for public instances).
     * @param array  $config  Must contain 'base_url'.
     */
    public function __construct(string $apikey, array $config = []) {
        parent::__construct($apikey, $config);
        $this->baseurl = rtrim($config['base_url'] ?? 'https://libretranslate.com', '/');
    }

    // -------------------------------------------------------------------------
    // translation_provider_interface
    // -------------------------------------------------------------------------

    public function get_provider_id(): string {
        return 'libretranslate';
    }

    public function get_provider_name(): string {
        return 'LibreTranslate';
    }

    /**
     * {@inheritdoc}
     *
     * LibreTranslate uses the same language list for source and target.
     *
     * @return translation_language[]
     */
    public function get_source_languages(): array {
        if ($this->sourcelanguages !== null) {
            return $this->sourcelanguages;
        }
        $this->sourcelanguages = $this->fetch_languages();
        return $this->sourcelanguages;
    }

    /**
     * {@inheritdoc}
     *
     * @return translation_language[]
     */
    public function get_target_languages(): array {
        if ($this->targetlanguages !== null) {
            return $this->targetlanguages;
        }
        $this->targetlanguages = $this->fetch_languages();
        return $this->targetlanguages;
    }

    /**
     * {@inheritdoc}
     *
     * Sends one POST /translate request per text (LibreTranslate's batch support
     * is instance-dependent; single requests are universally supported).
     * Texts are sent in parallel using a simple sequential loop for now.
     *
     * @param string[] $texts
     * @param string   $sourcelang  Use 'auto' or empty string for auto-detect.
     * @param string   $targetlang
     * @param array    $options     Only 'format' ('html'|'text') is used if present.
     * @return translation_result[]
     */
    public function translate(array $texts, string $sourcelang, string $targetlang, array $options = []): array {
        $mapped = $this->map_options($options);
        $source = $sourcelang ?: 'auto';
        $target = strtolower($this->remove_regional_variant($targetlang));

        $results = [];
        foreach ($texts as $text) {
            $payload = array_merge([
                'q'      => $text,
                'source' => strtolower($this->remove_regional_variant($source)),
                'target' => $target,
            ], $mapped);
            if (!empty($this->apikey)) {
                $payload['api_key'] = $this->apikey;
            }

            $response = $this->post('/translate', $payload);
            if ($response === null || isset($response['error'])) {
                $results[] = new translation_result(
                    '',
                    '',
                    $targetlang
                );
            } else {
                $results[] = new translation_result(
                    $response['translatedText'] ?? '',
                    $response['detectedLanguage']['language'] ?? '',
                    $targetlang
                );
            }
        }
        return $results;
    }

    /**
     * Map generic options to the LibreTranslate-understood subset.
     *
     * Recognised:
     *   tag_handling → format ('html' passes through; anything else omitted)
     *
     * @param array $options
     * @return array
     */
    public function map_options(array $options): array {
        $out = [];
        if (!empty($options['tag_handling']) && $options['tag_handling'] === 'html') {
            $out['format'] = 'html';
        }
        return $out;
    }

    public function is_api_key_set(): bool {
        return !empty($this->baseurl);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Fetches the language list from GET /languages and converts to translation_language[].
     *
     * @return translation_language[]
     */
    private function fetch_languages(): array {
        $response = $this->get('/languages');
        if ($response === null || !is_array($response)) {
            return [];
        }
        $langs = [];
        foreach ($response as $item) {
            if (empty($item['code'])) {
                continue;
            }
            $code = $this->normalize_lang_code($item['code']);
            $name = $item['name'] ?? $code;
            $langs[] = new translation_language($code, $name, false, false);
        }
        return $langs;
    }

    /**
     * Performs a GET request to the given LibreTranslate endpoint path.
     *
     * @param string $path  URL path, e.g. '/languages'.
     * @return array|null   Decoded JSON response, or null on failure.
     */
    private function get(string $path): ?array {
        $curl = new \curl(['ignoresecurity' => true]);
        $url = $this->baseurl . $path;
        $raw = $curl->get($url);
        if ($curl->get_errno()) {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Performs a POST request to the given LibreTranslate endpoint path.
     *
     * @param string $path     URL path, e.g. '/translate'.
     * @param array  $payload  Data to JSON-encode as the request body.
     * @return array|null      Decoded JSON response, or null on failure.
     */
    private function post(string $path, array $payload): ?array {
        $curl = new \curl(['ignoresecurity' => true]);
        $url = $this->baseurl . $path;
        $curl->setHeader(['Content-Type: application/json', 'Accept: application/json']);
        $raw = $curl->post($url, json_encode($payload));
        if ($curl->get_errno()) {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }
}
