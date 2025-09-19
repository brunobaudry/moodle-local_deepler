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
 *  description here.
 *
 * @module     'local_deepler'; // Full name of the plugin (used for diagnostics)./local/eventHandlers
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
        'core/log',
        './utils',
        './translation',
        './api',
        './customevents',
        './selectors',
        './uiHelpers',
        './settings'
    ],
    (
     Log,
     Utils,
     Translation,
     Api,
     Events,
     Selectors,
     UI,
     Settings
    )=>{
        let config = {};
        let filterTimeout;
        let mainEditorIsTextArea;
        /**
         * Event factories.
         */
        const registerEventListeners = () => {
            document.addEventListener('change', handleChangeEvent);
            document.addEventListener('click', handleClickEvent);
            document.addEventListener('focusin', handleFocusEvent);

            // Translation events.
            Events.on(Translation.ON_ITEM_TRANSLATED, onItemTranslated);
            Events.on(Translation.ON_TRANSLATION_FAILED, onTranslationFailed);
            Events.on(Translation.ON_TRANSLATION_DONE, onTranslationDone);
            Events.on(Translation.ON_REPHRASE_FAILED, onTranslationFailed);
            Events.on(Translation.ON_DB_SAVE_SUCCESS, onDbSavedSuccess);
            Events.on(Translation.ON_DB_FAILED, onDBFailed);
            Events.on(Translation.ON_ITEM_SAVED, onSuccessMessageItem);
            Events.on(Translation.ON_ITEM_NOT_SAVED, onErrorMessageItem);
            // Events.on(UI.ON_STATUS_CHANGED, onIconStatusChanged);
            Events.on(Api.GLOSSARY_DB_ALL_FAILED, onGlossaryDbAllfailed);
            Events.on(Api.GLOSSARY_DB_FAILED, onGlossaryDbfailed);
            Events.on(Api.GLOSSARY_DB_SUCCESS, onGlossaryDbSuccess);
            Events.on(Api.GLOSSARY_ENTRIES_SUCCESS, UI.showEntriesModal);
            Events.on(Api.GLOSSARY_ENTRIES_FAILED, (e)=>Log.error(e));
        };
        /**
         * Focus.
         *
         * @param {event} e
         */
        const handleFocusEvent = (e)=>{
            if (e.target.closest(Selectors.editors.targetarea)) {
                // Window.console.group('handleFocusEvent');
                // window.console.log(e, e.target);
                // window.console.groupEnd();
                UI.wrapTinyOnTarget(e.target);
            }
        };
        /**
         * Event listener for click events.
         *
         * @param {event} e
         */
        const handleClickEvent = (e) => {

            if (e.target.closest(Selectors.actions.autoTranslateBtn)) {
                callDeeplServices();
            }
            if (e.target.closest(Selectors.actions.selectAllBtn)) {
                // Here.
                UI.toggleAllCheckboxes(e.target.checked);
                Translation.updatTempStatusAll(
                    e.target.checked ? Selectors.process.totranslate : Selectors.process.wait);
            }
            if (e.target.closest(Selectors.actions.tothetop)) {
                UI.backToBase();
            }
           /* If (e.target.closest(Selectors.actions.checkBoxes)) {
                UI.toggleAutotranslateButton();
            }*/


            if (e.target.closest(Selectors.glossary.entriesviewerPage)) {
                Log.info('CLICK');
                Log.info(Settings.getValue(Selectors.deepl.glossaryId));
                Api.getGlossariesEntries(
                    Settings.getValue(Selectors.deepl.glossaryId),
                    config.deeplsourcelang,
                    config.targetlang
                );
            }
            const rowContainer = e.target.closest('[data-row-id]');
            if (rowContainer) {
                // Const key = rowContainer.getAttribute('data-row-id');
                if (e.target.closest(Selectors.actions.toggleMultilang)) {
                    onToggleMultilang(rowContainer);
                }
                if (e.target.closest(Selectors.actions.validatorsBtns)) {
                    const process = rowContainer.getAttribute('data-process');
                    switch (process) {
                        case Selectors.process.tosave :
                            // OnItemChecked(key, e.target.checked);
                            saveSingleTranslation(rowContainer);
                            break;
                    }
                }
            }
            if (e.target.closest(Selectors.actions.saveAll)) {
                saveTranslations();
            }
        };
        /**
         * Event listener for change events.
         * @param {Event} e
         */
        const handleChangeEvent = (e) => {
            if (e.target.closest(Selectors.actions.escapeLatex)) {

            }
            if (e.target.closest(Selectors.actions.escapePre)) {

            }
            if (e.target.closest(Selectors.actions.hideiframes)) {
                // Triggered by advanced admin #local_deepler__hideiframes.
                UI.doHideiframes();
            }
            if (e.target.closest(Selectors.actions.targetSwitcher)) {
                // Triggered by the target language drop down.
                Utils.switchLocation(
                    {
                        key: 'target_lang',
                        value: e.target.value.replace(config.rephrasesymbol, ''),
                    }
                );
            }
            if (e.target.closest(Selectors.deepl.glossaryId)) {
                // Triggered by the glossary drop down.
                UI.toggleGlossaryDetails(Settings.getValue(Selectors.deepl.glossaryId));
            }
            /**
             * *************************************
             ** Filters
             **************************************
             */
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(() => {
                if (e.target.closest(Selectors.actions.showUpdated)) {
                    UI.debouncedShowRows();
                }
                if (e.target.closest(Selectors.actions.showNeedUpdate)) {
                    UI.debouncedShowRows();
                }
                if (e.target.closest(Selectors.actions.showHidden)) {
                    UI.debouncedShowRows();
                }
            }, 30);
            if (e.target.closest(Selectors.actions.sectionSwitcher)) {
                // Triggered by the course section dropdown
                Utils.switchLocation(
                    {
                        key: 'section_id',
                        value: e.target.value,
                    }, 'activity_id'
                );
            }
            if (e.target.closest(Selectors.actions.moduleSwitcher)) {
                // Triggered by the course module/activity dropdown
                Utils.switchLocation(
                    {
                        key: 'activity_id',
                        value: e.target.value,
                    }
                );
            }
            /**
             ************************************** Row events.
             */
            const rowContainer = e.target.closest('[data-row-id]');
            if (rowContainer) {
                const key = rowContainer.getAttribute('data-row-id');
                switch (e.target.getAttribute('data-action')) {
                    case Selectors.actions.checkBoxes :
                        onItemChecked(rowContainer, e.target.checked);
                        break;
                    case Selectors.actions.sourceselect:
                        Translation.updateTempSourceLang(key, e.target.value);
                        break;
                }
            }
/*            If (e.target.closest(Selectors.actions.checkBoxes)) {
                onItemChecked(e);
            }
            if (e.target.closest(Selectors.actions.sourceselect)) {
                onSourceChange(e);
            }*/
/*
            if (e.target.closest(Selectors.deepl.glossaryId)) {
                UI.toggleGlossaryDetails(Settings.getValue(Selectors.deepl.glossaryId));
            }
*/

        };
        /**
         * Launch deepl services.
         */
        const callDeeplServices = () => {
            UI.launchTranslatingModal();
            const keys = [];
            const [cookie, settings] = Settings.prepareSettingsAndCookieValues();
            // UI.saveAllBtn.disabled = false;
            UI.disableSaveButton();
            UI.formQueryAll(Selectors.statuses.checkedCheckBoxes)
                .forEach((ckBox) => {
                    const row = ckBox.closest(Selectors.actions.corerowid);
                    const key = row.getAttribute("data-key");
                    const sourceText = UI.formQuery(Selectors.sourcetexts.singlelangkeys, key, row);
                    const editor = UI.findEditor(key);
                    Translation.initTempForKey(
                        key, editor,
                        sourceText.getAttribute("data-sourcetext-raw"),
                        sourceText.getAttribute("data-filedtext-raw"),
                        UI.formQuery(Selectors.sourcetexts.sourcelangdd, key, row).value
                    );
                    keys.push(key);
                });
            const newCookiename = Utils.COOKIE_PREFIX_NEW + config.currentlang + config.targetlang + config.courseid;
            Utils.setEncodedCookie(newCookiename, JSON.stringify(cookie), config.cookieduration);
            Translation.callTranslations(keys, config, settings);
        };
        /**
         * Event Listener when DeepL API call failed.
         * @param {string} error
         */
        const onTranslationFailed = (error) => {
            UI.disableTranslateButton();
            onTranslationDone();
            UI.deeplErrorModal(error);
        };
        /**
         * Event Listener when DeepL API call finished.
         */
        const onTranslationDone = () => {
            // UI.saveAllBtn.disabled = false;
            UI.disableTranslateButton();
            UI.enableSaveButton();
            UI.hideModal();
        };
        /**
         * Event listener for the translations process to dispaly the status.
         *
         * @param {string} key
         */
        const onItemTranslated = (key) => {
            // Add saved indicator.
            UI.setIconStatus(key, Selectors.process.tosave, true);
        };
        /**
         * Displays error message and icon.
         *
         * @param {string} key
         * @param {string} error
         */
        const onErrorMessageItem = (key, error) => {
            Log.warn(`ui/errorMessageItem`);
            Log.warn(key);
            Log.warn(error);
            const editor = UI.formQuery(Selectors.editors.multiples.editorsWithKey, key);
            editor.classList.add("local_deepler__error");
            UI.setIconStatus(key, Selectors.process.failed);
            UI.showErrorMessageForEditor(key, error);
        };
        /**
         * Listener for individual source change.
         * @todo MDL-000 implement in v1.4.0
         * @param {string} key
         * @param {string} sourceLang
         */
        // eslint-disable-next-line no-unused-vars
        const onSourceChange = (key, sourceLang) => {
            Translation.updateTempSourceLang(key, sourceLang);
            // Do check source and target and propose rephrase if PRO.
            Log.info('source changed');
            Log.info(key);
        };
        /**
         * Event listener for selection checkboxes.
         * @param {HTMLElement} row
         * @param {boolean} checked
         */
        const onItemChecked = (row, checked) => {
            // Check/uncheck checkboxes changes the charcount and icon status.
            UI.toggleStatus(row, checked);
            UI.countWordAndChar();
            UI.toggleAutotranslateButton();
        };
       /* Const onItemChecked = (e) => {
            // Check/uncheck checkboxes changes the charcount and icon status.
            if (e.target.getAttribute('data-action') === "local_deepler/checkbox") {
                const key = e.target.getAttribute('data-key');
                UI.toggleStatus(key, e.target.checked, Translation.translated(key));
                UI.countWordAndChar();
            }
        };*/
        /**
         * When a main error with the DB occurs.
         *
         * @param {string} error
         * @param {int} status
         */
        const onDBFailed = (error, status) => {
            UI.hideModal();
            UI.dbErrorModal(error, status);
        };
        /**
         *
         * @param {array} errors
         */
        const onDbSavedSuccess = (errors) => {
            UI.hideModal();
            if (errors.length > 0) {
                UI.dbErrorPartialModal(errors.length);
            }
        };

        /**
         * Multilang button handler
         *
         * @param {HTMLElement} row
         * @param {string} key
         */
        const onToggleMultilang = (row, key) => {
            // Window.console.log(e);
            // const row = e.target.closest(Selectors.actions.corerowid);
            // Let keyid = e.getAttribute('aria-controls');
            // let key = Utils.keyidToKey(keyid);
            //  let key = row.getAttribute('data-row-id');
            if (key === null) {
                Log.error(`KEY ${key} BAD FORMAT should be TABLE-ID-FIELD-CMID`);
            } else {
                let sourceText = UI.formQuery(Selectors.sourcetexts.singlelang, '', row);
                let multilangText = UI.formQuery(Selectors.sourcetexts.multilangs, ',', row);
                sourceText.classList.toggle("show");
                multilangText.classList.toggle("show");
            }
        };
        const onGlossaryDbAllfailed = (obj)=> {
            Log.info('onGlossaryDbAllfailed');
            Log.error(obj);
        };

        const onGlossaryDbfailed = (obj)=> {
            Log.info('onGlossaryDbfailed');
            Log.error(obj);
        };

        const onGlossaryDbSuccess = (obj)=> {
            Log.info('onGlossaryDbSuccess');
            Log.info(obj);
        };
        /**
         * Displays success message and icon.
         *
         * @param {String} key
         * @param {string} savedText
         */
        const onSuccessMessageItem = (key, savedText) => {
            UI.formQuery(Selectors.editors.multiples.editorsWithKey, key)
                .classList.add("local_deepler__success");
            // Add saved indicator.
            UI.setIconStatus(key, Selectors.process.success, Translation.translated(key));
            // Replace text in the multilang textarea.
            const multilangTextarea = UI.formQuery(Selectors.editors.multiples.textAreas, key);
            multilangTextarea.innerHTML = savedText;
            // Deselect the checkbox.
            UI.formQuery(Selectors.editors.multiples.checkBoxesWithKey, key).checked = false;
            // Remove success message after a few seconds.
            setTimeout(() => {
                let multilangPill = UI.formQuery(Selectors.statuses.multilang, key);
                let prevTransStatus = UI.formQuery(Selectors.statuses.prevTransStatus, key);
                prevTransStatus.classList = "badge badge-pill badge-success";
                if (multilangPill.classList.contains("disabled")) {
                    multilangPill.classList.remove('disabled');
                }
                UI.setIconStatus(key, Selectors.process.saved, Translation.translated(key));
            });
        };
        /**
         * @returns void
         */
        const saveTranslations = () => {
            const selectedCheckboxes = UI.formQueryAll(Selectors.statuses.checkedCheckBoxes);
            if (selectedCheckboxes.length === 0) {
                return;
            }
            // Prepare the UI for the save process.
            UI.disableSaveButton();
            UI.launchSaveAllModal();
            // Prepare the data to be saved.
            const data = [];
            /* Const keys = Array.from(selectedCheckboxes).map((e) => e.dataset.key);
            keys.forEach((key) => {
                    // @todo MDL-0000: should not rely on UI (add a flag in temptranslations object) .
                    if (UI.getIconStatus(key) === Selectors.statuses.tosave) {
                        UI.hideErrorMessage(key);
                        const dbItem = UI.prepareDBitem(key, Selectors.editors.multiples.editorsWithKey, config.courseid);
                        data.push(dbItem);
                    }
                }
            );*/
            selectedCheckboxes.forEach(
                (ck)=>{
                    // Const key = container.getAttribute('data-row-id');
                    const row = ck.closest(Selectors.actions.corerowid);
                    UI.hideErrorMessageForRow(row);
                    data.push(UI.prepareRowForDB(row, config.courseid));
                }
            );
            Translation.saveTranslations(data, mainEditorIsTextArea);
        };
    /**
     * Saving a single translation to DB.
     * @param {HTMLElement} row
     */
    const saveSingleTranslation = (row)=> {
        UI.hideErrorMessageForRow(row);
        const dbItem = UI.prepareRowForDB(row, config.courseid);
        Translation.saveTranslations([dbItem], mainEditorIsTextArea);
        // Const key = row.getAttribute('data-row-id');
        // UI.hideErrorMessage(key);
        // Const key = e.target.closest(Selectors.actions.validatorsBtns).dataset.keyValidator;
        /* if (UI.getIconStatus(key) === Selectors.statuses.tosave) {
            UI.hideErrorMessage(key);
            const dbItem = Utils.prepareDBitem(key, Selectors.editors.multiples.editorsWithKey, config.courseid);
            Translation.saveTranslations([dbItem], config.userPrefs === 'textarea');
        }*/
    };
    /**
     * Init.
     *
     * @param {object} cfg
     */
    const init = (cfg) => {
        config = cfg;
        mainEditorIsTextArea = config.userPrefs === 'textarea';
        registerEventListeners();
    };
    return {
        init: init
    };
});
