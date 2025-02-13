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
    './api', './utils', './selectors', './ui', './tokeniser', './customevents'],
    (Api, Utils, Selectors, Ui, Tokeniser, Events) => {
    let tempTranslations = {};
    let escapePatterns = {};
    let mainSourceLang = "";
    let targetLang = "";
    let settings = {};
    const setMainLangs = (source, target) => {
        mainSourceLang = source;
        targetLang = target;
    };
    const saveTranslations = (keys, maineditorIsTextArea) => {
        const data = keys.map(item => prepareDbUpdateItem(item, maineditorIsTextArea));
        Events.on(Api.TR_DB_SUCCESS);
        Api.updateTranslationsInDb(data);
        // Api.callApi("local_deepler_update_translation", {data: data}).done(handleAjaxUpdateDBResponse);
    };
        const prepareDbUpdateItem = (item, maineditorIsTextArea) => {
            const key = item.key;
            const textTosave = getupdatedtext(key, maineditorIsTextArea);
            item.text = textTosave;
            return {
                courseid: item.courseid,
                id: item.id,
                tid: item.tid,
                field: item.field,
                table: item.table,
                text: textTosave
            };
        };
        /**
         * Update Textarea.
         *
         * @param {string} key
         * @param {boolean} maineditorIsTextArea
         * @returns {string}
         * translation.js
         */
        const getupdatedtext = (key, maineditorIsTextArea) => {
            const sourceItemLang = tempTranslations[key].sourceLang;
            const fieldText = tempTranslations[key].fieldText; // Translation
            const translation = getEditorText(tempTranslations[key].editor, maineditorIsTextArea);// Translation
            const source = getSourceText(key);// Translation
            const isFirstTranslation = fieldText.indexOf("{mlang") === -1;
            const isSourceOther = sourceItemLang === mainSourceLang;
            const tagPatterns = {
                "other": "({mlang other)(.*?){mlang}",
                "target": `({mlang ${targetLang}}(.*?){mlang})`,
                "source": `({mlang ${sourceItemLang}}(.*?){mlang})`
            };
            const langsItems = {
                "fullContent": fieldText,
                "other": `{mlang other}${source}{mlang}`,
                "target": `{mlang ${targetLang}}${translation}{mlang}`,
                "source": `{mlang ${sourceItemLang}}${source}{mlang}`
            };
            if (isFirstTranslation) {
                // No mlang tag : easy.
                if (isSourceOther) {
                    return langsItems.other + langsItems.target;
                } else {
                    return langsItems.other + langsItems.source + langsItems.target;
                }
            }
            // Alreaddy mlang tag-s.
            return additionalUpdate(isSourceOther, tagPatterns, langsItems);
        };
        /**
         * Update Textarea when there was mlang tags.
         * Main regex '({mlang ([a-z]{2,5})}(.*?){mlang})'.
         * @param {boolean} isSourceOther
         * @param {string} tagPatterns
         * @param {string} langsItems
         * @returns {string} {string}
         * @todo MDL-000 refactor this.
         */
        const additionalUpdate = (isSourceOther, tagPatterns, langsItems) => {
            let manipulatedText = langsItems.fullContent;
            // Do we have a TARGET tag already ?
            const targetReg = new RegExp(tagPatterns.target, "sg");
            const hasTagTarget = manipulatedText.match(targetReg);
            if (hasTagTarget) {
                // Yes replace it.
                manipulatedText = manipulatedText.replace(targetReg, Tokeniser.escapeReplacementString(langsItems.target));
            } else {
                // No, add it at the end.
                const lastMlangClosingTagEnd = manipulatedText.lastIndexOf("{mlang}") + "{mlang}".length;
                manipulatedText = [manipulatedText.slice(0, lastMlangClosingTagEnd),
                    langsItems.target,
                    manipulatedText.slice(lastMlangClosingTagEnd)
                ].join('');
            }
            // Do we have a OTHER tag already ?
            const otherReg = new RegExp(tagPatterns.other, "sg");
            const hasTagOther = manipulatedText.match(otherReg);
            // Do we have a SOURCE tag already ?
            const sourceReg = new RegExp(tagPatterns.other, "sg");
            const hasTagSource = manipulatedText.match(sourceReg);
            if (isSourceOther) {
                // Whatever was the {mlang other} tag language we need to replace it by this source.
                manipulatedText = manipulatedText.replace(otherReg, Tokeniser.escapeReplacementString(langsItems.other));
                if (hasTagSource) {
                    // And remove the {mlang source} tag if found.
                    manipulatedText.replace(sourceReg, "");
                }
            } else {
                if (!hasTagOther) {
                    // We still add this source as otherTag of the so that it can be replaced further.
                    const firstMlangClosingTagEnd = manipulatedText.indexOf("{mlang");
                    manipulatedText = [manipulatedText.slice(0, firstMlangClosingTagEnd),
                        langsItems.other,
                        manipulatedText.slice(firstMlangClosingTagEnd)
                    ].join('');
                }
                if (!hasTagSource) {
                    // Add the {mlang source} tag if not found.
                    manipulatedText.replace(sourceReg, Tokeniser.escapeReplacementString(langsItems.source));
                }
            }
            return manipulatedText;
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
     * @param {string} key
     * @param {editor: object, editorType: string} editorSettings
     * @param {string} sourceTextEncoded
     * @param {string} multilangRawTextEncoded
     * @param {string} sourceLang
     */
    const initTempForKey = (key, editorSettings, sourceTextEncoded, multilangRawTextEncoded, sourceLang) => {
        const sourceText = Utils.fromBase64(sourceTextEncoded);
        const fieldText = Utils.fromBase64(multilangRawTextEncoded);
        const tokenised = Tokeniser.preprocess(sourceText, escapePatterns, escapePatterns);
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

    const prepareTranslation = (key) => {
        return {
            text: tempTranslations[key].source,
            source_lang: tempTranslations[key].sourceLang,
            key: key
        };
    };

    /* Const getTranslation = (key) => {
        let formData = Utils.prepareFormData(key);
        Api.translate(formData, (response) => {
            let tr = Utils.postprocess(response.translations[0].text, tempTranslations[key].tokens);
            tempTranslations[key].editor.innerHTML = tr;
            tempTranslations[key].translation = tr;
            Ui.setIconStatus(key, Selectors.statuses.tosave, true);
        });
    };*/
    /**
     * Call the external translation service to translate the selected keys.
     *
     * @param {array} keys
     */
    const callTranslations = (keys) => {
        const translations = [];
        const options = prepareAdvancedSettings(document.querySelector(Selectors.actions.targetSwitcher).value);
        keys.forEach((key) => {
            // InitTempForKey(key);
            translations.push(prepareTranslation(key));
        });
        Events.on(Api.DEEPL_SUCCESS, onTranslateSuccess);
        Events.on(Api.DEEPL_FAILED, onTranslateFailed);
        Api.translate(translations, options);
    };
const onTranslateSuccess = (response)=>{
    response.translations.forEach((tr) => {
        // @todo emit event.
        let key = tr.key;
        let translation = Utils.postprocess(tr.text, tempTranslations[key].tokens);
        tempTranslations[key].editor.innerHTML = translation;
        tempTranslations[key].translation = translation;
        Ui.setIconStatus(key, Selectors.statuses.tosave, true);
    });
};
const onTranslateFailed = (status, error)=>{
    window.console.log(status, error);
};
    /**
     * Compile Advanced settings.
     *
     * @param {string} targetLang
     * @returns {{}}
     * translation.js ok
     */
    const prepareAdvancedSettings = (targetLang) => {
        Utils.info('prepareAdvancedSettings');
        escapePatterns.LATEX = document.querySelector(Selectors.actions.escapeLatex).checked;
        escapePatterns.PRETAG = document.querySelector(Selectors.actions.escapePre).checked;
        // eslint-disable-next-line camelcase
        settings.tag_handling = document.querySelector(Selectors.deepl.tagHandling).checked ? 'html' : 'xml';//
        settings.context = document.querySelector(Selectors.deepl.context).value ?? null;//
        // eslint-disable-next-line camelcase
        settings.split_sentences = document.querySelector(Selectors.deepl.splitSentences).value;//
        // eslint-disable-next-line camelcase
        settings.preserve_formatting = document.querySelector(Selectors.deepl.preserveFormatting).checked;//
        settings.formality = document.querySelector('[name="local_deepler/formality"]:checked').value;
        // eslint-disable-next-line camelcase
        settings.glossary_id = document.querySelector(Selectors.deepl.glossaryId).value;//
        // eslint-disable-next-line camelcase
        settings.outline_detection = document.querySelector(Selectors.deepl.outlineDetection).checked;//
        // eslint-disable-next-line camelcase
        settings.non_splitting_tags = Utils.toJsonArray(document.querySelector(Selectors.deepl.nonSplittingTags).value);
        // eslint-disable-next-line camelcase
        settings.splitting_tags = Utils.toJsonArray(document.querySelector(Selectors.deepl.splittingTags).value);
        // eslint-disable-next-line camelcase
        settings.ignore_tags = Utils.toJsonArray(document.querySelector(Selectors.deepl.ignoreTags).value);
        // eslint-disable-next-line camelcase
        settings.target_lang = targetLang.toUpperCase();
        // eslint-disable-next-line camelcase
        settings.model_type = document.querySelector(Selectors.deepl.modelType).value ?? 'prefer_quality_optimized';
        // eslint-disable-next-line camelcase
        settings.show_billed_characters = true;

        // Settings.auth_key = config.apikey;
        // return settings;
    };
    /**
     * Check if the item is translatable.
     *
     * @param {string} sourceLang
     */
    const isTranslatable = (sourceLang = '') =>{
        Utils.info(targetLang, sourceLang, targetLang === (sourceLang === '' ? mainSourceLang : sourceLang));
        return targetLang === (sourceLang === '' ? mainSourceLang : sourceLang);
    };
        const translated = (key)=>{
            return tempTranslations[key]?.translation?.length > 0;
        };
        return {
        callTranslations: callTranslations,
        saveTranslations: saveTranslations,
        initTempForKey: initTempForKey,
        initTemp: initTemp,
        /* TempTranslations: tempTranslations,*/
        setMainLangs: setMainLangs,
        isTranslatable: isTranslatable,
        translated: translated
    };
});
