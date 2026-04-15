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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/deepler/classes/vendor/autoload.php');

use DeepL\AppInfo;
use DeepL\AuthorizationException;
use DeepL\DeepLClient;
use DeepL\DeepLException;
use DeepL\Language;
use DeepL\LanguageCode;
use local_deepler\local\translation\abstract_translation_provider;
use local_deepler\local\translation\interfaces\glossary_capable_interface;
use local_deepler\local\translation\interfaces\rephrase_capable_interface;
use local_deepler\local\translation\interfaces\usage_capable_interface;
use local_deepler\local\translation\translation_language;
use local_deepler\local\translation\translation_result;

/**
 * Translation provider that wraps the DeepL PHP SDK.
 *
 * Implements all four capability interfaces:
 *   translation_provider_interface (core translate)
 *   rephrase_capable_interface     (DeepL Improve / paid accounts)
 *   glossary_capable_interface     (DeepL Glossaries)
 *   usage_capable_interface        (usage quota)
 *
 * The beta languages list and the rephrase-supported languages list remain
 * here as DeepL-specific concerns.
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class deepl_provider extends abstract_translation_provider
    implements rephrase_capable_interface, glossary_capable_interface, usage_capable_interface {

    /** @var DeepLClient|null */
    private ?DeepLClient $client = null;

    /** @var bool Whether the API key belongs to a free-tier account. */
    private bool $isfree = false;

    /** @var object|null Cached usage object. */
    private ?object $usage = null;

    /**
     * Language codes that support the DeepL Improve (rephrase) feature.
     *
     * @var string[]
     */
    private array $rephraselangs = ['DE', 'EN-GB', 'EN-US', 'ES', 'FR', 'IT', 'PT-BR', 'PT-PT'];

    /**
     * Beta languages not yet returned by the DeepL languages endpoint.
     * Remove entries as DeepL adds them to their official list.
     *
     * @var Language[]
     */
    private array $betalanguages = [];

    /**
     * Keys that are valid DeepL translateText() options.
     *
     * @var string[]
     */
    private const DEEPL_TRANSLATE_OPTIONS = [
        'context',
        'tag_handling',
        'split_sentences',
        'preserve_formatting',
        'formality',
        'outline_detection',
        'non_splitting_tags',
        'splitting_tags',
        'ignore_tags',
        'glossary',
        'model_type',
        'show_billed_characters',
    ];

    /**
     * @param string $apikey   DeepL API key (UUID format, optionally with :fx or :pro suffix).
     * @param array  $config   Optional: ['version' => string] for AppInfo header.
     */
    public function __construct(string $apikey, array $config = []) {
        parent::__construct($apikey, $config);
        $this->init_beta_languages();
    }

    /**
     * Initialises the DeepLClient. Called lazily on first use.
     *
     * @return bool  True on success.
     */
    private function ensure_client(): bool {
        if ($this->client !== null) {
            return true;
        }
        if (empty($this->apikey)) {
            return false;
        }
        try {
            $version = $this->config['version'] ?? '';
            $this->client = new DeepLClient($this->apikey, [
                'send_platform_info' => true,
                'app_info'           => new AppInfo('Moodle-Deepler', $version),
            ]);
            $this->isfree = DeepLClient::isAuthKeyFreeAccount($this->apikey);
            return true;
        } catch (AuthorizationException $e) {
            return false;
        }
    }

    // -------------------------------------------------------------------------
    // translation_provider_interface
    // -------------------------------------------------------------------------

    public function get_provider_id(): string {
        return 'deepl';
    }

    public function get_provider_name(): string {
        return 'DeepL';
    }

    /**
     * {@inheritdoc}
     *
     * Returns normalized translation_language objects.
     * Merges beta languages if the 'allowbeta' config is set.
     *
     * @return translation_language[]
     */
    public function get_source_languages(): array {
        if ($this->sourcelanguages !== null) {
            return $this->sourcelanguages;
        }
        if (!$this->ensure_client()) {
            return [];
        }
        try {
            $raw = $this->client->getSourceLanguages();
            $allowbeta = get_config('local_deepler', 'allowbeta');
            if ($allowbeta) {
                $raw = array_merge($raw, $this->betalanguages);
            }
            $this->sourcelanguages = array_map([$this, 'wrap_deepl_language'], $raw);
        } catch (DeepLException $e) {
            $this->sourcelanguages = [];
        }
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
        if (!$this->ensure_client()) {
            return [];
        }
        try {
            $raw = $this->client->getTargetLanguages();
            $allowbeta = get_config('local_deepler', 'allowbeta');
            if ($allowbeta) {
                $raw = array_merge($raw, $this->betalanguages);
            }
            $this->targetlanguages = array_map([$this, 'wrap_deepl_language'], $raw);
        } catch (DeepLException $e) {
            $this->targetlanguages = [];
        }
        return $this->targetlanguages;
    }

    /**
     * {@inheritdoc}
     *
     * @param string[] $texts
     * @param string   $sourcelang
     * @param string   $targetlang
     * @param array    $options
     * @return translation_result[]
     */
    public function translate(array $texts, string $sourcelang, string $targetlang, array $options = []): array {
        if (!$this->ensure_client()) {
            return [];
        }
        $mapped = $this->map_options($options);
        $results = $this->client->translateText($texts, $sourcelang ?: null, $targetlang, $mapped);
        $out = [];
        foreach ($results as $r) {
            $out[] = new translation_result(
                $r->text,
                $r->detectedSourceLanguage ?? '',
                $targetlang,
                $r->billedCharacters ?? 0
            );
        }
        return $out;
    }

    /**
     * Pass through all recognised DeepL options; strip unknown keys.
     *
     * @param array $options
     * @return array
     */
    public function map_options(array $options): array {
        $out = [];
        foreach (self::DEEPL_TRANSLATE_OPTIONS as $key) {
            if (array_key_exists($key, $options) && $options[$key] !== '' && $options[$key] !== null) {
                $out[$key] = $options[$key];
            }
        }
        return $out;
    }

    public function is_api_key_set(): bool {
        return !empty($this->apikey);
    }

    // -------------------------------------------------------------------------
    // rephrase_capable_interface
    // -------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     *
     * @param string[] $texts
     * @param string   $targetlang
     * @param array    $options   From build_rephrase_params().
     * @return translation_result[]
     */
    public function rephrase(array $texts, string $targetlang, array $options = []): array {
        if (!$this->ensure_client()) {
            return [];
        }
        $results = $this->client->rephraseText($texts, $targetlang, $options);
        $out = [];
        foreach ($results as $r) {
            $out[] = new translation_result(
                $r->text,
                $r->detectedSourceLanguage ?? '',
                $r->targetLanguage ?? $targetlang
            );
        }
        return $out;
    }

    public function get_rephrase_languages(): array {
        return $this->rephraselangs;
    }

    public function can_rephrase(): bool {
        $this->ensure_client();
        return !$this->isfree;
    }

    /**
     * {@inheritdoc}
     *
     * Delegates to DeepLClient::buildRephraseBodyParams() which validates
     * tone/style values against DeepL's allowed lists.
     *
     * @param string      $targetlang
     * @param string|null $style
     * @param string|null $tone
     * @return array
     */
    public function build_rephrase_params(string $targetlang, ?string $style, ?string $tone): array {
        if (!$this->ensure_client()) {
            return ['target_lang' => $targetlang];
        }
        return $this->client->buildRephraseBodyParams($targetlang, $style, $tone);
    }

    // -------------------------------------------------------------------------
    // glossary_capable_interface
    // -------------------------------------------------------------------------

    public function list_glossaries(): array {
        if (!$this->ensure_client()) {
            return [];
        }
        return $this->client->listGlossaries();
    }

    public function get_glossary(string $glossaryid): object {
        $this->ensure_client();
        return $this->client->getGlossary($glossaryid);
    }

    public function get_glossary_entries(string $glossaryid): object {
        $this->ensure_client();
        return $this->client->getGlossaryEntries($glossaryid);
    }

    public function delete_glossary(string $glossaryid): void {
        if (!$this->ensure_client()) {
            return;
        }
        $this->client->deleteGlossary($glossaryid);
    }

    // -------------------------------------------------------------------------
    // usage_capable_interface
    // -------------------------------------------------------------------------

    public function get_usage(): object {
        if ($this->usage !== null) {
            return $this->usage;
        }
        $this->ensure_client();
        $this->usage = $this->client->getUsage();
        return $this->usage;
    }

    public function is_free_account(): bool {
        $this->ensure_client();
        return $this->isfree;
    }

    // -------------------------------------------------------------------------
    // Accessors used by lang_helper
    // -------------------------------------------------------------------------

    /**
     * Returns the underlying DeepLClient for legacy code that still needs it.
     *
     * @return DeepLClient|null
     * @deprecated Use the interface methods instead.
     */
    public function get_client(): ?DeepLClient {
        $this->ensure_client();
        return $this->client;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Converts a DeepL Language object to a normalized translation_language.
     *
     * @param Language $lang
     * @return translation_language
     */
    private function wrap_deepl_language(Language $lang): translation_language {
        $code = $this->normalize_lang_code($lang->code);
        $supportsrephrase = in_array($code, $this->rephraselangs, true);
        $supportformality = property_exists($lang, 'supportsFormality') ? (bool) $lang->supportsFormality : false;
        return new translation_language($code, $lang->name, $supportformality, $supportsrephrase);
    }

    /**
     * Populate the beta languages list.
     * Remove individual entries as DeepL adds them to their official API response.
     */
    private function init_beta_languages(): void {
        $this->betalanguages = [
            new Language('Acehnese', 'ACE', null),
            new Language('Afrikaans', 'AF', null),
            new Language('Aragonese', 'AN', null),
            new Language('Assamese', 'AS', null),
            new Language('Aymara', 'AY', null),
            new Language('Azerbaijani', 'AZ', null),
            new Language('Bashkir', 'BA', null),
            new Language('Belarusian', 'BE', null),
            new Language('Bhojpuri', 'BHO', null),
            new Language('Bengali', 'BN', null),
            new Language('Breton', 'BR', null),
            new Language('Bosnian', 'BS', null),
            new Language('Catalan', 'CA', null),
            new Language('Cebuano', 'CEB', null),
            new Language('Kurdish (Sorani)', 'CKB', null),
            new Language('Welsh', 'CY', null),
            new Language('Esperanto', 'EO', null),
            new Language('Basque', 'EU', null),
            new Language('Persian', 'FA', null),
            new Language('Irish', 'GA', null),
            new Language('Galician', 'GL', null),
            new Language('Guarani', 'GN', null),
            new Language('Konkani', 'GOM', null),
            new Language('Gujarati', 'GU', null),
            new Language('Hausa', 'HA', null),
            new Language('Hindi', 'HI', null),
            new Language('Croatian', 'HR', null),
            new Language('Haitian Creole', 'HT', null),
            new Language('Armenian', 'HY', null),
            new Language('Igbo', 'IG', null),
            new Language('Icelandic', 'IS', null),
            new Language('Javanese', 'JV', null),
            new Language('Georgian', 'KA', null),
            new Language('Kazakh', 'KK', null),
            new Language('Kurdish (Kurmanji)', 'KMR', null),
            new Language('Kyrgyz', 'KY', null),
            new Language('Latin', 'LA', null),
            new Language('Luxembourgish', 'LB', null),
            new Language('Lombard', 'LMO', null),
            new Language('Lingala', 'LN', null),
            new Language('Maithili', 'MAI', null),
            new Language('Malagasy', 'MG', null),
            new Language('Maori', 'MI', null),
            new Language('Macedonian', 'MK', null),
            new Language('Malayalam', 'ML', null),
            new Language('Mongolian', 'MN', null),
            new Language('Marathi', 'MR', null),
            new Language('Malay', 'MS', null),
            new Language('Maltese', 'MT', null),
            new Language('Burmese', 'MY', null),
            new Language('Nepali', 'NE', null),
            new Language('Occitan', 'OC', null),
            new Language('Oromo', 'OM', null),
            new Language('Punjabi', 'PA', null),
            new Language('Pangasinan', 'PAG', null),
            new Language('Kapampangan', 'PAM', null),
            new Language('Dari', 'PRS', null),
            new Language('Pashto', 'PS', null),
            new Language('Quechua', 'QU', null),
            new Language('Sanskrit', 'SA', null),
            new Language('Sicilian', 'SCN', null),
            new Language('Albanian', 'SQ', null),
            new Language('Serbian', 'SR', null),
            new Language('Sesotho', 'ST', null),
            new Language('Sundanese', 'SU', null),
            new Language('Swahili', 'SW', null),
            new Language('Tamil', 'TA', null),
            new Language('Telugu', 'TE', null),
            new Language('Tajik', 'TG', null),
            new Language('Turkmen', 'TK', null),
            new Language('Tagalog', 'TL', null),
            new Language('Tswana', 'TN', null),
            new Language('Tsonga', 'TS', null),
            new Language('Tatar', 'TT', null),
            new Language('Urdu', 'UR', null),
            new Language('Uzbek', 'UZ', null),
            new Language('Wolof', 'WO', null),
            new Language('Xhosa', 'XH', null),
            new Language('Yiddish', 'YI', null),
            new Language('Cantonese', 'YUE', null),
            new Language('Zulu', 'ZU', null),
        ];
    }
}
