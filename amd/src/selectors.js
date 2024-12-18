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
 * @typedef {Object} SelectorObject
 * @property {function(HTMLElement=): Element} query
 * @property {function(HTMLElement=): NodeList} queryAll
 * @property {function(string, HTMLElement=): Element} querySelectorKey
 * @property {function(string): string} replaceKey
 * @property {function(): string} toString
 */


/**
 * Add functions to the selectors.
 * @param {string} selector
 * @returns {SelectorObject}
 */
const createSelector = (selector) => ({
    query: (parent = document) => parent.querySelector(selector),
    queryAll: (parent = document) => parent.querySelectorAll(selector),
    queryKey: (key, parent = document) => parent.querySelector(selector.replace("<KEY>", key)),
    toString: () => selector,
    replaceKey: (key) => selector.replace("<KEY>", key)
});
/**
 * @module     local_deepler/deepler
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
const Selectors = {
    actions: {
        validatorsBtns: createSelector('div[data-key-validator]'),
        validator: createSelector('[data-key-VALIDATOR="<KEY>"]'),
        validatorIcon: createSelector('[data-key-VALIDATOR="<key>"] i'),
        validatorBtn: createSelector('[data-key-VALIDATOR="<key>"] span'),
        checkBoxes: createSelector('[data-action="local_deepler/checkbox"]'),
        sourceselect: createSelector('[data-action="local_deepler/sourceselect"]'),
        selectAllBtn: createSelector('[data-action="local_deepler/select-all"]'),
        saveAll: createSelector('[data-action="local_deepler/saveall-btn"]'),
        autoTranslateBtn: createSelector('[data-action="local_deepler/autotranslate-btn"]'),
        targetSwitcher: createSelector('[data-action="local_deepler/target-switcher"]'),
        sourceSwitcher: createSelector('[data-action="local_deepler/source-switcher"]'),
        showNeedUpdate: createSelector('[data-action="local_deepler/show-needsupdate"]'),
        showUpdated: createSelector('[data-action="local_deepler/show-updated"]'),
        escapeLatex: createSelector('[data-id="local_deepler/latexeascape"]'),
        escapePre: createSelector('[data-id="local_deepler/preescape"]'),
        toggleMultilang: createSelector('#toggleMultilang')
    },
    statuses: {
        sourceTextWarings: createSelector('[data-status="sourceTextWarings"]'),
        checkedCheckBoxes: createSelector('[data-action="local_deepler/checkbox"]:checked'),
        updated: createSelector('[data-status="updated"]'),
        needsupdate: createSelector('[data-status="needsupdate"]'),
        keys: createSelector('[data-status-KEY="<KEY>"'),
        successMessages: createSelector('[data-status="local_deepler/success-message"][data-KEY="<KEY>"]'),
        prevTransStatus: createSelector('[data-row-ID="<key>"] span#previousTranslationStatus'),
        multilang: createSelector('[data-row-ID="<key>"] span#toggleMultilang'),
        wait: createSelector('local_deepler/wait'),
        totranslate: createSelector('local_deepler/totranslate'),
        tosave: createSelector('local_deepler/tosave'),
        failed: createSelector('local_deepler/failed'),
        success: createSelector('local_deepler/success'),
        saved: createSelector('local_deepler/saved'),
        wordcount: createSelector('#local_deepler__wc'),
        charNumWithOutSpace: createSelector('#local_deepler__wosc'),
        charNumWithSpace: createSelector('#local_deepler__wsc'),
        deeplUsage: createSelector('#local_deepler__used'),
        deeplMax: createSelector('#local_deepler__max'),
        deeplStatusContainer: createSelector('#local_deepler-translate-header-usage'),
    },
    editors: {
        textarea: createSelector('[data-action="local_deepler/textarea"'),
        all: createSelector('[data-action="local_deepler/editor"]'),
        iframes: createSelector('[data-action="local_deepler/editor"] iframe'),
        contentEditable: createSelector('[data-action="local_deepler/editor"] [contenteditable="true"]'),
        multiples: {
            checkBoxesWithKey: createSelector('input[type="checkbox"][data-KEY="<KEY>"]'),
            editorChilds: createSelector('[data-action="local_deepler/editor"][data-KEY="<KEY>"] > *'),
            textAreas: createSelector('[data-action="local_deepler/textarea"][data-KEY="<KEY>"]'),
            editorsWithKey: createSelector('[data-action="local_deepler/editor"][data-KEY="<KEY>"]'),
            contentEditableKeys: createSelector('[data-KEY="<key>"] [contenteditable="true"]')
        },
        types: {
            basic: createSelector('[data-action="local_deepler/editor"][data-KEY="<key>"] [contenteditable="true"]'),
            atto: createSelector('[data-action="local_deepler/editor"][data-KEY="<key>"] [contenteditable="true"]'),
            other: createSelector('[data-action="local_deepler/editor"][data-KEY="<key>"] textarea[NAME="<key>[text]"]'),
            tiny: createSelector('[data-action="local_deepler/editor"][data-KEY="<key>"] iframe')
        }
    },
    sourcetexts: {
        keys: createSelector('[data-sourcetext-KEY="<KEY>"]'),
        sourcelangs: createSelector('[data-key="<KEY>"].local_deepler__source-text select'),
        multilangs: createSelector('#<KEY>'),
        parentrow: createSelector('[data-row-id="<KEY>"]')
    },
    deepl: {
        context: createSelector('[data-id="local_deepler/context"]'),
        nonSplittingTags: createSelector('[data-id="local_deepler/non_splitting_tags"]'),
        splittingTags: createSelector('[data-id="local_deepler/splitting_tags"]'),
        ignoreTags: createSelector('[data-id="local_deepler/ignore_tags"]'),
        preserveFormatting: createSelector('[data-id="local_deepler/preserve_formatting"]'),
        formality: createSelector('[name="local_deepler/formality"]:checked'),
        glossaryId: createSelector('[data-id="local_deepler/glossary_id"]'),
        tagHandling: createSelector('[data-id="local_deepler/tag_handling"]'),
        outlineDetection: createSelector('[data-id="local_deepler/outline_detection"]'),
        splitSentences: createSelector('[name="local_deepler/split_sentences"]:checked')
    }
};
export default Selectors;
