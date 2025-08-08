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

use core\user;
use DeepL\AuthorizationException;
use DeepL\DeepLClient;
use DeepL\DeepLException;
use DeepL\Language;
use DeepL\LanguageCode;
use Deepl\Usage;
use local_deepler\local\data\glossary;
use local_deepler\local\data\user_glossary;
use stdClass;

require_once(__DIR__ . '/../../vendor/autoload.php');

/**
 * Helper class to connect to Deepl's API, fetch the available langs etc.
 * as well as prepare the data for the html selects and AMD.
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
     * @var string The source language for deepl.
     */
    private string $deeplsourcelang;
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
    /**
     * @var string
     */
    private string $apikey;
    /** @var int the db id for the API key matching the user */
    private int $dbtokenid;
    /**
     * @var DeepLClient
     */
    private mixed $translator;
    /**
     * Languages available as source in Deepl's API.
     *
     * @var Language[]
     */
    private array $deeplsources;
    /**
     * Languages available as target in Deepl's API.
     *
     * @var Language[]
     */
    private array $deepltargets;
    /**
     * Deepl usage bound to the api key.
     *
     * @var Usage
     */
    protected Usage $usage;
    /**
     * Type of DeepL subscrription.
     *
     * @var bool
     */
    private bool $keyisfree;

    /**
     * @var bool
     */
    private bool $canimprove;
    /**
     * @var array|string[]
     */
    private array $deeplrephraselangs;
    /** @var \stdClass */
    private stdClass $user;

    /**
     * Constructor.
     *
     * @param \DeepL\DeepLClient|null $translator
     * @param string|null $apikey
     * @param array|null $moodlelangs
     * @param string|null $currentlang
     * @param string|null $targetlang
     * @throws \DeepL\DeepLException
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function __construct(
            ?DeepLClient $translator = null,
            ?string $apikey = null,
            ?array $moodlelangs = null,
            ?string $currentlang = null,
            ?string $targetlang = null
    ) {
        $this->deeplsources = [];
        $this->deepltargets = [];
        $this->deeplrephraselangs = ['de', 'en-GB', 'en-US', 'es', 'fr', 'it', 'pt-BR', 'pt-PT'];
        $this->canimprove = false;
        $this->currentlang = $currentlang ?? optional_param('lang', current_language(), PARAM_NOTAGS);
        $this->targetlang = $targetlang ?? optional_param('target_lang', '', PARAM_NOTAGS);
        if ($this->targetlang !== '') {
            $this->targetlang = LanguageCode::standardizeLanguageCode($this->targetlang);
        }
        $this->moodlelangs = $moodlelangs ?? get_string_manager()->get_list_of_translations();
        $this->deeplsourcelang = '';
        $this->apikey = $apikey ?? $this->initapikey();
        $this->dbtokenid = 0;
        $this->translator = $translator;
    }

    /**
     * Init global API key.
     *
     * @return string
     * @throws \dml_exception
     */
    private function initapikey(): string {
        $key = '';
        if (getenv('DEEPL_API_TOKEN')) {
            $key = getenv('DEEPL_API_TOKEN');
        } else if (get_config('local_deepler', 'apikey')) {
            $key = get_config('local_deepler', 'apikey');
        }
        return $key;

    }

    /**
     * Initialise the Deepl object.
     *
     * @param \stdClass $user
     * @return bool
     * @throws \DeepL\DeepLException
     * @throws \dml_exception
     */
    public function initdeepl(stdClass $user): bool {
        $this->user = $user;
        if (!$this->translator) {
            $this->setdeeplapi();
            $this->inittranslator();
        }

        try {
            $this->keyisfree = DeepLClient::isAuthKeyFreeAccount($this->apikey);
            $this->usage = $this->translator->getUsage();
            $this->canimprove = !$this->keyisfree;
            $this->deeplsources = $this->translator->getSourceLanguages();
            $this->deepltargets = $this->translator->getTargetLanguages();
            $this->setcurrentlanguage();
            return true;
        } catch (DeepLException $e) {
            return false;
        }
    }

    /**
     * Set the key string.
     * If empty, it will try to get it from the .env useful for tests runs.
     *
     * @return void
     * @throws \dml_exception
     */
    private function setdeeplapi(): void {
        $tokenrecord = $this->find_first_matching_token($this->user);
        if ($tokenrecord) {
            $this->apikey = $tokenrecord->token;
            $this->dbtokenid = $tokenrecord->id;
        } else if (!get_config('local_deepler', 'allowfallbackkey')) {
            $this->apikey = '';
        }
    }

    /**
     * Initialise the Deepl object.
     * Return a Boolean of the cnx status.
     *
     * @return bool
     * @throws \DeepL\DeepLException
     */
    private function inittranslator(): bool {
        if (!isset($this->translator)) {
            try {
                $this->translator = new DeepLClient($this->apikey, ['send_platform_info' => false]);
            } catch (AuthorizationException $e) {
                return false;
            }
        }
        return true;
    }
    /**
     * Set the source language.
     *
     * @return void
     * @throws \DeepL\DeepLException
     */
    private function setcurrentlanguage(): void {
        // Moodle format is not the common culture format.
        // Deepl's sources are ISO 639-1 (Alpha 2) and uppercase.
        $this->deeplsourcelang = LanguageCode::removeRegionalVariant(str_replace('_', '-', $this->currentlang));
    }

    /**
     * Finds the first available token for a user by looping through all tokens
     * and matching both standard and custom profile fields.
     *
     * @param \core_user|stdClass $user The Moodle user object.
     * @return stdClass|false The first matching token record, or false if none found.
     * @throws \dml_exception
     */
    private function find_first_matching_token($user) {
        global $DB;
        $foundtoken = false;
        $alluserfields = array_keys(utils::all_user_fields());

        // Build a map of custom profile fields for DB fallback.
        $customfields = [];
        foreach ($DB->get_records('user_info_field') as $field) {
            $customfields['profile_field_' . $field->shortname] = $field;
        }

        $tokens = $DB->get_records('local_deepler_tokens', null, 'id ASC');

        foreach ($tokens as $token) {
            $attr = $token->attribute;
            $pattern = (string) $token->valuefilter;

            // Check if the attribute is a user field.
            if (in_array($attr, $alluserfields)) {
                // If the user object has the property, compare directly.
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
                    // If not, and it's a custom profile field, fetch from DB.
                    $profiledata = $DB->get_record('user_info_data', [
                            'userid' => $user->id,
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
                            if (local_deepler_wildcard_match($pattern, $uservalue)) {
                                $foundtoken = $token;
                            }
                        } else if ($pattern === $uservalue) {
                            $foundtoken = $token;
                        }
                    }
                }
            }
        }
        return $foundtoken; // No matching token found.
    }

    /**
     * Find the Deepl langs that are supported by this moodle instance.
     *
     * @param array $deepls
     * @return array
     */
    private function finddeeplsformoodle(array $deepls): array {
        return array_filter($deepls, function($item) {
            foreach (array_keys($this->moodlelangs) as $moodlecode) {
                $moodle = strtolower(str_replace('_', '-', $moodlecode));
                $deepl = strtolower($item->code);
                if (stripos($deepl, $moodle) !== false) {
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * Injects lang attributes to the config object.
     *
     * @param \stdClass $config
     * @return \stdClass
     */
    public function prepareconfig(stdClass &$config): stdClass {
        $config->usage = $this->usage;
        $config->limitReached = $config->usage->anyLimitReached();
        $config->targetlang = $this->targetlang;
        $config->currentlang = $this->currentlang;
        $config->deeplsourcelang = $this->deeplsourcelang;
        $config->isfree = $this->keyisfree;
        $config->rephrasesymbol = self::REPHRASESYMBOL;
        $config->canimprove = $this->canimprove;
        return $config;
    }

    /**
     * Prepare the strings for the UI as JSON.
     *
     * @return string
     * @throws \coding_exception
     */
    public function preparestrings(): string {
        // Status strings for UI icons.
        $config = new stdClass();
        $config->statusstrings = new stdClass();
        $config->statusstrings->failed = get_string('statusfailed', 'local_deepler');
        $config->statusstrings->success = get_string('statussuccess', 'local_deepler');
        $config->statusstrings->tosave = get_string('statustosave', 'local_deepler');
        $config->statusstrings->totranslate = get_string('statustotranslate', 'local_deepler');
        $config->statusstrings->wait = get_string('statuswait', 'local_deepler');
        // General UI strings.
        $config->uistrings = new stdClass();
        $config->uistrings->deeplapiexception = get_string('deeplapiexception', 'local_deepler');
        $config->uistrings->errordbpartial = get_string('errordbpartial', 'local_deepler');
        $config->uistrings->errordbtitle = get_string('errordbtitle', 'local_deepler');
        $config->uistrings->errortoolong = get_string('errortoolong', 'local_deepler');
        $config->uistrings->saveallmodaltitle = get_string('saveallmodaltitle', 'local_deepler');
        $config->uistrings->saveallmodalbody = get_string('saveallmodalbody', 'local_deepler');
        $config->uistrings->canttranslatesame = get_string('canttranslatesame', 'local_deepler');
        return json_encode($config);
    }

    /**
     * Checks if a given lang is supported by Deepl
     *
     * @param string $lang
     * @return bool
     * @throws \DeepL\DeepLException
     */
    public function islangsupported(string $lang) {
        $list = $this->deeplsources;
        $len = count($list);
        while ($len--) {
            $code = LanguageCode::standardizeLanguageCode($list[$len]->code);
            if ($code === $lang || $code === strtolower($lang)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if source language is supported.
     *
     * @return bool
     */
    public function iscurrentsupported(): bool {
        return $this->islangsupported($this->deeplsourcelang);
    }

    /**
     * If key empty string.
     *
     * @return bool
     */
    public function isapikeynoset(): bool {
        return $this->apikey === '' || $this->apikey === null || $this->apikey === 'DEFAULT';
    }

    /**
     * Check if source is same as target. Might call the rephrase instead.
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
     * Getter for deeplsourcelang.
     *
     * @return string
     */
    public function get_deeplsourcelang(): string {
        return $this->deeplsourcelang;
    }

    /**
     * Sub function to list the options.
     *
     * @param bool $issource
     * @param mixed $l
     * @param bool $isverbose
     * @return array
     */
    private function getoption(bool $issource, mixed $l, bool $isverbose = true): array {
        // If the key is free, we can't improve the source lang.
        $code = LanguageCode::standardizeLanguageCode($l->code);
        $same = $issource ? $this->isrephrase($code, '') : $this->isrephrase('', $code);
        $text = $isverbose ? $l->name : $code;
        $langisrephrasable = in_array($code, $this->deeplrephraselangs, true);

        if ($issource) {
            $selected = $this->isrephrase($code, $this->deeplsourcelang);
            $disable = !$selected && ($same && !$this->canimprove || $same && !$langisrephrasable);
        } else {
            $selected = $this->targetlang !== '' && $this->isrephrase($code, $this->targetlang);
            $disable = ($same && !$langisrephrasable) || ($same && !$this->canimprove);
        }
        if ($same && $this->canimprove) {
            $text = self::REPHRASESYMBOL . $text;
            $code = self::REPHRASESYMBOL . $code;
        }
        return [
                'code' => $code,
                'lang' => $text,
                'verbose' => $l->name,
                'selected' => $selected,
                'disabled' => $disable,
        ];
    }

    /**
     * Create HTML props for select.
     *
     * @param array $tab
     * @return string
     * TODO MDL-0000 allow regional languages setup (expl EN-GB)
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
            $list .= $item['lang'] . '</option>';
        }
        return $list;
    }

    /**
     * Prepare dropdown options for targets.
     *
     * @return string
     */
    public function preparehtmltagets(): string {
        return $this->preparehtmlotions($this->prepareoptionlangs($this->finddeeplsformoodle($this->deepltargets), false));
    }

    /**
     * Prepare dropdown options for sources.
     *
     * @return string
     */
    public function preparehtmlsources(): string {
        return $this->preparehtmlotions($this->prepareoptionlangs($this->finddeeplsformoodle($this->deeplsources), true, false));
    }

    /**
     * Creates props for html selects.
     *
     * @param array $filtereddeepls
     * @param bool $issource
     * @param bool $verbose
     * @return array
     */
    private function prepareoptionlangs(array $filtereddeepls, bool $issource = true, bool $verbose = true): array {
        $tab = [];
        // Get the list of deepl langs that are supported by this moodle instance.
        foreach ($filtereddeepls as $l) {
            $tab[] = $this->getoption($issource, $l, $verbose);
        }
        return $tab;
    }

    /**
     * Prepare source options.
     *
     * @return array
     */
    public function preparesourcesoptionlangs(): array {
        return $this->prepareoptionlangs($this->finddeeplsformoodle($this->deeplsources));
    }

    /**
     *  Prepare target options.
     *
     * @return array
     */
    public function preparetargetsoptionlangs(): array {
        return $this->prepareoptionlangs($this->finddeeplsformoodle($this->deepltargets), false);
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
     * Getter for deeplrephraselangs.
     *
     * @return array
     */
    public function get_deeplrephraselangs(): array {
        return $this->deeplrephraselangs;
    }

    /**
     * Lists the compatible moodle langs for the current target lang.
     *
     * @return array
     * @throws \DeepL\DeepLException
     */
    public function findcompatiblelangs(): array {
        if ($this->targetlang === '') {
            return [];
        }
        $langroot = LanguageCode::removeRegionalVariant($this->targetlang);

        $compatibles = [];
        foreach (array_keys($this->moodlelangs) as $code) {
            if (str_contains($code, $langroot)) {
                $compatibles[] = $code;
            }
        }
        // Sort the array by the lang code, starting with the simplest one obviously.
        asort($compatibles);
        $tab = [];
        foreach ($compatibles as $item) {
            $tab[] = $item;
        }
        return $tab;
    }

    /**
     * Getter translator.
     *
     * @return \DeepL\DeepLClient|null
     */
    public function gettranslator(): ?DeepLClient {
        return $this->translator;
    }

    /**
     * Getter for main key.
     *
     * @return string
     */
    public function getapikey(): string {
        return $this->apikey;
    }

    /**
     * Getter for source langs.
     *
     * @return array|\DeepL\Language[]
     */
    public function getsourcelanguages(): array {
        return $this->deeplsources;
    }

    /**
     * Getter for target langs.
     *
     * @return array|\DeepL\Language[]
     */
    public function gettargelanguages(): array {
        return $this->deepltargets;
    }

    /**
     * Getter for Usage.
     *
     * @return \Deepl\Usage
     */
    public function getusage(): Usage {
        return $this->usage;
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
     * Getter for ability  to use the improve API.
     *
     * @return bool
     */
    public function getcanimprove(): bool {
        return $this->canimprove;
    }

    /**
     * Fetches all glossaries.
     *
     * @return array
     * @throws \DeepL\DeepLException
     */
    public function getalldeeplglossaries(): array {
        return $this->translator->listGlossaries();
    }

    /**
     * Adds a DeepL glossary if not yet stored in DB.
     *
     * @param array $deeplglossaries
     * @return void
     * @throws \dml_exception
     */
    public function adddeeplglossariesifunknown(array $deeplglossaries): void {
        /** @var \DeepL\GlossaryInfo $deeplglossary */
        foreach ($deeplglossaries as $deeplglossary) {
            if (!glossary::exists($deeplglossary->glossaryId)) {
                glossary::create(new glossary(
                        $deeplglossary->glossaryId,
                        $deeplglossary->name,
                        $deeplglossary->sourceLang,
                        $deeplglossary->targetLang,
                        $deeplglossary->entryCount
                ));
            }
        }
    }

    /**
     * Return all glossaries for current user.
     *
     * @return array
     * @throws \dml_exception
     */
    public function getusersglossaries(): array {
        $glos = [];
        $pivot = user_glossary::getallbyuser($this->user->id);

        foreach ($pivot as $item) {
            $glos[] = glossary::getbyid($item->glossaryid);
        }
        return $glos;
    }

    /**
     * Get all glossaries uploaded by translators of the same pool (sharing the same api token).
     *
     * @param array|null $except
     * @return array
     * @throws \dml_exception
     */
    public function getpoolglossaries(?array $except = []): array {
        // Build a set of IDs to avoid duplicates.
        $ids = array_map(fn($o) => $o->glossaryid, $except);
        $poolglossaries = glossary::getallbytokenid($this->dbtokenid);
        // Filter glossaries not build by user.
        return array_filter($poolglossaries, fn($glo) => !in_array($glo->glossaryid, $ids));
    }

    /**
     * Get all dictionaries except those bound to an api token.
     *
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function getpublicglossaries() {
        return glossary::getpublicexcepttokenid($this->dbtokenid);
    }

    /**
     * Delete user's glossary.
     *
     * @param int $glossarydbid
     * @return bool|null
     * @throws \DeepL\DeepLException
     * @throws \dml_exception
     */
    public function deleteglossary(int $glossarydbid): ?bool {
        $guid = user_glossary::getbyuserandglossary($this->user->id, $glossarydbid);
        if ($guid) {
            // Public glossaries downloaded from DeepL do not have users.
            $delete = user_glossary::delete($guid->id);
        }
        $glo = glossary::getbyid($glossarydbid);
        $success = $this->translator->deleteglossary($glo->glossaryid);
        $deleted = glossary::delete($glossarydbid);
        return $success && $deleted;
    }

    /**
     * Getter for the current token id.
     *
     * @return int
     */
    public function getdbtokenid(): int {
        return $this->dbtokenid;
    }
}
