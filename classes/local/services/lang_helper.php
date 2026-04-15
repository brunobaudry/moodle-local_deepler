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
defined('MOODLE_INTERNAL') || die();

use context_user;
use local_deepler\local\data\glossary;
use local_deepler\local\data\user_glossary;
use local_deepler\local\translation\interfaces\glossary_capable_interface;
use local_deepler\local\translation\interfaces\rephrase_capable_interface;
use local_deepler\local\translation\interfaces\translation_provider_interface;
use local_deepler\local\translation\interfaces\usage_capable_interface;
use local_deepler\local\translation\translation_language;
use local_deepler\local\translation\translation_provider_factory;
use stdClass;

/**
 * Helper class that orchestrates provider initialisation, language selection,
 * and UI data preparation (dropdowns, config objects, string packs).
 *
 * All direct DeepL SDK calls have been removed. Provider-specific logic lives
 * in translation_provider_interface implementations under
 * classes/local/translation/providers/.
 *
 * @package local_deepler
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lang_helper {
    /**
     * Constant to display the lang as rephrasing.
     */
    const REPHRASESYMBOL = "® ";

    /**
     * The current moodle language.
     *
     * @var string
     */
    public string $currentlang;

    /**
     * The target language.
     *
     * @var string
     */
    public string $targetlang;

    /**
     * @var array|mixed Moodle instance's installed languages.
     */
    public mixed $moodlelangs;

    // -------------------------------------------------------------------------
    // Private / protected state
    // -------------------------------------------------------------------------

    /** @var translation_provider_interface|null */
    private ?translation_provider_interface $provider = null;

    /** @var string Resolved API key (before provider is created). */
    private string $apikey;

    /** @var int DB row id of the matched token record (0 = global key). */
    private int $dbtokenid;

    /** @var translation_language[] Source languages returned by the provider. */
    private array $deeplsources;

    /** @var translation_language[] Target languages returned by the provider. */
    private array $deepltargets;

    /** @var string Normalised source language code derived from the Moodle current lang. */
    private string $deeplsourcelang;

    /** @var bool Whether the current provider/account supports rephrasing. */
    private bool $canimprove;

    /** @var stdClass */
    private stdClass $user;

    // -------------------------------------------------------------------------
    // Constructor
    // -------------------------------------------------------------------------

    /**
     * Constructor.
     *
     * The optional $provider parameter is used in tests to inject a mock.
     * In production code lang_helper is always constructed without arguments
     * and the provider is created by initdeepl().
     *
     * @param translation_provider_interface|null $provider  Pre-built provider (tests only).
     * @param string|null                         $apikey    Override API key.
     * @param array|null                          $moodlelangs Override Moodle language list.
     * @param string|null                         $currentlang Override current language.
     * @param string|null                         $targetlang  Override target language.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function __construct(
        ?translation_provider_interface $provider = null,
        ?string $apikey = null,
        ?array $moodlelangs = null,
        ?string $currentlang = null,
        ?string $targetlang = null
    ) {
        $this->deeplsources    = [];
        $this->deepltargets    = [];
        $this->canimprove      = false;
        $this->provider        = $provider;
        $this->currentlang     = $currentlang ?? optional_param('lang', current_language(), PARAM_NOTAGS);
        $this->targetlang      = $targetlang ?? optional_param('target_lang', '', PARAM_NOTAGS);
        if ($this->targetlang !== '') {
            $this->targetlang = $this->normalize_lang_code($this->targetlang);
        }
        $this->moodlelangs  = $moodlelangs ?? get_string_manager()->get_list_of_translations();
        $this->deeplsourcelang = '';
        $this->apikey       = $apikey ?? $this->initapikey();
        $this->dbtokenid    = 0;
    }

    // -------------------------------------------------------------------------
    // Provider initialisation
    // -------------------------------------------------------------------------

    /**
     * Initialises the translation provider and fetches language lists / usage.
     *
     * This is the main entry point called from translate.php and external API classes.
     *
     * @param stdClass $user
     * @param string   $version  Plugin version string (forwarded to provider for AppInfo).
     * @return bool  True on success, false if provider could not be initialised.
     * @throws \dml_exception
     */
    public function initdeepl(stdClass $user, string $version): bool {
        return $this->init_provider($user, $version);
    }

    /**
     * Initialises the translation provider and fetches language lists / usage.
     *
     * @param stdClass $user
     * @param string   $version
     * @return bool
     * @throws \dml_exception
     */
    public function init_provider(stdClass $user, string $version): bool {
        $this->user = $user;

        if ($this->provider === null) {
            $this->resolve_api_key_for_user();
            $providerid = translation_provider_factory::get_configured_provider_id();
            $this->provider = translation_provider_factory::make($providerid, $this->apikey, $version);
        }

        if ($this->provider === null || !$this->provider->is_api_key_set()) {
            return false;
        }

        $sources = $this->provider->get_source_languages();
        $targets = $this->provider->get_target_languages();

        if (empty($sources) && empty($targets)) {
            return false;
        }

        $this->deeplsources = $sources;
        $this->deepltargets = $targets;
        $this->canimprove   = ($this->provider instanceof rephrase_capable_interface)
            && $this->provider->can_rephrase();

        $this->setcurrentlanguage();
        return true;
    }

    /**
     * Resolves the API key to use for the current user.
     *
     * For DeepL, checks the token-pool table to find a user-specific key.
     * For other providers, the global config key is used.
     *
     * @return void
     * @throws \dml_exception|\coding_exception
     */
    private function resolve_api_key_for_user(): void {
        $providerid = translation_provider_factory::get_configured_provider_id();
        if ($providerid !== 'deepl') {
            return;
        }
        $this->setdeeplapi();
    }

    /**
     * Resolves the DeepL API key from the token-pool table for the current user.
     *
     * @return void
     * @throws \dml_exception|\coding_exception
     */
    private function setdeeplapi(): void {
        global $DB;
        $tokens = $DB->get_records('local_deepler_tokens', null, 'id ASC');

        if (empty($tokens)) {
            return;
        }
        $tokenrecord = $this->find_first_matching_token($this->user, $tokens);
        if ($tokenrecord) {
            $this->apikey    = $tokenrecord->token;
            $this->dbtokenid = $tokenrecord->id;
        } else if (!get_config('local_deepler', 'allowfallbackkey')) {
            $this->apikey = '';
        }
    }

    /**
     * Finds the first available token for a user by looping through all tokens
     * and matching both standard and custom profile fields.
     *
     * @param \core_user|stdClass $user    The Moodle user object.
     * @param array               $tokens  Array of token records to search through.
     * @return stdClass|false The first matching token record, or false if none found.
     * @throws \dml_exception|\coding_exception
     */
    private function find_first_matching_token(\core_user|stdClass $user, array $tokens): false|stdClass {
        global $DB;
        $foundtoken    = false;
        $alluserfields = array_keys(utils::all_user_fields(context_user::instance($this->user->id, MUST_EXIST)));

        $customfields = [];
        foreach ($DB->get_records('user_info_field') as $field) {
            $customfields['profile_field_' . $field->shortname] = $field;
        }

        foreach ($tokens as $token) {
            $attr    = $token->attribute;
            $pattern = (string) $token->valuefilter;

            if (in_array($attr, $alluserfields)) {
                if (property_exists($user, $attr)) {
                    $uservalue = (string) $user->$attr;
                    if (
                        ($pattern === $uservalue) ||
                        (strpos($pattern, '%') !== false) ||
                        (strpos($pattern, '*') !== false) ||
                        (strpos($pattern, '_') !== false)
                    ) {
                        if (utils::wildcard_match($pattern, $uservalue)) {
                            $foundtoken = $token;
                        }
                    } else if ($pattern === $uservalue) {
                        $foundtoken = $token;
                    }
                } else if (array_key_exists($attr, $customfields) && !empty($user->id)) {
                    $profiledata = $DB->get_record('user_info_data', [
                        'userid'  => $user->id,
                        'fieldid' => $customfields[$attr]->id,
                    ]);
                    if ($profiledata) {
                        $uservalue = (string) $profiledata->data;
                        if (
                            ($pattern === $uservalue) ||
                            (strpos($pattern, '%') !== false) ||
                            (strpos($pattern, '*') !== false) ||
                            (strpos($pattern, '_') !== false)
                        ) {
                            if (utils::wildcard_match($pattern, $uservalue)) {
                                $foundtoken = $token;
                            }
                        } else if ($pattern === $uservalue) {
                            $foundtoken = $token;
                        }
                    }
                }
            }
        }
        return $foundtoken;
    }

    /**
     * Derives the normalised DeepL-style source language code from the Moodle current lang.
     *
     * Moodle stores lang as 'en', 'fr', 'de', 'pt_br' etc.
     * We need 'EN', 'FR', 'DE', 'PT' (base code, uppercase).
     *
     * @return void
     */
    private function setcurrentlanguage(): void {
        $code = $this->normalize_lang_code($this->currentlang);
        $this->deeplsourcelang = $this->remove_regional_variant($code);
    }

    /**
     * Normalizes a language code to uppercase with dashes.
     *
     * @param string $code
     * @return string
     */
    private function normalize_lang_code(string $code): string {
        return strtoupper(str_replace('_', '-', trim($code)));
    }

    /**
     * Strips the regional variant from a language code.
     *
     * @param string $code  e.g. 'EN-GB'
     * @return string  e.g. 'EN'
     */
    private function remove_regional_variant(string $code): string {
        return explode('-', $code, 2)[0];
    }

    // -------------------------------------------------------------------------
    // Initialisation helpers (kept for backward compat, now trivial)
    // -------------------------------------------------------------------------

    /**
     * Reads the global API key from env / config.
     *
     * @return string
     * @throws \dml_exception
     */
    private function initapikey(): string {
        if (getenv('DEEPL_API_TOKEN')) {
            return getenv('DEEPL_API_TOKEN');
        }
        return (string) (get_config('local_deepler', 'apikey') ?: '');
    }

    // -------------------------------------------------------------------------
    // Config / UI preparation
    // -------------------------------------------------------------------------

    /**
     * Injects provider state into the config object for AMD JavaScript.
     *
     * @param stdClass $config
     * @return stdClass
     */
    public function prepareconfig(stdClass &$config): stdClass {
        if ($this->provider instanceof usage_capable_interface) {
            $usage = $this->provider->get_usage();
            $config->usage        = $usage;
            $config->limitReached = $usage->anyLimitReached();
            $config->isfree       = $this->provider->is_free_account();
        } else {
            $config->usage        = (object) ['character' => (object) ['count' => 0, 'limit' => 0]];
            $config->limitReached = false;
            $config->isfree       = false;
        }
        $config->targetlang      = $this->targetlang;
        $config->currentlang     = $this->currentlang;
        $config->deeplsourcelang = $this->deeplsourcelang;
        $config->rephrasesymbol  = self::REPHRASESYMBOL;
        $config->canimprove      = $this->canimprove;
        return $config;
    }

    /**
     * Prepare the strings for the UI as JSON.
     *
     * @return string
     * @throws \coding_exception
     */
    public function preparestrings(): string {
        $config = new stdClass();
        $config->statusstrings = new stdClass();
        $config->statusstrings->failed     = get_string('statusfailed', 'local_deepler');
        $config->statusstrings->success    = get_string('statussuccess', 'local_deepler');
        $config->statusstrings->tosave     = get_string('statustosave', 'local_deepler');
        $config->statusstrings->totranslate = get_string('statustotranslate', 'local_deepler');
        $config->statusstrings->wait       = get_string('statuswait', 'local_deepler');
        $config->uistrings = new stdClass();
        $config->uistrings->deeplapiexception  = get_string('deeplapiexception', 'local_deepler');
        $config->uistrings->errordbpartial     = get_string('errordbpartial', 'local_deepler');
        $config->uistrings->errordbtitle       = get_string('errordbtitle', 'local_deepler');
        $config->uistrings->errortoolong       = get_string('errortoolong', 'local_deepler');
        $config->uistrings->saveallmodaltitle  = get_string('saveallmodaltitle', 'local_deepler');
        $config->uistrings->translatemodaltitle = get_string('translate:modal:title', 'local_deepler');
        $config->uistrings->translatemodalbody  = get_string('translate:modal:body', 'local_deepler');
        $config->uistrings->saveallmodalbody    = get_string('saveallmodalbody', 'local_deepler');
        $config->uistrings->canttranslatesame   = get_string('canttranslatesame', 'local_deepler');
        return json_encode($config);
    }

    // -------------------------------------------------------------------------
    // Language helpers
    // -------------------------------------------------------------------------

    /**
     * Checks if source language is supported by the active provider.
     *
     * @return bool
     */
    public function iscurrentsupported(): bool {
        return $this->islangsupported($this->deeplsourcelang);
    }

    /**
     * Checks if a given language code is supported by the active provider.
     *
     * @param string $lang
     * @return bool
     */
    public function islangsupported(string $lang): bool {
        if ($this->provider === null) {
            return false;
        }
        return $this->provider->is_language_supported($lang);
    }

    /**
     * Returns whether the API key / base URL is configured.
     *
     * @return bool
     */
    public function isapikeynoset(): bool {
        if ($this->provider !== null) {
            return !$this->provider->is_api_key_set();
        }
        return $this->apikey === '' || $this->apikey === null || $this->apikey === 'DEFAULT';
    }

    /**
     * Getter for the normalised source language code.
     *
     * @return string
     */
    public function get_deeplsourcelang(): string {
        return $this->deeplsourcelang;
    }

    /**
     * Prepare source language dropdown options.
     *
     * @return array
     */
    public function preparesourcesoptionlangs(): array {
        return $this->prepareoptionlangs($this->finddeeplsformoodle($this->deeplsources), true, false);
    }

    /**
     * Prepare target language dropdown options.
     *
     * @return array
     */
    public function preparetargetsoptionlangs(): array {
        return $this->prepareoptionlangs($this->finddeeplsformoodle($this->deepltargets), false);
    }

    /**
     * Creates option array for HTML selects.
     *
     * @param translation_language[] $filteredlangs
     * @param bool                   $issource
     * @param bool                   $verbose
     * @return array
     */
    private function prepareoptionlangs(array $filteredlangs, bool $issource = true, bool $verbose = true): array {
        $tab = [];
        foreach ($filteredlangs as $l) {
            $tab[] = $this->getoption($issource, $l, $verbose);
        }
        return $tab;
    }

    /**
     * Builds the data array for a single language option element.
     *
     * @param bool               $issource
     * @param translation_language $l
     * @param bool               $isverbose
     * @return array
     */
    private function getoption(bool $issource, translation_language $l, bool $isverbose = true): array {
        $code             = $l->code;
        $same             = $issource ? $this->isrephrase($code, '') : $this->isrephrase('', $code);
        $text             = $isverbose ? $l->name : $code;
        $langisrephrasable = $l->supports_rephrase;

        if ($issource) {
            $selected = $this->isrephrase($code, $this->deeplsourcelang);
            $disable  = !$selected && ($same && !$this->canimprove || $same && !$langisrephrasable);
        } else {
            $selected = $this->targetlang !== '' && $this->isrephrase($code, $this->targetlang);
            $disable  = ($same && !$langisrephrasable) || ($same && !$this->canimprove);
        }
        if ($same && $this->canimprove) {
            $text = self::REPHRASESYMBOL . $text;
            $code = self::REPHRASESYMBOL . $code;
        }
        return [
            'code'     => $code,
            'lang'     => $text,
            'verbose'  => $l->name,
            'selected' => $selected,
            'disabled' => $disable,
        ];
    }

    /**
     * Check if source is same as target (rephrase / improve mode).
     *
     * @param string $source
     * @param string $target
     * @return bool
     */
    public function isrephrase(string $source = '', string $target = ''): bool {
        $s = $source === '' ? $this->deeplsourcelang : $source;
        $t = $target === '' ? $this->targetlang : $target;
        return str_contains($t, $s);
    }

    /**
     * Filters a provider language list to only those installed in this Moodle instance.
     *
     * @param translation_language[] $langs
     * @return translation_language[]
     */
    private function finddeeplsformoodle(array $langs): array {
        return array_values(array_filter($langs, function (translation_language $item): bool {
            foreach (array_keys($this->moodlelangs) as $moodlecode) {
                $moodle   = strtolower(str_replace('_', '-', $moodlecode));
                $provider = strtolower($item->code);
                if (stripos($provider, $moodle) !== false) {
                    return true;
                }
            }
            return false;
        }));
    }

    /**
     * Lists the compatible Moodle langs for the current target lang.
     *
     * @return array
     */
    public function findcompatiblelangs(): array {
        if ($this->targetlang === '') {
            return [];
        }
        $langroot    = $this->remove_regional_variant($this->targetlang);
        $compatibles = [];
        foreach (array_keys($this->moodlelangs) as $code) {
            if (str_contains(strtoupper($code), $langroot)) {
                $compatibles[] = $code;
            }
        }
        asort($compatibles);
        return array_values($compatibles);
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    /**
     * Returns the active translation provider.
     *
     * @return translation_provider_interface|null
     */
    public function get_provider(): ?translation_provider_interface {
        return $this->provider;
    }

    /**
     * Returns the underlying DeepLClient for legacy code that still needs it.
     *
     * Returns null if the provider is not deepl_provider.
     *
     * @return \DeepL\DeepLClient|null
     * @deprecated Use get_provider() instead.
     */
    public function gettranslator() {
        if ($this->provider instanceof \local_deepler\local\translation\providers\deepl_provider) {
            return $this->provider->get_client();
        }
        return null;
    }

    /**
     * Returns the API usage object.
     *
     * @return object|null  null if the provider does not support usage reporting.
     */
    public function getusage(): ?object {
        if ($this->provider instanceof usage_capable_interface) {
            return $this->provider->get_usage();
        }
        return null;
    }

    /**
     * Getter for source languages.
     *
     * @return translation_language[]
     */
    public function getsourcelanguages(): array {
        return $this->deeplsources;
    }

    /**
     * Getter for target languages.
     *
     * @return translation_language[]
     */
    public function gettargelanguages(): array {
        return $this->deepltargets;
    }

    /**
     * Getter for canimprove.
     *
     * @return bool
     */
    public function get_canimprove(): bool {
        return $this->canimprove;
    }

    /**
     * Getter for canimprove (legacy alias).
     *
     * @return bool
     */
    public function getcanimprove(): bool {
        return $this->canimprove;
    }

    /**
     * Returns rephrase-supported language codes from the provider.
     *
     * @return string[]
     */
    public function get_deeplrephraselangs(): array {
        if ($this->provider instanceof rephrase_capable_interface) {
            return $this->provider->get_rephrase_languages();
        }
        return [];
    }

    /**
     * Getter for main API key.
     *
     * @return string
     */
    public function getapikey(): string {
        return $this->apikey;
    }

    /**
     * Getter for current lang.
     *
     * @param bool $mainlonly
     * @return string
     */
    public function getcurrentlang(bool $mainlonly = false): string {
        return $mainlonly ? substr($this->currentlang, 0, 2) : $this->currentlang;
    }

    /**
     * Getter for chosen target.
     *
     * @param bool $mainlonly
     * @return string
     */
    public function gettargetlang(bool $mainlonly = false): string {
        return $mainlonly ? substr($this->targetlang, 0, 2) : $this->targetlang;
    }

    /**
     * Getter for the current token id.
     *
     * @return int
     */
    public function getdbtokenid(): int {
        return $this->dbtokenid;
    }

    // -------------------------------------------------------------------------
    // Glossary management (delegates to glossary_capable_interface)
    // -------------------------------------------------------------------------

    /**
     * Adds provider glossaries that are not yet stored in DB.
     *
     * @param array $providerglossaries  Raw glossary objects from the provider.
     * @return void
     * @throws \dml_exception
     */
    public function adddeeplglossariesifunknown(array $providerglossaries): void {
        foreach ($providerglossaries as $g) {
            if (!glossary::exists($g->glossaryId)) {
                glossary::create(new glossary(
                    $g->glossaryId,
                    $g->name,
                    $g->sourceLang,
                    $g->targetLang,
                    $g->entryCount
                ));
            }
        }
    }

    /**
     * Return all glossaries for the current user.
     *
     * @return array
     * @throws \dml_exception
     */
    public function getusersglossaries(): array {
        $glos  = [];
        $pivot = user_glossary::getallbyuser($this->user->id);
        foreach ($pivot as $item) {
            $glos[] = glossary::getbyid($item->glossaryid);
        }
        return $glos;
    }

    /**
     * Get all glossaries uploaded by translators sharing the same API token.
     *
     * @param array|null $except
     * @return array
     * @throws \dml_exception
     */
    public function getpoolglossaries(?array $except = []): array {
        $ids          = array_map(fn($o) => $o->glossaryid, $except);
        $poolglossaries = glossary::getallbytokenid($this->dbtokenid);
        return array_filter($poolglossaries, fn($glo) => !in_array($glo->glossaryid, $ids));
    }

    /**
     * Get all dictionaries except those bound to an API token.
     *
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function getpublicglossaries(): array {
        return glossary::getpublicexcepttokenid($this->dbtokenid);
    }

    /**
     * Syncs provider glossaries with local DB: adds missing, removes deleted.
     *
     * Only meaningful when the provider implements glossary_capable_interface.
     *
     * @return glossary[]
     * @throws \dml_exception
     */
    public function syncdeeplglossaries(): array {
        if (!($this->provider instanceof glossary_capable_interface)) {
            return glossary::getall('', '');
        }

        $providerglossaries = $this->provider->list_glossaries();
        $glossariesallids   = glossary::getall_ids();
        $pluginsgloids      = array_map(fn($o) => $o->glossaryid, $glossariesallids);

        foreach ($providerglossaries as $g) {
            if (!in_array($g->glossaryId, $pluginsgloids)) {
                glossary::create(new glossary(
                    $g->glossaryId,
                    $g->name,
                    $g->sourceLang,
                    $g->targetLang,
                    $g->entryCount
                ));
            }
        }

        $providerids         = array_map(fn($o) => $o->glossaryId, $providerglossaries);
        $pluginidsnotinprovider = array_filter(
            $glossariesallids,
            fn($obj) => !in_array($obj->glossaryid, $providerids)
        );
        foreach (array_map(fn($o) => $o->id, $pluginidsnotinprovider) as $deleteme) {
            $this->deleteglossary($deleteme, true);
        }

        return glossary::getall('', '');
    }

    /**
     * Delete a glossary from the provider and/or DB.
     *
     * @param int  $glossarydbid  Local DB row ID.
     * @param bool $dbonly        If true, only removes from DB (not from provider).
     * @return bool|null
     * @throws \dml_exception
     */
    public function deleteglossary(int $glossarydbid, bool $dbonly = false): ?bool {
        $guid    = user_glossary::getbyuserandglossary($this->user->id, $glossarydbid);
        $success = $dbonly;
        if ($guid) {
            user_glossary::delete($guid->id);
        }
        $glo = glossary::getbyid($glossarydbid);
        if (!$dbonly && $this->provider instanceof glossary_capable_interface) {
            try {
                $this->provider->delete_glossary($glo->glossaryid);
                $success = true;
            } catch (\Throwable $e) {
                $success = false;
            }
        }
        $deleted = glossary::delete($glossarydbid);
        return $success && $deleted;
    }

    // -------------------------------------------------------------------------
    // Private HTML builder (kept for legacy renderers)
    // -------------------------------------------------------------------------

    /**
     * Create HTML props for select.
     *
     * @param array $tab
     * @return string
     * TODO MDL-0000 allow regional languages setup (e.g. EN-GB)
     */
    private function preparehtmlotions(array $tab): string {
        $list = '';
        foreach ($tab as $item) {
            $list .= '<option value="' . $item['code'] . '"';
            if ($item['selected']) {
                $list .= ' selected ';
            }
            if ($item['disabled']) {
                $list .= ' disabled ';
            }
            $list .= ' data-initial-value="' . $item['code'] . '">' . $item['lang'] . '</option>';
        }
        return $list;
    }
}
