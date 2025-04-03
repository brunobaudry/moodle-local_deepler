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

use DeepL\DeepLClient;
use DeepL\Language;
use DeepL\LanguageCode;
use Deepl\Usage;
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
     * Constant to display hte lang as rephrasing.
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
     * Mutlilangv2 parent lang behaviour.
     *
     * @var string
     */
    private string $multilangparentlang;
    /**
     * @var bool
     */
    private bool $canimprove;
    /**
     * @var array|string[]
     */
    private array $deeplrephraselangs;

    /**
     * Constructor.
     *
     * @throws \coding_exception
     */
    public function __construct() {
        // Set to dummies values.
        $this->currentlang = optional_param('lang', current_language(), PARAM_NOTAGS);
        $this->targetlang = optional_param('target_lang', '', PARAM_NOTAGS);
        if ($this->targetlang !== '') {
            $this->targetlang = LanguageCode::standardizeLanguageCode($this->targetlang);
        }
        $this->moodlelangs = get_string_manager()->get_list_of_translations();
        $this->multilangparentlang = get_config('filter_multilang2', 'parentlangbehaviour');
        $this->deeplsourcelang = '';

    }

    /**
     * Initialise the Deepl object.
     *
     * @return void
     * @throws \DeepL\DeepLException
     * @throws \dml_exception
     */
    public function initdeepl(): void {
        $this->setdeeplapi();
        $this->inittranslator();
        $this->keyisfree = DeepLClient::isAuthKeyFreeAccount($this->apikey);
        $this->usage = $this->translator->getUsage();
        // Not in the API yet.
        $this->deeplrephraselangs = ['de', 'en-GB', 'en-US', 'es', 'fr', 'it', 'pt-BR', 'pt-PT'];
        $this->canimprove = !$this->keyisfree;
        $this->deeplsources = $this->translator->getSourceLanguages();
        $this->deepltargets = $this->translator->getTargetLanguages();
        $this->setcurrentlanguage();
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
     * Set the key string.
     * If empty, it will try to get it from the .env useful for tests runs.
     *
     * @return void
     * @throws \dml_exception
     */
    private function setdeeplapi(): void {
        $configkey = get_config('local_deepler', 'apikey');
        if ($configkey === '') {
            $configkey = getenv('DEEPL_API_TOKEN') ? getenv('DEEPL_API_TOKEN') : '';
        }
        $this->apikey = $configkey;
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
            } catch (\DeepL\AuthorizationException $e) {
                return false;
            }
        }
        return true;
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
    public function prepareconfig(stdClass &$config) {
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
        return json_encode($config);
    }

    /**
     * Checks if a given lang is supported by Deepl
     *
     * @param string $lang
     * @return bool
     */
    private function islangsupported(string $lang) {
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
}
