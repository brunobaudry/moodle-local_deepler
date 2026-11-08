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
 * @file       amd/src/local/ui_deepler.js
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


define(['core/log',
    'editor_tiny/loader',
    'editor_tiny/editor',
    'core/modal',
    'core/modal_save_cancel',
    'core/modal_events',
    'core/str',
    './selectors',
    './translation',
    './utils',
    './scrollspy',
    './api',
    'core/notification',
    './reactive/statemanager',
    './reactive/components/page',
    './reactive/components/row'
], (Log, TinyMCEinit, TinyMCE,
    Modal,
    ModalSaveCancel,
    ModalEvents,
    Str,
    Selectors,
    Translation,
    Utils,
    ScrollSpy,
    Api,
    MoodleNotification,
    StateManager,
    PageComponent,
    RowComponent
    ) => {

    /**
     * Debounce for performance
     *
     * @param {function} fn
     * @param {int} delay
     * @returns {(function(...[*]): void)|*}
     */
    const debounce = (fn, delay) => {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn(...args), delay);
            };
    };

    /**
     *
     * @type {(function(...[*]): void)|*}
     */
    const debouncedShowRows = debounce(() => {
 showRows();
}, 100);


// Cached DOM elements
const cachedSelectors = {
    wordCount: document.querySelector(Selectors.statuses.wordcount),
    charWithSpace: document.querySelector(Selectors.statuses.charNumWithSpace),
    charWOSpace: document.querySelector(Selectors.statuses.charNumWithOutSpace),
    deeplUseSpan: document.querySelector(Selectors.statuses.deeplUsage),
    deeplMaxSpan: document.querySelector(Selectors.statuses.deeplMax),
    deeplStatusContainer: document.querySelector(Selectors.statuses.deeplStatusContainer)
};

    let offsetTop; // Highest point of the usable plugin UI.
    let hideiframes = {};
    // Store removed iframes and their parent/next sibling for restoration.
    let removedIframes = [];

    let config = {};
    let langstrings = {};
    let autotranslateButton = {};
    let saveAllBtn = {};
    let selectAllBtn = {};
    let checkboxes = [];
    let format = new Intl.NumberFormat();
    let saveAllModal = {};
    let errordbtitle = '';
    let settingsUI = {};
    let allDataFormatOne = [];
    let glossaryDetailViewr;
    let filterTimeout;
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


    // Const ON_TARGET_LANG_CHANGE = 'onTargetLangChange';
    /**
     * Event factories.
     */
    const registerEventListeners = () => {
        document.addEventListener('change', handleChangeEvent);
        document.addEventListener('click', handleClickEvent);
        document.addEventListener('focusin', handleFocusEvent);
    };
    const showEntriesModal = (ajaxResponse)=>{
        const glossaryid = ajaxResponse.glossaryid;
        const entries = JSON.parse(ajaxResponse.entries);
        const status = ajaxResponse.status;
        const message = ajaxResponse.message;
        if (status === 'success') {
            const table = document.createElement('table');
            table.className = 'generaltable';
            // Create the header.
            const thead = document.createElement('thead');
            thead.innerHTML = `<tr><th>${ajaxResponse.source.toUpperCase()}</th>
                <th>${ajaxResponse.target.toUpperCase()}</th></tr>`;
            table.appendChild(thead);

            const tbody = document.createElement('tbody');

            Object.entries(entries).forEach(([key, value]) => {
                const row = document.createElement('tr');
                row.innerHTML = `<td>${key}</td><td>${value}</td>`;
                tbody.appendChild(row);
            });

            table.appendChild(tbody);

            Modal.create({
                title: 'Entries',
                body: table,
                type: 'default',
                show: true,
                removeOnClose: true,
            });
        } else {
            Modal.create({
                title: `Error fetching entries for<br/><em>${glossaryid}</em>`,
                body: message,
                type: 'default',
                show: true,
                removeOnClose: true,
            });
        }
    };
    const resizeEditors = ()=>{

        allDataFormatOne.forEach((editable)=>{
            const key = editable.id.replace('tiny_', '');
            const selector = `[data-sourcetext-key="${key}"]`;
            let parent = domQuery(selector);
            const grandparent = parent.parentElement;

            const updateEditableHeight = ()=> {
                const totalHeight = grandparent.offsetHeight + 80; // Tiny header average height is 80.
                editable.style.height = totalHeight + 'px';
            };

            // Observe size changes in parent and grandparent.
            const resizeObserver = new ResizeObserver(() => {
                updateEditableHeight();
            });

            resizeObserver.observe(parent);
            resizeObserver.observe(grandparent);

        });
    };
    /**
     * Register UI elements.
     */
    const registerUI = () => {

        try {
            allDataFormatOne = domQueryAll(Selectors.editors.targetarea);
            hideiframes = domQuery(Selectors.actions.hideiframes);
            langstrings = JSON.parse(domQuery(Selectors.config.langstrings).getAttribute('data-langstrings'));
            errordbtitle = langstrings.uistrings.errordbtitle;
            saveAllBtn = domQuery(Selectors.actions.saveAll);
            selectAllBtn = domQuery(Selectors.actions.selectAllBtn);
            autotranslateButton = domQuery(Selectors.actions.autoTranslateBtn);
            checkboxes = domQueryAll(Selectors.actions.checkBoxes);

if (!glossaryDetailViewr && document.querySelector(Selectors.glossary.entriesviewerPage)) {
    glossaryDetailViewr = document.querySelector(Selectors.glossary.entriesviewerPage);
}

            settingsUI[Selectors.deepl.glossaryId] = domQuery(Selectors.deepl.glossaryId);
            settingsUI[Selectors.deepl.context] = domQuery(Selectors.deepl.context);
            settingsUI[Selectors.deepl.formality] = domQuery(Selectors.deepl.formality);
            settingsUI[Selectors.deepl.modelType] = domQuery(Selectors.deepl.modelType);
            settingsUI[Selectors.deepl.ignoreTags] = domQuery(Selectors.deepl.ignoreTags);
            settingsUI[Selectors.deepl.nonSplittingTags] = domQuery(Selectors.deepl.nonSplittingTags);
            settingsUI[Selectors.deepl.outlineDetection] = domQuery(Selectors.deepl.outlineDetection);
            settingsUI[Selectors.deepl.preserveFormatting] = domQuery(Selectors.deepl.preserveFormatting);
            settingsUI[Selectors.deepl.splitSentences] = domQuery(Selectors.deepl.splitSentences);
            settingsUI[Selectors.deepl.splittingTags] = domQuery(Selectors.deepl.splittingTags);
            settingsUI[Selectors.deepl.tagHandling] = domQuery(Selectors.deepl.tagHandling);
            if (!config.isfree) {
                settingsUI[Selectors.deepl.toneorstyle] = domQuery(Selectors.deepl.toneorstyle);
            }
            settingsUI[Selectors.actions.escapeLatex] = domQuery(Selectors.actions.escapeLatex);
            settingsUI[Selectors.actions.escapePre] = domQuery(Selectors.actions.escapePre);
            fetchCookies();
            resizeEditors();
        } catch (e) {
            if (config.debug) {
                Log.error(e.message);
            }
        }
    };
    /**
     * Get the stored settings for this course and lang pair.
     */
    const fetchCookies = () => {
        if (!config.targetlang) {
            return;
        }
        const glossaryCookie = Utils.getCookie(config);
        const newCookie = Utils.getEncodedCookie(config);
        if (newCookie !== null) {
            const settingsCookie = JSON.parse(newCookie);
            for (const selector in settingsUI) {
                if (settingsCookie[selector] !== undefined) {
                    switch (settingsUI[selector].type) {
                        case 'select-one' :
                            setOptionFromCookie(settingsCookie[selector]);
                            break;
                        case 'checkbox' :
                            settingsUI[selector].checked = settingsCookie[selector];
                            break;
                        case 'radio' :
                            domQuery(selector + `[value="${settingsCookie[selector]}"]`).checked = true;
                            break;
                        default:
                            settingsUI[selector].value = settingsCookie[selector];
                            break;
                    }

                }
            }
        }
        if (glossaryCookie !== null) {
            // Legacy cookie.
            settingsUI[Selectors.deepl.glossaryId].value = glossaryCookie;
        }
    };
    /**
     * Selects a dd based on its value.
     * @param {string} value
     */
    const setOptionFromCookie = (value)=>{
        let optionToSelect = domQuery(`option[value="${value}"]`);
        if (optionToSelect) {
            optionToSelect.selected = true;
        }
    };
    /**
     * Opens a modal infobox to warn user trunks of fields are saving.
     * @param {object} messageObject
     * @returns {Promise<void>}
     */
    const launchModal = async(messageObject) => {
        saveAllModal = await Modal.create(messageObject);
        await saveAllModal.show();
    };

    /**
     * Opens the global DeepL settings modal.
     *
     * @returns {Promise<void>}
     */
    const openGlobalSettingsModal = async() => {
        const settingsContainer = document.querySelector('#local_deepler__additionalsettings');
        if (!settingsContainer) {
            return;
        }

        const placeholder = document.createElement('div');
        placeholder.id = 'local_deepler__additionalsettings_placeholder';
        settingsContainer.parentNode.insertBefore(placeholder, settingsContainer);

        const title = (langstrings && langstrings.uistrings && langstrings.uistrings.seesetting) ?
            langstrings.uistrings.seesetting :
            await Str.get_string('seesetting', 'local_deepler');

        const modal = await ModalSaveCancel.create({
            title: title,
            body: '',
            large: true,
        });

        const bodyElement = modal.getBody()[0];
        settingsContainer.classList.remove('d-none');
        bodyElement.appendChild(settingsContainer);

        let saved = false;

        modal.getRoot().on(ModalEvents.save, (e) => {
            e.preventDefault();
            saved = true;
            const [cookie] = prepareSettingsAndCookieValues();
            const newCookiename = Utils.COOKIE_PREFIX_NEW + config.currentlang + config.targetlang + config.courseid;
            Utils.setEncodedCookie(newCookiename, JSON.stringify(cookie), config.cookieduration);
            modal.hide();
        });

        modal.getRoot().on(ModalEvents.hidden, () => {
            settingsContainer.classList.add('d-none');
            if (placeholder.parentNode) {
                placeholder.parentNode.insertBefore(settingsContainer, placeholder);
                placeholder.remove();
            }
            if (!saved) {
                fetchCookies();
            }
            modal.destroy();
        });

        await modal.show();
    };

    /**
     * TinyMCE initialize.
     *
     * @param {Event} e
     * @param {Object} options
     * @return {Promise<void>}
     */
    const initTinyMCE = async(e, options) => {
        try {
            await TinyMCEinit.getTinyMCE(); // Ensure TinyMCE is initialized.
            await TinyMCE.setupForTarget(e.target, options);
            Log.info(`tiny loaded for ${e.target.id}`);
        } catch (error) {
            Log.error(error);
        }
    };

    const handleFocusEvent = (e)=>{
        if (e.target.closest(Selectors.editors.targetarea)) {
            if (getIconStatus(e.target.id.replace('tiny_', '')) === Selectors.statuses.tosave) {
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
                void initTinyMCE(e, options).catch(MoodleNotification.exception);
            }

        }
    };
    /**
     * Event listener for click events.
     *
     * @param {event} e
     */
    const handleClickEvent = (e) => {
        if (e.target.closest('#local_deepler__settings') || e.target.closest('[data-action="global-settings"]')) {
            e.preventDefault();
            void openGlobalSettingsModal().catch(MoodleNotification.exception);
        }
        if (e.target.closest(Selectors.actions.toggleMultilang)) {
            onToggleMultilang(e.target.closest(Selectors.actions.toggleMultilang));
        }
        if (e.target.closest(Selectors.actions.autoTranslateBtn)) {
            if ((!config.canimprove && config.deeplsourcelang === config.targetlang) || config.targetlang === undefined) {
                showModal('Cannot call deepl', `<p>${langstrings.uistrings.canttranslatesame} ${config.targetlang}</p>`);
            } else {
                callDeeplServices();
            }
        }
        if (e.target.closest(Selectors.actions.selectAllBtn)) {
            toggleAllCheckboxes(e);
        }
        if (e.target.closest(Selectors.actions.tothetop)) {
            window.scrollTo({top: offsetTop - 5, behavior: 'smooth'});
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
        if (!e.target.closest(Selectors.glossary.entriesviewerPage)) {
            return;
        }
        Log.info(settingsUI[Selectors.deepl.glossaryId].value);
        Api.getGlossariesEntries(
            settingsUI[Selectors.deepl.glossaryId].value,
            config.version
        ).then((response) => {
            return showEntriesModal(response);
        }).catch((err) => {
            Log.error('Error fetching glossary entries', err);
        });
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
    };
    /**
     * Toggle iFrames in sourcetexts.
     * @param {boolean} isChecked
     */
    function doHideiframes(isChecked) {
        const allIframes = domQueryAll(Selectors.sourcetexts.iframes);
        if (!isChecked && allIframes.length > 0) {
            removedIframes = [];
            allIframes.forEach(iframe => {
                removedIframes.push({
                    parent: iframe.parentNode,
                    nextSibling: iframe.nextSibling,
                    html: iframe.outerHTML
                });
                iframe.remove();
            });
        } else if (removedIframes.length > 0) {
            // Restore all previously removed iframes.
            removedIframes.forEach(info => {
                // Create a new element from the stored HTML.
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = info.html;
                const newIframe = tempDiv.firstChild;
                // Insert it back into the DOM
                if (info.nextSibling) {
                    info.parent.insertBefore(newIframe, info.nextSibling);
                } else {
                    info.parent.appendChild(newIframe);
                }
            });
            removedIframes = [];
        }
    }


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
            let source = domQuery(Selectors.sourcetexts.keys, key);
            let multilang = domQuery(Selectors.sourcetexts.multilangs, keyid);
            source.classList.toggle("show");
            multilang.classList.toggle("show");
        }
    };

    /**
     * @returns void
     */
    const saveTranslations = async() => {
        const selectedCheckboxes = domQueryAll(Selectors.statuses.checkedCheckBoxes);
        if (selectedCheckboxes.length === 0) {
            return;
        }
        // Prepare the UI for the save process.
        saveAllBtn.disabled = true;
        await launchModal({
            title: langstrings.uistrings.saveallmodaltitle,
            body: langstrings.uistrings.saveallmodalbody,
        });
        // Prepare the data to be saved.
        const data = [];
        const keys = Array.from(selectedCheckboxes).map((e) => e.dataset.key);
        keys.forEach((key) => {
                // @todo MDL-0000: should not rely on UI (add a flag in temptranslations object) .
                if (getIconStatus(key) === Selectors.statuses.tosave) {
                    hideErrorMessage(key);
                    data.push(prepareDBitem(key));
                }
            }
        );
        try {
            const response = await Translation.saveTranslations(data, config);
            if (saveAllModal !== null && saveAllModal.isVisible) {
                saveAllModal.hide();
            }
            const errors = response.filter((item) => item.error !== '');
            response.forEach((item) => {
                if (item.error === '') {
                    onSuccessMessageItem(item.keyid, item.text);
                } else {
                    onErrorMessageItem(item.keyid, item.error);
                }
            });
            onDbSavedSuccess(errors);
        } catch (error) {
            if (saveAllModal !== null && saveAllModal.isVisible) {
                saveAllModal.hide();
            }
            onDBFailed(error.message || error, '');
        }
    };
    /**
     * Saving a single translation to DB.
     * @param {Event} e
     */
    const saveSingleTranslation = async(e) => {
        const key = e.target.closest(Selectors.actions.validatorsBtns).dataset.keyValidator;
        if (getIconStatus(key) === Selectors.statuses.tosave) {
            hideErrorMessage(key);
            try {
                const response = await Translation.saveTranslations([prepareDBitem(key)], config);
                const item = response.find(r => r.keyid === key);
                if (item && item.error !== '') {
                    onErrorMessageItem(key, item.error);
                } else if (item) {
                    onSuccessMessageItem(key, item.text);
                }
            } catch (error) {
                onErrorMessageItem(key, error.message || error);
            }
        }
    };
    /**
     *
     * @param {string} key
     * @returns {{key, courseid, id: number, tid: *, table: *, field: *}}
     */
    const prepareDBitem = (key) => {
        const element = domQuery(Selectors.editors.multiples.editorsWithKey, key);
        return {
            key: key,
            courseid: config.courseid,
            id: parseInt(element.getAttribute("data-id")),
            tid: element.getAttribute("data-tid"),
            table: element.getAttribute("data-table"),
            field: element.getAttribute("data-field"),
            cmid: element.getAttribute("data-cmid"),
        };
    };
    /**
     * Listener for individual source change.
     *
     * @param {event} e
     */
    const onSourceChange = (e) => {
        const rowSelect = e.target.closest(Selectors.actions.sourceselect);
        if (!rowSelect) {
            return;
        }
        const key = rowSelect.getAttribute('data-key');
        const selectedSourceLang = rowSelect.value;
        const sourceContainer = domQuery(Selectors.sourcetexts.keys, key);
        if (!sourceContainer) {
            return;
        }

        const rawUnfilteredBase64 = sourceContainer.getAttribute('data-filedtext-raw') || '';
        const rawUnfiltered = Utils.fromBase64(rawUnfilteredBase64);
        const mlangResult = Utils.extractMultilangSource(rawUnfiltered, selectedSourceLang);

        // Update data-sourcetext-raw with base64 encoded filtered text.
        sourceContainer.setAttribute('data-sourcetext-raw', Utils.toBase64(mlangResult.filteredText));

        // Update displayed text in DOM.
        const preEl = sourceContainer.querySelector('pre');
        if (preEl) {
            preEl.textContent = mlangResult.filteredText;
        } else {
            sourceContainer.innerHTML = mlangResult.filteredText;
        }

        // Re-initialize Translation temp item with updated source text.
        const editor = findEditor(key);
        Translation.initTempForKey(
            key,
            editor,
            Utils.toBase64(mlangResult.filteredText),
            rawUnfilteredBase64,
            selectedSourceLang
        );

        // Update reactive state for this row.
        const counts = getCount(key);
        const reactive = StateManager.getStateManager(config.courseid || 'main');
        if (reactive) {
            const strippedText = Utils.stripHTMLTags(mlangResult.filteredText).trim();
            reactive.dispatch('updateRowOriginalText', key, strippedText, counts.wordCount || 0, counts.charNumWithSpace || 0);
            reactive.dispatch('setRowSourceLang', key, selectedSourceLang);
        }

        updateAllRowsTranslationStatus();
        countWordAndChar();
    };
    /**
     * Event listener for selection checkboxes.
     * @param {Event} e
     */
    const onItemChecked = (e) => {
        // Check/uncheck checkboxes changes the charcount and icon status.
        if (e.target.getAttribute('data-action') === "local_deepler/checkbox") {
            const key = e.target.getAttribute('data-key');
            toggleStatus(key, e.target.checked);
            const reactive = StateManager.getStateManager(config.courseid || 'main');
            if (reactive) {
                reactive.dispatch('setRowSelection', key, e.target.checked);
            }
            countWordAndChar();
        }
    };

    /**
     * Toggle checkboxes
     * @param {Event} e Event
     */
    const toggleAllCheckboxes = (e) => {
        const checked = e.target.checked;
        const updates = [];

        // Prepare all updates without applying immediately.
        checkboxes.forEach(checkbox => {
            const shouldCheck = checked ? !getParentRow(checkbox).classList.contains('d-none') : false;

            if (checkbox.checked !== shouldCheck) {
                updates.push({checkbox, shouldCheck});
            }
        });

        // Apply updates in the next animation frame, batching DOM writes.
        requestAnimationFrame(() => {
            updates.forEach(({checkbox, shouldCheck}) => {
                checkbox.checked = shouldCheck;
                toggleStatus(checkbox.getAttribute('data-key'), shouldCheck);
            });

            const reactive = StateManager.getStateManager(config.courseid || 'main');
            if (reactive) {
                reactive.dispatch('selectAllRows', checked);
            }

            toggleAutotranslateButton();
            countWordAndChar();
        });
    };
    /**
     * Toggle Autotranslate Button
     */
    const toggleAutotranslateButton = () => {
        // Use Array.some() for early exit when a checked checkbox is found
        autotranslateButton.disabled = !Array.from(checkboxes).some(e => e.checked);
    };
    /**
     * Get the translation row status icon.
     *
     * @param {string} key
     * @returns {*}
     */
    const getIconStatus = (key)=> {
        return domQuery(Selectors.actions.validatorBtn, key).getAttribute('data-status');
    };
    /**
     * Change translation process status icon.
     *
     * @param {string} key
     * @param {string} status
     * @param {boolean} isBtn
     */
    const setIconStatus = (key, status = Selectors.statuses.wait, isBtn = false) => {
        let icon = domQuery(Selectors.actions.validatorBtn, key);
        if (!isBtn) {
            if (!icon.classList.contains('disabled')) {
                icon.classList.add('disabled');
            }
            if (icon.classList.contains('btn')) {
                icon.classList.remove('btn');
                icon.classList.remove('btn-success');
            }
        } else {
            if (!icon.classList.contains('btn')) {
                icon.classList.add('btn');
                icon.classList.add('btn-success');
            }
            if (icon.classList.contains('disabled')) {
                icon.classList.remove('disabled');
            }
        }
        icon.setAttribute('role', isBtn ? 'button' : 'status');
        icon.setAttribute('data-status', status);

        icon.setAttribute('title', langstrings.statusstrings[status.replace('local_deepler/', '')]);
    };
    /**
     * Fetch the parent row of the translation.
     * @param {Node} node
     * @returns {*}
     */
    const getParentRow = (node) => {
        return node.closest(Utils.replaceKey(Selectors.sourcetexts.parentrow, node.getAttribute('data-key')));
    };
    const showModal = (title, body, type = 'default') => {
        Modal.create({
            title: title,
            body: body,
            type: type,
            show: true,
            removeOnClose: true,
        });
    };
    /**
     * Event Listener when DeepL API call failed.
     * @param {string} error
     */
    const onTranslationFailed = (error) => {
        let s = langstrings.uistrings.deeplapiexception;
        onTranslationDone();
        showModal(s, error, 'Alert');
    };
    /**
     * Event Listener when DeepL API call finished.
     */
    const onTranslationDone = () => {
        if (saveAllModal !== null && saveAllModal.isVisible) {
            saveAllModal.hide();
        }
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
     * Launch deepl services.
     */
    const callDeeplServices = async() => {
        const body = `
        <div class="text-center p-3">
            <span class="spinner-border" role="status" aria-hidden="true"></span>
            <p class="mt-2">${langstrings.uistrings.translatemodalbody}...</p>
        </div>
    `;
        await launchModal(
            {
                title: langstrings.uistrings.translatemodaltitle,
                body: body,
            }
        );
        const keys = [];
        const [cookie, settings] = prepareSettingsAndCookieValues();
        saveAllBtn.disabled = false;
        domQueryAll(Selectors.statuses.checkedCheckBoxes)
            .forEach((ckBox) => {
                const key = ckBox.getAttribute("data-key");
                const sourceText = domQuery(Selectors.sourcetexts.keys, key);
                const editor = findEditor(key);
                Translation.initTempForKey(
                    key, editor,
                    sourceText.getAttribute("data-sourcetext-raw"),
                    sourceText.getAttribute("data-filedtext-raw"),
                    domQuery(Selectors.sourcetexts.sourcelangdd, key).value
                );
                keys.push(key);
            });
        const newCookiename = Utils.COOKIE_PREFIX_NEW + config.currentlang + config.targetlang + config.courseid;
        Utils.setEncodedCookie(newCookiename, JSON.stringify(cookie), config.cookieduration);
        try {
            const results = await Translation.callTranslations(keys, config, settings);
            if (results && results.length > 0) {
                results.forEach((item) => {
                    if (item.error === '') {
                        onItemTranslated(item.key);
                    } else {
                        onErrorMessageItem(item.key, item.error);
                    }
                });
            } else {
                keys.forEach((key) => {
                    onItemTranslated(key);
                });
            }
        } catch (error) {
            onTranslationFailed(error.message || error);
        } finally {
            onTranslationDone();
        }
    };
    /**
     * Helper function for prepareSettingsAndCookieValues.
     *
     * @param {string} selector
     * @param {element} element
     * @param {object} cookie
     * @param {object} settings
     */
    const getSettingValue = (selector, element, cookie, settings) => {
        switch (element.type) {
            case 'select-one':
                cookie[selector] = element.value;
                settings[selector] = element.value;
                break;

            case 'textarea':
                cookie[selector] = element.value;
                settings[selector] = Utils.toJsonArray(element.value);
                break;

            case 'checkbox':
                cookie[selector] = element.checked;
                if (selector === Selectors.deepl.tagHandling) {
                    settings[selector] = element.checked ? 'html' : 'xml';
                } else {
                    settings[selector] = element.checked;
                }
                break;

            case 'radio':
                settings[selector] = cookie[selector] = queryRadioValue(selector);
                break;

            default:
                settings[selector] = cookie[selector] = element.value;
        }
    };
    /**
     * Parse the advanced settings UI and map the values for cookies and Deepl.
     *
     * @returns {[{},{}]}
     */
    const prepareSettingsAndCookieValues = () => {
        const settings = {};
        const cookie = {};

        for (const selector in settingsUI) {
            const element = settingsUI[selector];

            if (!element) {
                Log.warn(`prepareSettingsAndCookieValues. Could not find selector ${selector}`);
                Log.warn(settingsUI);
                continue;
            }

            getSettingValue(selector, element, cookie, settings);
        }

        return [cookie, settings];
    };
    /**
     * Factory to display process' statuses for each item.
     *
     * @param {String} key
     * @param {Boolean} checked
     */
    const toggleStatus = (key, checked) => {
        Log.info(key);
        const validatorbtn = domQuery(Selectors.actions.validatorBtn, key);
        const status = validatorbtn.dataset.status;
        switch (status) {
            case Selectors.statuses.wait :
                Translation.initTemp(key); // Reset the translation.
                if (checked) {
                    setIconStatus(key, Selectors.statuses.totranslate);
                }
                break;
            case Selectors.statuses.totranslate :
                if (checked && Translation.translated[key]) {
                    setIconStatus(key, Selectors.statuses.tosave, true);
                } else {
                    setIconStatus(key, Selectors.statuses.wait);
                }
                break;
            case Selectors.statuses.tosave :
                if (!checked) {
                    setIconStatus(key, Selectors.statuses.totranslate);
                }
                break;
            case Selectors.statuses.failed :
                if (checked) {
                    setIconStatus(key, Selectors.statuses.totranslate);
                }
                break;
            case Selectors.statuses.success :
                break;
            case Selectors.statuses.saved :
                if (checked) {
                    setIconStatus(key, Selectors.statuses.totranslate);
                }
                Translation.initTemp(key);
                break;
        }
    };
    /**
     * Shows/hides rows.
     */
    const showRows = () => {
        // Map each selector to its corresponding checkbox checked state
        const selectorMap = {
            [Selectors.statuses.updated]: domQuery(Selectors.actions.showUpdated).checked,
            [Selectors.statuses.needsupdate]: domQuery(Selectors.actions.showNeedUpdate).checked,
            [Selectors.statuses.hidden]: domQuery(Selectors.actions.showHidden).checked
        };

        // Combine all selectors into one comma-separated string for batch querying.
        const mergedSelector = Object.keys(selectorMap).join(",");

        // Query all items matching any of the selectors once.
        const allItems = domQueryAll(mergedSelector);

        // Cache the global "select all" button checked state once.
        const allSelected = domQuery(Selectors.actions.selectAllBtn).checked;

        allItems.forEach(item => {
            // Determine which selector this item matches so we can apply the right checkbox state.
            let shouldShow = false;
            let shouldCheck = false;
            for (const [selector, checked] of Object.entries(selectorMap)) {
                if (item.matches(selector)) {
                    shouldShow = checked;
                    shouldCheck = allSelected && checked;
                    break;
                }
            }

            // Show or hide item based on checkbox state.
            item.classList.toggle("d-none", !shouldShow);

            // Handle checkbox selection for this item or its children.
            let rowId = item.getAttribute('data-row-id');

            if (rowId === null) {
                // For items without row-id, toggle checkboxes of their child rows.
                const childs = domQueryAll(Selectors.statuses.hiddenForStudentRows, '', item);
                childs.forEach(child => {
                    const childId = child.getAttribute('data-row-id');
                    Log.info("Whitin LooP");
                    Log.debug(childId);
                    toggleChildCheckBoxSelection(childId, shouldCheck);
                });
            } else {
                Log.info("RowId not null");
                Log.debug(rowId);
                toggleChildCheckBoxSelection(rowId, shouldCheck);
            }
        });

        // Call global UI update functions once
        toggleAutotranslateButton();
        countWordAndChar();
    };
    /**
     * Manages selection and icon status of fields.
     *
     * @param {string} key
     * @param {bool} shouldBeChecked
     */
    const toggleChildCheckBoxSelection = (key, shouldBeChecked)=>{
        Log.info(`ui/toggleChildCheckBoxSelection`);
        Log.debug(key);
        Log.debug(Selectors.editors.multiples.checkBoxesWithKey);
        const single = domQuery(Selectors.editors.multiples.checkBoxesWithKey, key);
        Log.info(single);
        single.checked = shouldBeChecked;
        toggleStatus(key, false);
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
        const editor = domQuery(Selectors.editors.multiples.editorsWithKey, key);
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
     * Hides an item's error message.
     *
     * @param {String} key
     */
    const hideErrorMessage = (key) => {
        let parent = domQuery(Selectors.editors.multiples.editorsWithKey, key);
        let alertChild = domQuery('.alert-danger', '', parent);
        if (alertChild) {
            alertChild.remove();
        }
    };
    /**
     * Displays success message and icon.
     *
     * @param {String} key
     * @param {string} savedText
     */
    const onSuccessMessageItem = (key, savedText) => {
        domQuery(Selectors.editors.multiples.editorsWithKey, key)
            .classList.add("local_deepler__success");
        // Add saved indicator.
        setIconStatus(key, Selectors.statuses.success);
        // Replace text in the multilang textarea.
        const multilangTextarea = domQuery(Selectors.editors.multiples.textAreas, key);
        multilangTextarea.innerHTML = savedText;
        // Deselect the checkbox.
        domQuery(Selectors.editors.multiples.checkBoxesWithKey, key).checked = false;
        // Remove success message after a few seconds.
        setTimeout(() => {
            let multilangPill = domQuery(Selectors.statuses.multilang, key);
            let prevTransStatus = domQuery(Selectors.statuses.prevTransStatus, key);
            if (prevTransStatus) {
                prevTransStatus.className = "badge badge-pill rounded-pill badge-success";
                prevTransStatus.setAttribute('title', langstrings.uistrings.uptodate || "Up to date");
            }
            let sourceContainer = domQuery(Selectors.sourcetexts.keys, key);
            if (sourceContainer) {
                sourceContainer.setAttribute('data-filedtext-raw', Utils.toBase64(savedText));
            }
            let rowEl = domQuery(`[data-row-id="${key}"]`);
            if (rowEl) {
                rowEl.setAttribute('data-status', 'updated');
            }
            if (multilangPill && multilangPill.classList.contains("disabled")) {
                multilangPill.classList.remove('disabled');
            }
            setIconStatus(key, Selectors.statuses.saved);
        });
    };
    /**/
    /**
     * Display error message attached to the item's editor.
     * @param {String} key
     * @param {String} message
     */
    const showErrorMessageForEditor = (key, message) => {
        let parent = domQuery(Selectors.editors.multiples.editorsWithKey, key);
        const errorMsg = document.createElement('div');
        errorMsg.id = 'local_deepler__errormsg';
        errorMsg.classList = ['alert alert-danger'];
        errorMsg.innerHTML = message;
        parent.appendChild(errorMsg);
    };
    /**
     * Update rephrase symbol on main source language dropdown options (strip all).
     */
    const updateMainSourceDropdownNoRephrase = () => {
        const sourceSelect = domQuery(Selectors.actions.sourceSwitcher);
        if (!sourceSelect) {
            return;
        }
        Array.from(sourceSelect.options).forEach((option) => {
            const rawCode = Utils.stripRephraseSymbol(option.value, config.rephrasesymbol);
            const rawText = Utils.stripRephraseSymbol(option.textContent, config.rephrasesymbol);
            option.value = rawCode;
            option.textContent = rawText;
        });
    };

    /**
     * Update rephrase symbol on target language dropdown options.
     *
     * @param {string} sourceLang
     */
    const updateTargetLangDropdownRephrase = (sourceLang) => {
        const targetSelect = domQuery(Selectors.actions.targetSwitcher);
        if (!targetSelect) {
            return;
        }
        const currentTargetVal = Utils.stripRephraseSymbol(targetSelect.value, config.rephrasesymbol);
        Array.from(targetSelect.options).forEach((option) => {
            if (!option.value) {
                return;
            }
            const rawCode = Utils.stripRephraseSymbol(option.value, config.rephrasesymbol);
            const rawText = Utils.stripRephraseSymbol(option.textContent, config.rephrasesymbol);
            const same = Utils.isRephrase(sourceLang, rawCode);
            const canRephrase = config.canimprove && Utils.isRephrasable(rawCode, config.supportedlangs);

            if (same && canRephrase) {
                option.value = config.rephrasesymbol + rawCode;
                option.textContent = config.rephrasesymbol + rawText;
            } else {
                option.value = rawCode;
                option.textContent = rawText;
            }
        });
        const matchingOpt = Array.from(targetSelect.options).find(
            (opt) => Utils.stripRephraseSymbol(opt.value, config.rephrasesymbol) === currentTargetVal
        );
        if (matchingOpt) {
            targetSelect.value = matchingOpt.value;
        }
    };

    /**
     * Update rephrase symbol on individual row source dropdowns.
     *
     * @param {string} targetLang
     */
    const updateRowSourceDropdownsRephrase = (targetLang) => {
        const rowSelects = domQueryAll(Selectors.actions.sourceselect);
        rowSelects.forEach((rowSelect) => {
            const currentRowVal = Utils.stripRephraseSymbol(rowSelect.value, config.rephrasesymbol);
            Array.from(rowSelect.options).forEach((option) => {
                if (!option.value) {
                    return;
                }
                const rawCode = Utils.stripRephraseSymbol(option.value, config.rephrasesymbol);
                const rawText = Utils.stripRephraseSymbol(option.textContent, config.rephrasesymbol);
                const same = Utils.isRephrase(rawCode, targetLang);
                const canRephrase = config.canimprove && Utils.isRephrasable(rawCode, config.supportedlangs);

                if (same && canRephrase) {
                    option.value = config.rephrasesymbol + rawCode;
                    option.textContent = config.rephrasesymbol + rawText;
                } else {
                    option.value = rawCode;
                    option.textContent = rawText;
                }
            });
            const matchingOpt = Array.from(rowSelect.options).find(
                (opt) => Utils.stripRephraseSymbol(opt.value, config.rephrasesymbol) === currentRowVal
            );
            if (matchingOpt) {
                rowSelect.value = matchingOpt.value;
            }
        });
    };

    /**
     * Updates the translation status bullet (#previousTranslationStatus) and checkbox tooltip for all rows.
     */
    const updateAllRowsTranslationStatus = () => {
        const targetLang = Utils.stripRephraseSymbol(config.targetlang || '', config.rephrasesymbol);
        const rowElements = domQueryAll('[data-row-id]');

        rowElements.forEach((rowEl) => {
            const statusAttr = rowEl.getAttribute('data-status');
            if (statusAttr === 'local_deepler/disabled') {
                return;
            }

            const key = rowEl.getAttribute('data-row-id');
            const bullet = domQuery(Selectors.statuses.prevTransStatus, key);
            const checkbox = domQuery(Selectors.editors.multiples.checkBoxesWithKey, key);
            const sourceContainer = domQuery(Selectors.sourcetexts.keys, key);
            if (!bullet || !sourceContainer) {
                return;
            }

            const rawFieldBase64 = sourceContainer.getAttribute('data-filedtext-raw') || '';
            const rawFieldText = Utils.fromBase64(rawFieldBase64);

            const rowSourceSelect = domQuery(Selectors.sourcetexts.sourcelangdd, key);
            const rowSourceLang = rowSourceSelect
                ? Utils.stripRephraseSymbol(rowSourceSelect.value, config.rephrasesymbol)
                : (config.deeplsourcelang || config.sourcelang || 'en');

            const statusInfo = Utils.getTranslationStatusInfo(
                rawFieldText,
                rowSourceLang,
                targetLang,
                config,
                langstrings.uistrings
            );

            bullet.className = 'badge badge-pill rounded-pill ' + statusInfo.buttonClass;
            bullet.setAttribute('title', statusInfo.titleString);
            if (checkbox) {
                checkbox.setAttribute('title', statusInfo.titleString);
            }
            rowEl.setAttribute('data-status', statusInfo.status);
        });
    };

    /**
     * Event listener to switch target lang dynamically without page reloading.
     * @param {Event} e
     */
    const switchTarget = (e) => {
        const targetLang = Utils.stripRephraseSymbol(e.target.value, config.rephrasesymbol);
        // Store in cookie for current session only (no expires attribute).
        document.cookie = "local_deepler_targetlang=" + encodeURIComponent(targetLang) + "; path=/; SameSite=Lax";
        config.targetlang = targetLang;

        Translation.setMainLangs({
            currentlang: config.currentlang,
            deeplsourcelang: config.deeplsourcelang,
            targetlang: targetLang,
        });

        const reactive = StateManager.getStateManager(config.courseid || 'main');
        if (reactive) {
            reactive.dispatch('setGlobalSetting', 'targetLang', targetLang);
        }

        // Update target language header label.
        const targetHeader = domQuery('#local_deepler__targetlang');
        if (targetHeader && langstrings.uistrings.selecttargetlanguage) {
            targetHeader.innerHTML = langstrings.uistrings.selecttargetlanguage.replace('{$a}', targetLang);
        }

        // Update translate button text.
        const translateBtn = domQuery(Selectors.actions.autoTranslateBtn);
        if (translateBtn) {
            if (langstrings.uistrings.translatebutton) {
                translateBtn.innerHTML = langstrings.uistrings.translatebutton.replace('{$a}', targetLang);
            }
            if (langstrings.uistrings.translateexplain) {
                translateBtn.setAttribute('title', langstrings.uistrings.translateexplain.replace('{$a}', targetLang));
            }
        }

        // Update compatible languages switcher.
        const langroot = Utils.removeRegionalVariant(targetLang);
        const moodleLangs = config.moodlelangs || [];
        const compatibles = moodleLangs.filter((code) => code.includes(langroot)).sort();
        const compatSelect = domQuery(Selectors.actions.targetCompatibleSwitcher);
        if (compatSelect) {
            compatSelect.innerHTML = '';
            compatibles.forEach((code) => {
                const opt = document.createElement('option');
                opt.value = code;
                opt.textContent = code;
                compatSelect.appendChild(opt);
            });
            if (compatibles.length > 0) {
                compatSelect.value = compatibles[0];
            }
            compatSelect.style.display = compatibles.length >= 2 ? 'inherit' : 'none';
        }

        // Update ® symbols on target dropdown and row source dropdowns.
        updateTargetLangDropdownRephrase(config.deeplsourcelang || config.sourcelang || 'en');
        updateRowSourceDropdownsRephrase(targetLang);

        // Re-initialize Translation temp items for all rows.
        const rowElements = domQueryAll('[data-row-id]');
        rowElements.forEach((rowEl) => {
            const key = rowEl.getAttribute('data-row-id');
            const sourceContainer = domQuery(Selectors.sourcetexts.keys, key);
            if (!sourceContainer) {
                return;
            }
            const rowSourceSelect = domQuery(Selectors.sourcetexts.sourcelangdd, key);
            const sourceRawBase64 = sourceContainer.getAttribute('data-sourcetext-raw') || '';
            const fieldRawBase64 = sourceContainer.getAttribute('data-filedtext-raw') || '';
            const editor = findEditor(key);
            Translation.initTempForKey(
                key,
                editor,
                sourceRawBase64,
                fieldRawBase64,
                rowSourceSelect ? rowSourceSelect.value : (config.deeplsourcelang || config.sourcelang || 'en')
            );
        });

        // Update translation status bullets and tooltips for all rows.
        updateAllRowsTranslationStatus();

        // Update buttons state based on new target lang.
        selectAllBtn.disabled = !Translation.isTranslatable();
        checkboxes.forEach((node) => {
            node.disabled = selectAllBtn.disabled;
        });
        toggleAutotranslateButton();
        debouncedShowRows();
    };
    /**
     * Event listener to filter sections.
     * @param {Event} e
     */
    const switchSection = (e) => {
        let url = new URL(window.location.href);
        let searchParams = url.searchParams;
        // Pass the target lang in the url and refresh, not forgetting to remove the rephrase prefix indicator.
        searchParams.set("section_id", e.target.value.trim());
        if (searchParams.has("activity_id")) {
            searchParams.delete("activity_id");
        }
        window.location = url.toString();
    };
    /**
     * Event listener to filter modules.
     * @param {Event} e
     */
    const switchModules = (e) => {
        let url = new URL(window.location.href);
        let searchParams = url.searchParams;
        // Pass the target lang in the url and refresh, not forgetting to remove the rephrase prefix indicator.
        searchParams.set("activity_id", e.target.value.trim());
        window.location = url.toString();
    };
    /**
     * Event listener to switch source lang dynamically without page reloading.
     * Sets the source lang in session cookie and re-filters all rows.
     *
     * @param {Event} e
     */
    const switchSource = async(e) => {
        const sourceLang = e.target.value.replace(config.rephrasesymbol, '').trim();
        // Store in cookie for current session only (no expires attribute).
        document.cookie = "local_deepler_sourcelang=" + encodeURIComponent(sourceLang) + "; path=/; SameSite=Lax";

        config.sourcelang = sourceLang;
        config.deeplsourcelang = sourceLang;
        config.currentlang = sourceLang;

        Translation.setMainLangs({
            currentlang: sourceLang,
            deeplsourcelang: sourceLang,
            targetlang: config.targetlang,
        });

        const reactive = StateManager.getStateManager(config.courseid || 'main');
        if (reactive) {
            reactive.dispatch('setGlobalSetting', 'sourceLang', sourceLang);
        }

        // Ensure main source dropdown has NO ® symbols.
        updateMainSourceDropdownNoRephrase();

        // Update target dropdown ® symbols based on new source language.
        updateTargetLangDropdownRephrase(sourceLang);

        // Update row source dropdowns ® symbols based on target language.
        updateRowSourceDropdownsRephrase(config.targetlang);

        // Iterate through all translation rows and dynamically re-filter strings.
        const rowElements = domQueryAll('[data-row-id]');
        rowElements.forEach((rowEl) => {
            const key = rowEl.getAttribute('data-row-id');
            const sourceContainer = domQuery(Selectors.sourcetexts.keys, key);
            if (!sourceContainer) {
                return;
            }

            const rawUnfilteredBase64 = sourceContainer.getAttribute('data-filedtext-raw') || '';
            const rawUnfiltered = Utils.fromBase64(rawUnfilteredBase64);
            const mlangResult = Utils.extractMultilangSource(rawUnfiltered, sourceLang);

            // Update data-sourcetext-raw with base64 encoded filtered text.
            sourceContainer.setAttribute('data-sourcetext-raw', Utils.toBase64(mlangResult.filteredText));

            // Update visible DOM text.
            const preEl = sourceContainer.querySelector('pre');
            if (preEl) {
                preEl.textContent = mlangResult.filteredText;
            } else {
                sourceContainer.innerHTML = mlangResult.filteredText;
            }

            // Update row source selection dropdown.
            const rowSourceSelect = domQuery(Selectors.sourcetexts.sourcelangdd, key);
            if (rowSourceSelect) {
                if (mlangResult.mlangTagMatched) {
                    rowSourceSelect.disabled = true;
                    if (mlangResult.matchedCode) {
                        rowSourceSelect.value = mlangResult.matchedCode;
                    }
                    rowSourceSelect.classList.remove('is-warning', 'border-warning');
                } else if (mlangResult.isFallbackOther) {
                    rowSourceSelect.disabled = false;
                    rowSourceSelect.value = sourceLang;
                    // Trigger auto-detection if enabled.
                    if (config.autodetect_sourcelang) {
                        Api.detectSourceLanguage(mlangResult.filteredText, key)
                            .then((res) => {
                                if (res && res.detected_lang && reactive) {
                                    reactive.dispatch('setDetectedSourceLang', key, res.detected_lang.toLowerCase());
                                }
                                return res;
                            })
                            .catch((err) => {
                                Log.debug('Auto-detection error: ' + err);
                            });
                    }
                } else {
                    rowSourceSelect.disabled = false;
                    rowSourceSelect.value = sourceLang;
                    rowSourceSelect.classList.remove('is-warning', 'border-warning');
                }
            }

            // Re-initialize Translation temp item.
            const editor = findEditor(key);
            Translation.initTempForKey(
                key,
                editor,
                Utils.toBase64(mlangResult.filteredText),
                rawUnfilteredBase64,
                rowSourceSelect ? rowSourceSelect.value : sourceLang
            );

            // Update reactive state for this row.
            const counts = getCount(key);
            if (reactive) {
                const strippedText = Utils.stripHTMLTags(mlangResult.filteredText).trim();
                reactive.dispatch('updateRowOriginalText', key, strippedText, counts.wordCount || 0, counts.charNumWithSpace || 0);
                reactive.dispatch('setRowSourceLang', key, rowSourceSelect ? rowSourceSelect.value : sourceLang);
            }
        });

        // Update translation status bullets and tooltips for all rows.
        updateAllRowsTranslationStatus();

        countWordAndChar();
    };
    /**
     * Launch, display count of Words And Chars.
     */
    const countWordAndChar = () => {
        let wrdsc = 0,
        cws = 0,
        cwos = 0;

        // Cache checkboxes matching selector once, convert to array if needed.
        const checkedBoxes = Array.from(domQueryAll(Selectors.statuses.checkedCheckBoxes));

        // Aggregate counts in one loop.
        checkedBoxes.forEach(ckBox => {
            const key = ckBox.getAttribute('data-key');
            const results = getCount(key);
            wrdsc += results.wordCount;
            cws += results.charNumWithSpace;
            cwos += results.charNumWithOutSpace;
        });

        // Cache DOM elements once for output instead of querying repeatedly.
        const wordCount = cachedSelectors.wordCount;
        const charWithSpace = cachedSelectors.charWithSpace;
        const charWOSpace = cachedSelectors.charWOSpace;
        const deeplUseSpan = cachedSelectors.deeplUseSpan;
        const deeplMaxSpan = cachedSelectors.deeplMaxSpan;
        const parent = cachedSelectors.deeplStatusContainer;

        // Calculate current usage once.
        const current = cws + config.usage.character.count;

        // Update UI in one batch (minimizes layout thrashing).
        wordCount.innerText = wrdsc;
        charWithSpace.innerText = cws;
        charWOSpace.innerText = cwos;
        deeplUseSpan.innerText = format.format(current);
        deeplMaxSpan.innerText = config.usage.character.limit === null ? '∞' : format.format(config.usage.character.limit);

        // Toggle classes efficiently.
        parent.classList.toggle('alert-success', current < config.usage.character.limit || config.usage.character.limit === null);
        parent.classList.toggle('alert-danger', current >= config.usage.character.limit && config.usage.character.limit !== null);
    };
    /**
     * Get the editor container based on recieved current user's editor preference.
     *
     * @param {string} key Translation Key
     */
    const findEditor = (key) => {
        Log.info('findEditor', key);
        let e = domQuery(Selectors.editors.types.basic, key);
        let et = 'basic';
        if (e === null) {
            let r = null;
            let editorTab = ["atto", "tiny", "marklar", "textarea"];
            if (editorTab.indexOf(config.userPrefs) === -1) {
                Log.warn('Unsupported editor ' + config.userPrefs);
            } else {
                // First let's try the current editor.
                try {
                    r = findEditorByType(key, config.userPrefs);
                } catch (error) {
                    // Content was edited by another editor.
                    Log.trace(`Editor not found: ${config.userPrefs} for key ${key}`);
                }
            }
            return r;
        } else {
            return {editor: e, editorType: et};
        }
    };
    /**
     * @param {string} key
     * @param {object} editorType
     * @returns {{editor: object, editorType: string}}
     */
    const findEditorByType = (key, editorType) => {
        Log.info(key, editorType);
        let et = 'basic';
        let ed = null;
        switch (editorType) {
            case "atto" :
                et = 'iframe';
                ed = domQuery(Selectors.editors.types.atto, key);
                break;
            case "tiny":
                et = 'iframe';
                ed = findTinyInstanceByKey(key);
                break;
            case 'marklar':
            case "textarea" :
                ed = domQuery(Selectors.editors.types.other, key);
                break;
        }
        Log.info('Editor container :');
        Log.info(ed);
        return {editor: ed, editorType: et};
    };
    /**
     * Finds TinyMCE instance.
     * @param {string} key
     * @returns {Node}
     */
    const findTinyInstanceByKey = (key)=> {
        let editor = null;
        TinyMCE.getAllInstances().every((k, v)=>{
            if (v.attributes.name.value.indexOf(key) == 0) {
                editor = k.getBody();
                return false;
            }
            return true;
        });
        return editor;
    };
    /**
     * Compile the needed counts for info.
     *
     * @param {string} key
     * @returns {{wordCount: *, charNumWithSpace: *, charNumWithOutSpace: *}}
     */
    const getCount = (key) => {
        const item = domQuery(Selectors.sourcetexts.keys, key);
        const raw = item.getAttribute("data-sourcetext-raw");
        // Cleaned sourceText.
        const trimmedVal = Utils.stripHTMLTags(Utils.fromBase64(raw)).trim();
        return {
            "wordCount": (trimmedVal.match(/\S+/g) || []).length,
            "charNumWithSpace": trimmedVal.length,
            "charNumWithOutSpace": trimmedVal.replace(/\s+/g, '').length
        };
    };
    /**
     * Wrapper for radios value.
     *
     * @param {string} selector
     * @returns {*}
     */
    const queryRadioValue = (selector) => {
        return domQuery(Selectors.actions.radioValues.replace("<RADIO>", selector)).value;
    };
    /**
     * Shortcut for dom querySelector.
     *
     * @param {string} selector
     * @param {string} key
     * @param {element} target
     * @returns {element}
     */
    const domQuery = (selector, key = '', target = null) => {
        const el = target ?? document;
        // Some selectors (e.g. editors.types.other) hold <KEY> more than once: replace all occurrences.
        const q = key === '' ? selector : selector.replaceAll("<KEY>", key);
        return el.querySelector(q);
    };

    /**
     * Shortcut for dom querySelector.
     *
     * @param {string} selector
     * @param {string} key
     * @param {element} target
     * @returns {NodeList}
     */
    const domQueryAll = (selector, key = '', target = null) => {
        const el = target ?? document;
        const q = key === '' ? selector : selector.replaceAll("<KEY>", key);
        return el.querySelectorAll(q);
    };
    /**
     * Initializes the reactive state manager and registers page & row components.
     *
     * @param {object} cfg
     */
    const initReactiveState = (cfg) => {
        try {
            const rowElements = domQueryAll('[data-row-id]');
            const rows = [];
            rowElements.forEach((el) => {
                const rowId = el.getAttribute('data-row-id');
                const counts = getCount(rowId);
                const sourceEl = domQuery(Selectors.sourcetexts.keys, rowId);
                const raw = sourceEl ? sourceEl.getAttribute("data-sourcetext-raw") : "";
                const originalText = raw ? Utils.stripHTMLTags(Utils.fromBase64(raw)).trim() : "";
                const checkbox = domQuery(Selectors.actions.checkBoxes, rowId, el);
                const isSelected = checkbox ? checkbox.checked : true;

                rows.push({
                    id: rowId,
                    key: rowId,
                    sourceLang: cfg.sourcelang || 'en',
                    originalText: originalText,
                    translatedText: '',
                    customSettings: null,
                    status: 'idle',
                    errorMessage: null,
                    selected: isSelected,
                    isDirty: false,
                    wordCount: counts.wordCount || 0,
                    charCount: counts.charNumWithSpace || 0,
                });
            });

            const reactive = StateManager.getStateManager(cfg.courseid || 'main', {
                targetLang: cfg.targetlang || 'fr',
                formality: 'default',
                tagHandling: 'html',
                splitSentences: '1',
                preserveFormatting: false,
                escapeLatex: true,
                escapePre: true,
                hideIframes: true,
                glossaryId: '',
                modelType: 'default',
                toneorstyle: 'default',
                rows: rows,
            });

            // Register Page Component on container
            const pageContainer = document.querySelector('.local_deepler__form') || document.body;
            new PageComponent({
                element: pageContainer,
                reactive: reactive,
            });

            // Register Row Components on each row element
            rowElements.forEach((el) => {
                new RowComponent({
                    element: el,
                    reactive: reactive,
                });
            });

            // Initial stats recalculation
            reactive.dispatch('recalculateStats');
        } catch (err) {
            Log.error('Error initializing reactive state: ' + err.message);
        }
    };

    /**
     * Event listener to switch source lang.
     * @param {*} cfg
     */
    const init = (cfg) => {
        offsetTop = domQuery(Selectors.config.langstrings).offsetTop;
        ScrollSpy.init('.local_deepler__form', '#local_deepler-scrollspy',
            {highestLevel: 3, fadingDistance: 60, offsetEndOfScope: 1, offsetTop: 100, crumbsmaxlen: cfg.crumbsmaxlen});
        Translation.init(cfg);
        config = cfg;
        Log.info(cfg);
        registerUI();
        registerEventListeners();
        toggleAutotranslateButton();
        doHideiframes(hideiframes.checked);
        saveAllBtn.disabled = true;
        selectAllBtn.disabled = !Translation.isTranslatable();
        checkboxes.forEach((node) => {
            node.disabled = selectAllBtn.disabled;
        });
        updateAllRowsTranslationStatus();
        debouncedShowRows();
        updateMainSourceDropdownNoRephrase();
        updateTargetLangDropdownRephrase(cfg.deeplsourcelang || cfg.sourcelang || 'en');
        updateRowSourceDropdownsRephrase(cfg.targetlang || '');
        initReactiveState(cfg);
    };
    /**
     * Api to be used by the other modules.
     */
    return {
        init: init
    };
});
