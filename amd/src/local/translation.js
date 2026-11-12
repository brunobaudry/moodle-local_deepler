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
    'core/log', './api', './utils', './selectors', './tokeniser', './reactive/statemanager'],
    (Log, Api, Utils, Selectors, Tokeniser, StateManager) => {
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
        const trSelectors = [
            Selectors.deepl.tagHandling,
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
         * Helper to get active reactive state manager.
         *
         * @returns {object|null}
         */
        const getReactive = () => {
            return StateManager.getStateManager(courseid || 'main');
        };

        /**
         * Save translations to the DB.
         *
         * @param {Array} items
         * @param {object} config
         * @returns {Promise<Array>}
         */
        const saveTranslations = async(items, config) => {
            const reactive = getReactive();
            if (reactive) {
                reactive.dispatch('updateBatchStatus', {isSaving: true});
                items.forEach((item) => {
                    reactive.dispatch('setRowStatus', item.key, 'saving');
                });
            }
            const data = items.map(item => prepareDbUpdateItem(item, config.userPrefs === 'textarea'));
            Log.debug(`translation/x/saveTranslations::data`);
            Log.debug(data);
            try {
                const response = await Api.updateTranslationsInDb(data, userid, courseid);
                if (reactive) {
                    reactive.dispatch('updateBatchStatus', {isSaving: false});
                }
                if (response.length === 1 && response[0].error && response[0].keyid === '') {
                    throw new Error(response[0].error);
                }
                response.forEach((item) => {
                    if (item.error === '') {
                        if (tempTranslations[item.keyid]) {
                            tempTranslations[item.keyid].fieldText = item.text;
                        }
                        if (reactive) {
                            reactive.dispatch('setRowStatus', item.keyid, 'saved');
                        }
                    } else {
                        if (reactive) {
                            reactive.dispatch('setRowStatus', item.keyid, 'error', item.error);
                        }
                    }
                });
                return response;
            } catch (error) {
                if (reactive) {
                    reactive.dispatch('updateBatchStatus', {isSaving: false});
                }
                Log.error(`translation/saveTranslations::failed`, error);
                throw error;
            }
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
            const textTosave = getEditorText(tempTranslations[key].editor, maineditorIsTextArea);
            Log.debug(`translation/x/prepareDbUpdateItem::textTosave`);
            Log.debug(textTosave);
            Log.debug(tempTranslations[key]);
            Log.debug(mainSourceLang);
            Log.debug(deeplSourceLang);
            Log.debug(moodleTargetToSave);
            return {
                tid: item.tid,
                text: textTosave,
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
         * Format translation options from custom settings.
         *
         * @param {object|null} custom
         * @returns {object|null}
         */
        const formatRowOptions = (custom) => {
            if (!custom) {
                return null;
            }
            return {
                context: custom.context || null,
                // eslint-disable-next-line camelcase
                tag_handling: custom.tag_handling || null,
                // eslint-disable-next-line camelcase
                split_sentences: custom.split_sentences || null,
                // eslint-disable-next-line camelcase
                preserve_formatting: custom.preserve_formatting ?? null,
                formality: custom.formality || null,
                // eslint-disable-next-line camelcase
                outline_detection: custom.outline_detection ?? null,
                // eslint-disable-next-line camelcase
                non_splitting_tags: custom.non_splitting_tags || null,
                // eslint-disable-next-line camelcase
                splitting_tags: custom.splitting_tags || null,
                // eslint-disable-next-line camelcase
                ignore_tags: custom.ignore_tags || null,
                // eslint-disable-next-line camelcase
                glossary_id: custom.glossary_id || null,
                // eslint-disable-next-line camelcase
                model_type: custom.model_type || null,
                toneorstyle: custom.toneorstyle || null,
            };
        };

        /**
         * Retrieve custom settings for a given row key.
         *
         * @param {string} key
         * @returns {object|null}
         */
        const getRowCustomSettings = (key) => {
            const reactive = getReactive();
            const rows = reactive?.state?.rows;
            if (rows) {
                const row = rows.get(key) || Array.from(rows.values()).find((r) => r.key === key || r.id === key);
                if (row?.customSettings) {
                    return row.customSettings;
                }
            }
            return tempTranslations[key]?.customSettings || null;
        };

        /**
         * Prepare the texts for the external api calls.
         *
         * @param {string} key
         * @returns {{text, source_lang: (string|string|*), key}}
         */
        const prepareTranslation = (key) => {
            const rowCustomSettings = getRowCustomSettings(key);
            const item = {
                text: tempTranslations[key].source,
                // eslint-disable-next-line camelcase
                source_lang: tempTranslations[key].sourceLang,
                key: key,
            };

            const options = formatRowOptions(rowCustomSettings);
            if (options) {
                item.options = options;
            }

            return item;
        };
        /**
         * Call the external translation service to translate the selected keys.
         *
         * @param {Array} keys
         * @param {object} config
         * @param {object} settings
         * @returns {Promise<Array>}
         */
        const callTranslations = async(keys, config, settings) => {
            rephrasesymbol = config.rephrasesymbol;
            const reactive = getReactive();
            if (reactive) {
                reactive.dispatch('updateBatchStatus', {isTranslating: true});
                keys.forEach((key) => {
                    reactive.dispatch('setRowStatus', key, 'translating');
                });
            }
            const translations = [];
            const rephrases = [];
            // We parse and check if it is a translation or text improvement.
            keys.forEach((key) => {
                const t = prepareTranslation(key);
                if (config.canimprove && t.source_lang && t.source_lang.includes(rephrasesymbol)) {
                    delete t.source_lang;
                    if (t.options) {
                        t.options = {
                            toneorstyle: t.options.toneorstyle || null,
                        };
                    }
                    Log.debug(`translation/x/callTranslations::t`);
                    Log.debug(t);
                    rephrases.push(t);
                } else {
                    translations.push(t);
                }
            });

            const results = [];
            try {
                if (translations.length > 0) {
                    const response = await Api.translate(
                        translations,
                        prepareTranslationSettings(settings),
                        Api.APP_VERSION
                    );
                    onTranslateSuccess(response);
                    results.push(...response);
                }
                if (rephrases.length > 0) {
                    const response = await Api.rephrase(
                        rephrases,
                        prepareRephraseSettings(settings),
                        Api.APP_VERSION
                    );
                    onRephraseSuccess(response);
                    results.push(...response);
                }
                return results;
            } catch (error) {
                if (reactive) {
                    reactive.dispatch('updateBatchStatus', {isTranslating: false});
                }
                Log.error(`translation/callTranslations::failed`, error);
                throw error;
            } finally {
                if (reactive) {
                    reactive.dispatch('updateBatchStatus', {isTranslating: false});
                }
            }
        };
        /**
         * When translation succeeded.
         *
         * @param {Array} response
         */
        const onTranslateSuccess = (response) => {
            Log.info(response);
            const reactive = getReactive();
            const glossaries = [];
            response.forEach((tr) => {
                let key = tr.key;
                if (tr.error === '') {
                    // For now used glossary_id should be the same for the batch,
                    // but it would make sense to use a single glossary for each text.
                    if (tr.glossary_id && glossaries.indexOf(tr.glossary_id) === -1 && tr.glossary_id.trim() !== '') {
                        glossaries.push(tr.glossary_id);
                    }
                    let translation = Tokeniser.postprocess(tr.translated_text, tempTranslations[key].tokens);
                    if (tempTranslations[key].editor) {
                        tempTranslations[key].editor.innerHTML = translation;
                    }
                    tempTranslations[key].translation = translation;
                    if (reactive) {
                        reactive.dispatch('updateRowTranslation', key, translation);
                    }
                } else {
                    if (reactive && key) {
                        reactive.dispatch('setRowStatus', key, 'error', tr.error);
                    }
                }
            });
            if (glossaries.length > 0) {
                Api.updateGlossariesUsage(glossaries).catch((err) => Log.error(err));
            }
        };
        /**
         * When rephrasing succeeded.
         *
         * @param {Array} response
         */
        const onRephraseSuccess = (response) => {
            const reactive = getReactive();
            response.forEach((tr) => {
                let key = tr.key;
                if (tr.error === '') {
                    let rephrase = Tokeniser.postprocess(tr.text, tempTranslations[key].tokens);
                    if (tempTranslations[key].editor) {
                        tempTranslations[key].editor.innerHTML = rephrase;
                    }
                    tempTranslations[key].translation = rephrase;
                    if (reactive) {
                        reactive.dispatch('updateRowTranslation', key, rephrase);
                    }
                } else {
                    if (reactive && key) {
                        reactive.dispatch('setRowStatus', key, 'error', tr.error);
                    }
                }
            });
        };
        /**
         * Set up the Deepl settings needed for translations.
         *
         * @param {object} settings
         * @returns {{}}
         */
        const prepareTranslationSettings = (settings)=>{
            const s = filterSetting(settings, trSelectors);
            // eslint-disable-next-line camelcase
            s.target_lang = targetLang.toUpperCase();
            // eslint-disable-next-line camelcase
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
            // eslint-disable-next-line camelcase
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
         */
        const isTranslatable = () =>{
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
        };
});
