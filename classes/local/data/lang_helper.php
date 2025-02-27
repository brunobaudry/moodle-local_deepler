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

namespace local_deepler\local\data;
defined('MOODLE_INTERNAL') || die();

use DeepL\DeepLException;
use DeepL\Translator;
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
     * The current moodle language.
     *
     * @var string
     */
    public string $currentlang;
    /**
     * @var string The source language for deepl.
     */
    public string $deeplsourcelang;
    /**
     * The target language.
     *
     * @var string
     */
    public string $targetlang;
    /**
     * Moodle instance's installed languages.
     *
     * @var array|mixed
     */
    public mixed $moodlelangs;
    /**
     * @var string
     */
    private string $apikey;
    /**
     * @var Translator
     */
    private mixed $translator;
    /**
     * Languages available as source in Deepl's API.
     *
     * @var object
     */
    private mixed $deeplsources;
    /**
     * Languages available as target in Deepl's API.
     *
     * @var object
     */
    private mixed $deepltargets;
    /**
     * Deepl usage bound to the api key.
     *
     * @var Usage
     */
    protected Usage $usage;
    /**
     * Whether to allow sublanguages as main.
     *
     * @var string
     */
    private mixed $allowsublangcodesasmain;
    /**
     * Type of DeepL subscrription.
     *
     * @var bool
     */
    private $keyisfree;
    /**
     * Mutlilangv2 parent lang behaviour.
     *
     * @var string
     */
    private string $multilangparentlang;

    /**
     * Constructor.
     *
     * @throws \coding_exception
     */
    public function __construct() {
        // Set to dummies values.
        $this->allowsublangcodesasmain = get_config('local_deepler', 'allowsublangs');
        $this->currentlang = optional_param('lang', current_language(), PARAM_NOTAGS);
        $this->targetlang = optional_param('target_lang', '', PARAM_NOTAGS);
        $this->moodlelangs = get_string_manager()->get_list_of_translations();
        $this->multilangparentlang = get_config('filter_multilang2', 'parentlangbehaviour');
    }

    public function initdeepl() {
        $this->setdeeplapi();
        $this->inittranslator();
        $this->keyisfree = Translator::isAuthKeyFreeAccount($this->apikey);
        $this->usage = $this->translator->getUsage();
        $this->deeplsources = $this->translator->getSourceLanguages();
        $this->deepltargets = $this->translator->getTargetLanguages();
        $this->setcurrentlanguage();
    }

    private function setcurrentlanguage() {
        // Moodle format is not the common culture format.
        // Deepl's sources are ISO 639-1 (Alpha 2) and uppercase.
        $hasunderscore = strpos($this->currentlang, '_');
        if ($this->allowsublangcodesasmain && $hasunderscore && !$this->iscurrentsupported()) {
            $this->deeplsourcelang = strtoupper(substr($this->currentlang, 0, $hasunderscore));
        } else {
            $this->deeplsourcelang = strtoupper($this->currentlang);
        }
    }
    /**
     * Simple init and checks of external call to Deepl's API.
     *
     * @param string $key
     * @return bool
     * @throws \DeepL\DeepLException
     * @throws \dml_exception
     *
     * public function init(string $key): bool {
     * $this->setdeeplapi($key);
     * if ($this->isapikeynoset()) {
     * return false;
     * } else {
     * $initok = $this->inittranslator();
     * if ($initok) {
     * try {
     * try {
     * $this->usage = $this->translator->getUsage();
     * } catch (DeepLException $e) {
     * $initok = false;
     * }
     * $noissuewithsupportedlanguages = $this->setsupportedlanguages();
     * $initok = $initok && $noissuewithsupportedlanguages;
     * $hasunderscore = strpos($this->currentlang, '_');
     * if ($this->allowsublangcodesasmain && $hasunderscore && !$this->iscurrentsupported()) {
     * $this->currentlang = substr($this->currentlang, 0, $hasunderscore);
     * }
     * } catch (\DeepL\AuthorizationException $e) {
     * return false;
     * }
     * }
     * return $initok;
     * }
     * }*/

    /**
     * Set the key string.
     * If empty, it will try to get it from the .env useful for tests runs.
     *
     * @param string $key
     * @return void
     * @throws \dml_exception
     */
    private function setdeeplapi() {
        $configkey = get_config('local_deepler', 'apikey');
        if ($configkey === '') {
            $configkey = getenv('DEEPL_APIKEY') ? getenv('DEEPL_APIKEY') : '';
        }
        $this->apikey = $configkey;
    }

    /**
     * Fecthes and set the available languages.
     *
     * @return void
     * @throws \DeepL\DeepLException
     *
     * private function setsupportedlanguages(): bool {
     * try {
     * $this->deeplsources = $this->translator->getSourceLanguages();
     * $this->deepltargets = $this->translator->getTargetLanguages();
     * } catch (DeepLException $e) {
     * return false;
     * }
     * return true;
     * }
     */
    /**
     * Initialise the Deepl object.
     * Return a boolean of the cnx status.
     *
     * @return bool
     * @throws \DeepL\DeepLException
     */
    private function inittranslator() {
        if (!isset($this->translator)) {
            try {
                $this->translator = new \DeepL\Translator($this->apikey, ['send_platform_info' => false]);
            } catch (\DeepL\AuthorizationException $e) {
                return false;
            }
        }
        return true;
    }

    /**
     * Creates props for html selects.
     *
     * @param bool $issource
     * @param bool $verbose
     * @return array
     */
    public function prepareoptionlangs(bool $issource, bool $verbose = true) {
        $tab = [];
        //$deeplLangs = $this->translator->getTargetLanguages();
        $filteredDeepls = $this->findDeeplsformoodle($issource);
        /** @var  $l \DeepL\Language */
        foreach ($filteredDeepls as $l) {
            // $k = strtolower($l->code);
            $small = substr(strtolower($l->code), 0, 2);
            // if($issource)
            // $disable = $issource ? $k === $this->targetlang : $k === $this->currentlang;

            if ($issource) {
                $issameastaget = $this->isrephrase($l->code);
                $selected = strpos($l->code, $this->deeplsourcelang) !== false;
                $text = ($issameastaget && !$this->keyisfree ? '® ' : '') . $l->code;
                $disable = $issameastaget && $this->keyisfree;
            } else {
                $issameassource = strpos($l->code, $this->deeplsourcelang) !== false;
                $selected = strpos($l->code, $this->targetlang) !== false;
                $text = ($issameassource && !$this->keyisfree ? "® " : '') . $l->name;
                $disable = $issameassource && $this->keyisfree;
            }
            //$selected = $issource ? $small === $this->currentlang : $l->code === $this->targetlang;
            //$text = $verbose ? !$issource && ($small === $this->currentlang) ? 'REPHRASE ' . $l->code : $l->name : $l->code;
            // $disable = $disable || !$this->islangsupported($k, $issource);
            $tab[] = [
                    'code' => $l->code,
                    'lang' => $text,
                    'selected' => $selected ? 'selected' : '',
                    'disabled' => $disable ? 'disabled' : '',
            ];
        }
        return $tab;
    }

    function findDeeplsformoodle(bool $issource) {
        $deepls = $issource ? $this->deeplsources : $this->deepltargets;
        return array_filter($deepls, function($item) {
            foreach ($this->moodlelangs as $code => $langverbose) {
                $moodle = strtolower(str_replace('_', '', $code));
                $deepl = strtolower(str_replace('-', '', $item->code));
                if (stripos($deepl, $moodle) !== false) {
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * Create HTML props for select.
     *
     * @param bool $issource
     * @param bool $verbose
     * @return string
     * TODO MDL-0000 allow regional languages setup (expl EN-GB)
     */
    public function preparehtmlotions(bool $issource, bool $verbose = true) {
        $tab = $this->prepareoptionlangs($issource, $verbose);
        $list = '';
        foreach ($tab as $item) {
            $list .= "<option value='{$item['code']}' {$item['selected']} {$item['disabled']} data-initial-value='{$item['code']}'>
                    {$item['lang']}</option>";
        }
        return $list;
    }

    /**
     * Injects lang attributes to the config object.
     *
     * @param \stdClass $config
     * @return \stdClass
     * @throws DeepLException
     * @todo MDL-0000 rename this function
     */
    public function prepareconfig(stdClass &$config) {
        $config->usage = $this->usage;
        try {
            $config->limitReached = $config->usage->anyLimitReached();
        } catch (DeepLException $e) {
            $config->limitReached = true;
        }
        $config->targetlang = $this->targetlang;
        $config->currentlang = $this->currentlang;
        $config->deeplsourcelang = $this->deeplsourcelang;
        $config->isfree = $this->keyisfree;
        //$this->preparestrings($config);
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
     * @param bool $issource
     * @param bool $strict
     * @return bool
     */
    private function islangsupported(string $lang, bool $issource, bool $strict = false) {
        $list = $issource ? $this->deeplsources : $this->deepltargets;
        $len = count($list);
        while ($len--) {
            $code = $strict ? $list[$len]->code : substr($list[$len]->code, 0, 2);
            if ($code === $lang || $code === strtoupper($lang)) {
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
        return $this->islangsupported($this->currentlang, true, true);
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
     * @return bool
     */
    public function isrephrase(string $source = '') {
        $s = $source === '' ? $this->deeplsourcelang : $source;
        return strpos($this->targetlang, $s) !== false;
    }

}
