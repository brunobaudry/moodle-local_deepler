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


/**
 * @module     local_deepler/deepler
 * @file       amd/src/local/translation.js
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'core/log', './api', './utils', './selectors', './tokeniser', './customevents'],
    (Log, Api, Utils, Selectors, Tokeniser, Events) => {
        let tempTranslations = {};
        let escapePatterns = {};
        let mainSourceLang = "";
        let deeplSourceLang = "";
        let targetLang = "";
        let moodleTargetToSave = "";
        let courseid = 0;
        let userid = 0;
        let rephrasesymbol = '';
        const deeplSettinRegex = /\[(name="local_deepler|data-id="local_deepler)\/(\w+)"\]/i;
        const ON_ITEM_TRANSLATED = 'onItemTranslated';
        // Const ON_ITEM_NOT_TRANSLATED = 'onItemsNotTranslated';
        const ON_ITEM_SAVED = 'onItemSaved';
        const ON_ITEM_NOT_SAVED = 'onItemNotSaved';
        const ON_TRANSLATION_FAILED = 'onTranslationFailed';
        const ON_REPHRASE_FAILED = 'onRephraseFailed';
        const ON_DB_SAVE_SUCCESS = 'onDbSuccess';
        const ON_DB_FAILED = 'onDbFailed';
        /**
         * Prepare the langaue settings.
         *
         * @param {object} config
         */
        const setMainLangs = (config) => {
            if (config.currentlang !== undefined && config.currentlang !== '') {
                mainSourceLang = config.currentlang;
            }
            if (config.targetlang !== undefined && config.targetlang !== '') {
                targetLang = config.targetlang.toLowerCase();
            }
            if (config.deeplsourcelang !== undefined && config.deeplsourcelang !== '') {
                deeplSourceLang = config.deeplsourcelang.toLowerCase();
            }
        };
        /**
         * When translation successfully stored in Moodle's DB.
         *
         * @param {object} data
         */
        const onTrDbSuccess = (data)=>{
            if (data.length === 0) {
                Events.emit(ON_DB_FAILED, 'no data returned', '');
            } else {
                const errors = data.filter((item) => item.error !== '');
                data.forEach((item) => {
                    if (item.error === '') {
                        // Refreshing the text in the temp obbject in case of new translation without page refresh.
                        tempTranslations[item.keyid].fieldText = item.text;
                        Events.emit(ON_ITEM_SAVED, item.keyid, item.text);
                    } else {
                        Events.emit(ON_ITEM_NOT_SAVED, item.keyid, item.error);
                    }
                });
                Events.emit(ON_DB_SAVE_SUCCESS, errors);
            }
        };
        /**
         * Translation DB failed.
         * @param {int} status
         * @param {string} error
         */
        const onTrDbFailed = (status, error) =>{
                Events.emit(ON_DB_FAILED, error, status);
                Log.trace(status);
                Log.trace(error);
            };
        /**
         * Save translations to the DB.
         * @param {array} items
         * @param {object} config
         */
        const saveTranslations = (items, config) => {
            const data = items.map(item => prepareDbUpdateItem(item, config.userPrefs === 'textarea'));
            Log.debug(`translation/x/saveTranslations::data`);
            Log.debug(data);
            Events.on(Api.TR_DB_SUCCESS, onTrDbSuccess);
            Events.on(Api.TR_DB_FAILED, onTrDbFailed);
            Api.updateTranslationsInDb(data, userid, courseid);
        };
        /**
         * Prepare the data to be saved in the DB.
         *
         * @param {object} item
         * @param {boolean} maineditorIsTextArea
         * @returns {{ id, tid: *, field, table, text: string}}
         */
        const prepareDbUpdateItem = (item, maineditorIsTextArea) => {
            const key = item.key;
            Log.debug(tempTranslations[key]);
            // Const textTosave = getupdatedtext(key, maineditorIsTextArea);
            const textTosave = getEditorText(tempTranslations[key].editor, maineditorIsTextArea);
            Log.debug(`translation/x/prepareDbUpdateItem::textTosave`);
            Log.debug(textTosave);
            Log.debug(tempTranslations[key]);
            Log.debug(mainSourceLang);
            Log.debug(deeplSourceLang);
            Log.debug(moodleTargetToSave);
            // TextTosave = getEditorText(key, maineditorIsTextArea);
            return {
                tid: item.tid,
                text: getEditorText(tempTranslations[key].editor, maineditorIsTextArea),
                keyid: key,
                mainsourcecode: mainSourceLang,
                sourcecode: tempTranslations[key].sourceLang,
                targetcode: document.querySelector(Selectors.actions.targetCompatibleSwitcher).value,
                sourcetext: getSourceText(key)
            };
        };
        /**
         * Editor's text content.
         *
         * @param {HTMLElement} editor
         * @param {boolean} maineditorIsTextArea
         * @returns {string}
         * translation.js
         */
        const getEditorText = (editor, maineditorIsTextArea) => {
            let text = editor.innerHTML;
            if (maineditorIsTextArea) {
                text = Utils.decodeHTML(text);
            }
            return text;
        };
        /**
         * Source text de-tokenised.
         *
         * @param {String} key
         * @returns {String}
         * translation.js
         */
        const getSourceText = (key) => {
            const sourceTokenised = tempTranslations[key].source;
            return Tokeniser.postprocess(sourceTokenised, tempTranslations[key].tokens);
        };
        /**
         * Initializing object storage before translation.
         *
         * @param {string} key
         * @param {editor: object, editorType: string} editorSettings
         * @param {string} sourceTextEncoded
         * @param {string} multilangRawTextEncoded
         * @param {string} sourceLang
         */
        const initTempForKey = (key, editorSettings, sourceTextEncoded, multilangRawTextEncoded, sourceLang) => {
            const sourceText = Utils.fromBase64(sourceTextEncoded);
            const fieldText = Utils.fromBase64(multilangRawTextEncoded);
            const tokenised = Tokeniser.preprocess(sourceText, escapePatterns);
            tempTranslations[key] = {
                editorType: editorSettings.editorType,
                editor: editorSettings.editor,
                source: tokenised.tokenizedText,
                sourceLang: sourceLang,
                fieldText: fieldText,
                status: Selectors.statuses.wait,
                translation: '',
                tokens: tokenised.expressions
            };
        };
        /**
         * Wipe pout the temp.
         * @param {string} key
         */
        const initTemp = (key)=>{
            tempTranslations[key] = {
                editorType: null,
                editor: null,
                source: '',
                sourceLang: '',
                fieldText: '',
                status: '',
                translation: '',
                tokens: []
            };
        };
        /**
         * Prepare the texts for the external api calls.
         *
         * @param {string} key
         * @returns {{text, source_lang: (string|string|*), key}}
         */
        const prepareTranslation = (key) => {
            return {
                text: tempTranslations[key].source,
                // eslint-disable-next-line camelcase
                source_lang: tempTranslations[key].sourceLang,
                key: key
            };
        };
        /**
         * Call the external translation service to translate the selected keys.
         *
         * @param {array} keys
         * @param {object} config
         * @param {object} settings
         * @return void
         */
        const callTranslations = (keys, config, settings) => {
            rephrasesymbol = config.rephrasesymbol;
            const translations = [];
            const rephrases = [];
            // We parse and check if it is a tranlsation or text improvment.
            keys.forEach((key) => {
                const t = prepareTranslation(key);
                if (config.canimprove && t.source_lang.includes(rephrasesymbol)) {
                    delete t.source_lang;
                    Log.debug(`translation/x/callTranslations::t`);
                    Log.debug(t);
                    rephrases.push(t);
                } else {
                    translations.push(t);
                }
            });
            if (translations.length > 0) {
                Events.on(Api.DEEPL_SUCCESS, onTranslateSuccess);
                Events.on(Api.DEEPL_FAILED, onTranslateFailed);
                 Api.translate(translations, prepareTranslationSettings(settings), Api.APP_VERSION);
            }
            if (rephrases.length > 0) {
                Events.on(Api.DEEPL_RF_SUCCESS, onRephraseSuccess);
                Events.on(Api.DEEPL_RF_FAILED, onRephaseFailed);
                Api.rephrase(rephrases, prepareRephraseSettings(settings), Api.APP_VERSION);
            }
        };
        /**
         * When translation went good.
         *
         * @param {object} response
         */
        const onTranslateSuccess = (response)=>{
            response.forEach((tr) => {
                if (tr.error === '') {
                    let key = tr.key;
                    let translation = Tokeniser.postprocess(tr.translated_text, tempTranslations[key].tokens);
                    tempTranslations[key].editor.innerHTML = translation;
                    tempTranslations[key].translation = translation;
                    Events.emit(ON_ITEM_TRANSLATED, key);
                } else {
                    Events.emit(ON_TRANSLATION_FAILED, tr.error);
                }
            });
        };
        /**
         * When rephrasing went good.
         *
         * @param {object} response
         */
        const onRephraseSuccess = (response)=>{
            response.forEach((tr) => {
                if (tr.error === '') {
                    let key = tr.key;
                    let rephrase = Tokeniser.postprocess(tr.text, tempTranslations[key].tokens);
                    tempTranslations[key].editor.innerHTML = rephrase;
                    tempTranslations[key].translation = rephrase;
                    Events.emit(ON_ITEM_TRANSLATED, key);
                } else {
                    Events.emit(ON_REPHRASE_FAILED, tr.error);
                }
            });
        };
        /**
         * When translation failed.
         *
         * @param {int} status
         * @param {string} error
         */
        const onTranslateFailed = (status, error)=>{
            Events.emit(ON_TRANSLATION_FAILED, status, error);
        };
        /**
         * Event handler for when the rephrase fails.
         *
         * @param {int} status
         * @param {string} error
         */
        const onRephaseFailed = (status, error)=>{
            Events.emit(ON_REPHRASE_FAILED, status, error);
        };
        /**
         * Set up the Deepl settings needed for translations.
         *
         * @param {object} settings
         * @returns {{}}
         */
        const prepareTranslationSettings = (settings)=>{
            const trSelectors = [Selectors.deepl.tagHandling,
                Selectors.deepl.context,
                Selectors.deepl.splitSentences,
                Selectors.deepl.preserveFormatting,
                Selectors.deepl.formality,
                Selectors.deepl.glossaryId,
                Selectors.deepl.outlineDetection,
                Selectors.deepl.nonSplittingTags,
                Selectors.deepl.splittingTags,
                Selectors.deepl.ignoreTags,
                Selectors.deepl.modelType
            ];
            const s = filterSetting(settings, trSelectors);
            s.target_lang = targetLang.toUpperCase();
            s.show_billed_characters = true;
            return s;
        };
        /**
         * Set up the Deepl settings needed for rephrases.
         * @param {object}  settings
         * @returns {{}}
         */
        const prepareRephraseSettings = (settings)=>{
            const rephraseSelectors = [Selectors.deepl.toneorstyle];
            const s = filterSetting(settings, rephraseSelectors);
            s.target_lang = targetLang.toUpperCase();
            return s;
        };
        /**
         * Filter the UI settings by Deepl service needed set.
         * @param {object} settings
         * @param {array}  selList
         * @returns {{}}
         */
        const filterSetting = (settings, selList) =>{
            const se = {};
            for (let i in selList) {
                const set = selList[i].match(deeplSettinRegex)[2];
                se[set] = settings[selList[i]];
            }
            return se;
        };
        /**
         * Check if the item is translatable.
         * @todo MDL-0000 implement in v1.4.0 (return based on local source)
         * @param {string} sourceLang
         */
        const isTranslatable = (sourceLang = '') =>{
             Log.info(targetLang, sourceLang, targetLang === (sourceLang === '' ? mainSourceLang : sourceLang));
            // Return targetLang !== (sourceLang === '' ? mainSourceLang : sourceLang);
            return targetLang !== '';
        };
        /**
         * Checks if translation is done.
         *
         * @param {string} key
         * @returns {boolean}
         */
        const translated = (key)=>{
            return tempTranslations[key]?.translation?.length > 0;
        };
        /**
         * Wrapper to trace the temp for a field.
         *
         * @param {string} key
         */
        const debugTemp = (key)=>{
            Log.debug(`translation/x/debugTemp::key`);
            Log.debug(key);
            Log.debug(tempTranslations[key]);
        };
        /**
         * IOne to start them all.
         *
         * @param {object} cfg
         */
        const init = (cfg) => {
            Api.APP_VERSION = cfg.version;
            courseid = cfg.courseid;
            userid = cfg.userid;
            setMainLangs(cfg);
        };
        /**
         * Api to be used by the other modules.
         */
        return {
            init: init,
            debugTemp: debugTemp,
            callTranslations: callTranslations,
            saveTranslations: saveTranslations,
            initTempForKey: initTempForKey,
            initTemp: initTemp,
            setMainLangs: setMainLangs,
            isTranslatable: isTranslatable,
            translated: translated,
            ON_ITEM_TRANSLATED: ON_ITEM_TRANSLATED,
            ON_DB_FAILED: ON_DB_FAILED,
            ON_ITEM_SAVED: ON_ITEM_SAVED,
            ON_ITEM_NOT_SAVED: ON_ITEM_NOT_SAVED,
            ON_TRANSLATION_FAILED: ON_TRANSLATION_FAILED,
            ON_REPHRASE_FAILED: ON_REPHRASE_FAILED,
            ON_DB_SAVE_SUCCESS: ON_DB_SAVE_SUCCESS,
    };
});
