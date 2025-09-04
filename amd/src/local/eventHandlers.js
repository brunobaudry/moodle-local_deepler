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
define(['translation',
        'api', 'customevents', 'selectors',
        'editor_tiny/loader', 'editor_tiny/editor',
        'uiHelpers',
    'core/log'
    ],
    (Translation,
     Api, Events, Selectors,
     TinyMCEinit, TinyMCE,
     UI,
     Log)=>{
        let config = {};
        let filterTimeout;
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
        Events.on(Api.GLOSSARY_DB_ALL_FAILED, onGlossaryDbAllfailed);
        Events.on(Api.GLOSSARY_DB_FAILED, onGlossaryDbfailed);
        Events.on(Api.GLOSSARY_DB_SUCCESS, onGlossaryDbSuccess);
        Events.on(Api.GLOSSARY_ENTRIES_SUCCESS, UI.showEntriesModal);
        Events.on(Api.GLOSSARY_ENTRIES_FAILED, (e)=>Log.error(e));
    };

    const handleFocusEvent = (e)=>{
        if (e.target.closest(Selectors.editors.targetarea)) {
            if (UI.getIconStatus(e.target.id.replace('tiny_', '')) === Selectors.statuses.tosave) {
                const options = {
                    subdirs: false,
                    maxbytes: 10240,
                    maxfiles: 0,
                    noclean: true,
                    trusttext: true,
                    // eslint-disable-next-line camelcase
                    enable_filemanagement: false,
                    autosave: false,
                    removeorphaneddrafts: true,
                    plugins: []
                };
                // eslint-disable-next-line promise/catch-or-return
                TinyMCEinit.getTinyMCE().then(
                    // eslint-disable-next-line promise/always-return
                    ()=>{
                        // eslint-disable-next-line promise/no-nesting
                        TinyMCE.setupForTarget(e.target, options)
                            // eslint-disable-next-line promise/always-return
                            .then(()=>{
                                Log.info('tiny loaded for ' + e.target.id);
                            })
                            .catch((r)=>{
                                Log.error(r);
                            });
                    }
                );
            }

        }
    };
    /**
     * Event listener for click events.
     *
     * @param {event} e
     */
    const handleClickEvent = (e) => {
        if (e.target.closest(Selectors.actions.toggleMultilang)) {
            onToggleMultilang(e.target.closest(Selectors.actions.toggleMultilang));
        }
        if (e.target.closest(Selectors.actions.autoTranslateBtn)) {
            if ((config.isfree && config.deeplsourcelang === config.targetlang) || config.targetlang === undefined) {
                showModal('Cannot call deepl', `<p>${langstrings.uistrings.canttranslatesame} ${config.targetlang}</p>`);
            } else {
                callDeeplServices();
            }
        }
        if (e.target.closest(Selectors.actions.selectAllBtn)) {
            toggleAllCheckboxes(e);
        }
        if (e.target.closest(Selectors.actions.tothetop)) {
            UI.backToBase();
        }
        if (e.target.closest(Selectors.actions.checkBoxes)) {
            toggleAutotranslateButton();
        }
        if (e.target.closest(Selectors.actions.saveAll)) {
            saveTranslations();
        }
        if (e.target.closest(Selectors.actions.validatorsBtns)) {
            saveSingleTranslation(e);
        }
        if (e.target.closest(Selectors.glossary.entriesviewerPage)) {
            Log.info('CLICK');
            Log.info(settingsUI[Selectors.deepl.glossaryId].value);
            Api.getGlossariesEntries(
                settingsUI[Selectors.deepl.glossaryId].value,
                config.deeplsourcelang,
                config.targetlang
            );
        }
    };
    /**
     * Event listener for change events.
     * @param {event} e
     */
    const handleChangeEvent = (e) => {
        if (e.target.closest(Selectors.actions.hideiframes)) {
            doHideiframes(hideiframes.checked);
        }
        if (e.target.closest(Selectors.actions.targetSwitcher)) {
            switchTarget(e);
        }
        if (e.target.closest(Selectors.actions.sectionSwitcher)) {
            switchSection(e);
        }
        if (e.target.closest(Selectors.actions.moduleSwitcher)) {
            switchModules(e);
        }
        if (e.target.closest(Selectors.actions.sourceSwitcher)) {
            switchSource(e);
        }
        if (e.target.closest(Selectors.actions.checkBoxes)) {
            onItemChecked(e);
        }
        if (e.target.closest(Selectors.actions.sourceselect)) {
            onSourceChange(e);
        }
        if (e.target.closest(Selectors.deepl.glossaryId)) {
            if (settingsUI[Selectors.deepl.glossaryId].value !== '') {
                glossaryDetailViewr.style.display = 'block';
            } else {
                glossaryDetailViewr.style.display = 'none';
            }
        }
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(() => {
            if (e.target.closest(Selectors.actions.showUpdated)) {
                debouncedShowRows();
            }
            if (e.target.closest(Selectors.actions.showNeedUpdate)) {
                debouncedShowRows();
            }
            if (e.target.closest(Selectors.actions.showHidden)) {
                debouncedShowRows();
            }
            }, 30);
    };

    /**
     * Event Listener when DeepL API call failed.
     * @param {string} error
     */
    const onTranslationFailed = (error) => {
        let s = langstrings.uistrings.deeplapiexception;
        onTranslationDone();
        UI.showModal(s, error, 'Alert');
    };
    /**
     * Event Listener when DeepL API call finished.
     */
    const onTranslationDone = () => {
        UI.hideModal();
    };
    /**
     * Event listener for the translations process to dispaly the status.
     *
     * @param {string} key
     */
    const onItemTranslated = (key) => {
        // Add saved indicator.
        setIconStatus(key, Selectors.statuses.tosave, true);
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
        const editor = Utils.domQuery(Selectors.editors.multiples.editorsWithKey, key);
        editor.classList.add("local_deepler__error");
        setIconStatus(key, Selectors.statuses.failed);
        // Display granular error messages.
        const indexOfSET = error.indexOf("Data too long");// Probably a text too long for the field if not -1.
        if (indexOfSET === -1) {
            showErrorMessageForEditor(key, error);
        } else {
            let s = langstrings.uistrings.errortoolong;
            showErrorMessageForEditor(key, `${error.substring(0, error.indexOf('WHERE id=?'))} ${s}`);
        }
    };
    /**
     * Listener for individual source change.
     * @todo MDL-000 implement in v1.4.0
     *
     * @param {event} e
     */
    const onSourceChange = (e) => {
        // Do check source and target and propose rephrase if PRO.
        Log.info('source changed');
        Log.info(e.target.getAttribute('data-key'));
    };
    /**
     * Event listener for selection checkboxes.
     * @param {Event} e
     */
    const onItemChecked = (e) => {
        // Check/uncheck checkboxes changes the charcount and icon status.
        if (e.target.getAttribute('data-action') === "local_deepler/checkbox") {
            toggleStatus(e.target.getAttribute('data-key'), e.target.checked);
            countWordAndChar();
        }
    };
    /**
     * When a main error with the DB occurs.
     *
     * @param {string} error
     * @param {int} status
     */
    const onDBFailed = (error, status) => {
        if (saveAllModal !== null && saveAllModal.isVisible) {
            saveAllModal.hide();
        }
        showModal(`${errordbtitle} ${status}`, `DB failed to save translations. ${error}`, 'Alert');
    };
    /**
     *
     * @param {array} errors
     */
    const onDbSavedSuccess = (errors) => {
        if (saveAllModal !== null && saveAllModal.isVisible) {
            saveAllModal.hide();
        }
        if (errors.length > 0) {
            let s = langstrings.uistrings.errordbpartial;
            s = s.replace('{$a}', errors.length);
            showModal(errordbtitle, s, 'Alert');
        }
    };

    /**
     * Multilang button handler
     *
     * @param {Event} e Event
     */
    const onToggleMultilang = (e) => {
        let keyid = e.getAttribute('aria-controls');
        let key = Utils.keyidToKey(keyid);
        if (key === null) {
            Log.error(`KEY ${keyid} BAD FORMAT should be TABLE-ID-FIELD-CMID`);
        } else {
            let source = Utils.domQuery(Selectors.sourcetexts.keys, key);
            let multilang = Utils.domQuery(Selectors.sourcetexts.multilangs, keyid);
            source.classList.toggle("show");
            multilang.classList.toggle("show");
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
        Utils.domQuery(Selectors.editors.multiples.editorsWithKey, key)
            .classList.add("local_deepler__success");
        // Add saved indicator.
        setIconStatus(key, Selectors.statuses.success);
        // Replace text in the multilang textarea.
        const multilangTextarea = Utils.domQuery(Selectors.editors.multiples.textAreas, key);
        multilangTextarea.innerHTML = savedText;
        // Deselect the checkbox.
        Utils.domQuery(Selectors.editors.multiples.checkBoxesWithKey, key).checked = false;
        // Remove success message after a few seconds.
        setTimeout(() => {
            let multilangPill = Utils.domQuery(Selectors.statuses.multilang, key);
            let prevTransStatus = Utils.domQuery(Selectors.statuses.prevTransStatus, key);
            prevTransStatus.classList = "badge badge-pill badge-success";
            if (multilangPill.classList.contains("disabled")) {
                multilangPill.classList.remove('disabled');
            }
            setIconStatus(key, Selectors.statuses.saved);
        });
    };
    const init = (cfg) => {
        config = cfg;
    };
    return {
        registerEventListeners: registerEventListeners
    };
});
