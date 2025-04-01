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
 * @file       amd/src/local/selectors.js
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export default {
    actions: {
        validatorsBtns: 'div[data-key-validator]',
        validator: '[data-key-validator="<KEY>"]',
        validatorIcon: '[data-key-validator="<KEY>"] i',
        validatorBtn: '[data-key-validator="<KEY>"] span',
        checkBoxes: '[data-action="local_deepler/checkbox"]',
        sourceselect: '[data-action="local_deepler/sourceselect"]',
        selectAllBtn: '[data-action="local_deepler/select-all"]',
        saveAll: '[data-action="local_deepler/saveall-btn"]',
        autoTranslateBtn: '[data-action="local_deepler/autotranslate-btn"]',
        autoTranslateBtnLang: '[data-action="local_deepler/autotranslate-btn"] span',
        targetSwitcher: '[data-action="local_deepler/target-switcher"]',
        sourceSwitcher: '[data-action="local_deepler/source-switcher"]',
        showNeedUpdate: '[data-action="local_deepler/show-needsupdate"]',
        showUpdated: '[data-action="local_deepler/show-updated"]',
        showHidden: '[data-action="local_deepler/show-hidden"]',
        escapeLatex: '[data-id="local_deepler/latexeascape"]',
        escapePre: '[data-id="local_deepler/preescape"]',
        toggleMultilang: '#toggleMultilang'
    },
    statuses: {
        sourceTextWarings: '[data-status="sourceTextWarings"]',
        checkedCheckBoxes: '[data-action="local_deepler/checkbox"]:checked',
        updated: '[data-status="updated"]',
        needsupdate: '[data-status="needsupdate"]',
        hidden: '.local_deeplerinvisible',
        keys: '[data-status-key="<KEY>"',
        successMessages: '[data-status="local_deepler/success-message"][data-key="<KEY>"]',
        prevTransStatus: '[data-row-id="<KEY>"] span#previousTranslationStatus',
        multilang: '[data-row-id="<KEY>"] span#toggleMultilang',
        wait: 'local_deepler/wait',
        totranslate: 'local_deepler/totranslate',
        tosave: 'local_deepler/tosave',
        failed: 'local_deepler/failed',
        success: 'local_deepler/success',
        saved: 'local_deepler/saved',
        wordcount: '#local_deepler__wc',
        charNumWithOutSpace: '#local_deepler__wosc',
        charNumWithSpace: '#local_deepler__wsc',
        deeplUsage: '#local_deepler__used',
        deeplMax: '#local_deepler__max',
        deeplStatusContainer: '#local_deepler-translate-header-usage',
    },
    editors: {
        textarea: '[data-action="local_deepler/textarea"',
        all: '[data-action="local_deepler/editor"]',
        iframes: '[data-action="local_deepler/editor"] iframe',
        contentEditable: '[data-action="local_deepler/editor"] [contenteditable="true"]',
        multiples: {
            checkBoxesWithKey: 'input[type="checkbox"][data-key="<KEY>"]',
            checkBoxesWithKeyHidden: '.local_deeplerinvisible input[type="checkbox"][data-key]',
            editorChilds: '[data-action="local_deepler/editor"][data-key="<KEY>"] > *',
            textAreas: '[data-action="local_deepler/textarea"][data-key="<KEY>"]',
            editorsWithKey: '[data-action="local_deepler/editor"][data-key="<KEY>"]',
            contentEditableKeys: '[data-key="<KEY>"] [contenteditable="true"]'
        },
        types: {
            basic: '[data-action="local_deepler/editor"][data-key="<KEY>"] [contenteditable="true"]',
            atto: '[data-action="local_deepler/editor"][data-key="<KEY>"] [contenteditable="true"]',
            other: '[data-action="local_deepler/editor"][data-key="<KEY>"] textarea[name="<KEY>[text]"]',
            tiny: '[data-action="local_deepler/editor"][data-key="<KEY>"] iframe'
        }
    },
    sourcetexts: {
        keys: '[data-sourcetext-key="<KEY>"]',
        sourcelangs: '[data-key="<KEY>"].local_deepler__source-text select',
        sourcelangdd: '[data-key="<KEY>"][data-action="local_deepler/sourceselect"]',
        sourcelang: '#local_deepler__sourcelang strong',
        targetlang: '#local_deepler__targetlang strong',
        multilangs: '#<KEY>',
        parentrow: '[data-row-id="<KEY>"]'
    },
    deepl: {
        context: '[data-id="local_deepler/context"]',
        nonSplittingTags: '[data-id="local_deepler/non_splitting_tags"]',
        splittingTags: '[data-id="local_deepler/splitting_tags"]',
        ignoreTags: '[data-id="local_deepler/ignore_tags"]',
        preserveFormatting: '[data-id="local_deepler/preserve_formatting"]',
        formality: '[name="local_deepler/formality"]:checked',
        glossaryId: '[data-id="local_deepler/glossary_id"]',
        tagHandling: '[data-id="local_deepler/tag_handling"]',
        outlineDetection: '[data-id="local_deepler/outline_detection"]',
        splitSentences: '[name="local_deepler/split_sentences"]:checked',
        modelType: '[name="local_deepler/modeltype"]:checked'
    },
    config: {
        langstrings: '#local_deepler__stringscontainer',
    },
};
