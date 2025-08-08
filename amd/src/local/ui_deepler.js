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
    './selectors',
    './translation',
    './utils',
    './customevents',
    './scrollspy',
    './api'
], (Log, TinyMCEinit, TinyMCE,
    Modal,
    Selectors,
    Translation,
    Utils,
    Events,
    ScrollSpy,
    Api) => {
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
    /**
     * When a main error with the DB occurs.
     *
     * @param {string} error
     * @param {int} status
     */
    const onDBFailed = (error, status) => {
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

        // Translation events.
        Events.on(Translation.ON_ITEM_TRANSLATED, onItemTranslated);
        Events.on(Translation.ON_TRANSLATION_FAILED, onTranslationFailed);
        Events.on(Translation.ON_REPHRASE_FAILED, onTranslationFailed);
        Events.on(Translation.ON_DB_SAVE_SUCCESS, onDbSavedSuccess);
        Events.on(Translation.ON_DB_FAILED, onDBFailed);
        Events.on(Translation.ON_ITEM_SAVED, onSuccessMessageItem);
        Events.on(Translation.ON_ITEM_NOT_SAVED, onErrorMessageItem);
        Events.on(Api.GLOSSARY_DB_ALL_FAILED, onGlossaryDbAllfailed);
        Events.on(Api.GLOSSARY_DB_FAILED, onGlossaryDbfailed);
        Events.on(Api.GLOSSARY_DB_SUCCESS, onGlossaryDbSuccess);
        Events.on(Api.GLOSSARY_ENTRIES_SUCCESS, showEntriesModal);
        Events.on(Api.GLOSSARY_ENTRIES_FAILED, (e)=>Log.error(e));
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
            glossaryDetailViewr = domQuery(Selectors.glossary.entriesviewerPage);
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
     * @returns {Promise<void>}
     */
    const launchModal = async() => {
        saveAllModal = await Modal.create({
            title: langstrings.uistrings.saveallmodaltitle,
            body: langstrings.uistrings.saveallmodalbody,
        });
        await saveAllModal.show();
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
            if ((!config.canimprove && config.deeplsourcelang === config.targetlang) || config.targetlang === undefined) {
                showModal('Cannot call deepl', `<p>${langstrings.uistrings.canttranslatesame} ${config.targetlang}</p>`);
            } else {
                callDeeplServices();
            }
        }
        if (e.target.closest(Selectors.actions.selectAllBtn)) {
            toggleAllCheckboxes(e);
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
        if (e.target.closest(Selectors.actions.sourceSwitcher)) {
            switchSource(e);
        }
        if (e.target.closest(Selectors.actions.showUpdated)) {
            showRows(Selectors.statuses.updated, e.target.checked);
        }
        if (e.target.closest(Selectors.actions.showNeedUpdate)) {
            showRows(Selectors.statuses.needsupdate, e.target.checked);
        }
        if (e.target.closest(Selectors.actions.showHidden)) {
            showRows(Selectors.statuses.hidden, e.target.checked);
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
    const saveTranslations = () => {
        const selectedCheckboxes = domQueryAll(Selectors.statuses.checkedCheckBoxes);
        if (selectedCheckboxes.length === 0) {
            return;
        }
        // Prepare the UI for the save process.
        saveAllBtn.disabled = true;
        launchModal().then(r => Log.info('SaveAll Modal launched ' + r)).catch((reason)=>{
            Log.error(reason);
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
        Translation.saveTranslations(data, config);
    };
    /**
     * Saving a single translation to DB.
     * @param {Event} e
     */
    const saveSingleTranslation = (e)=> {
        const key = e.target.closest(Selectors.actions.validatorsBtns).dataset.keyValidator;
        if (getIconStatus(key) === Selectors.statuses.tosave) {
            hideErrorMessage(key);
            Translation.saveTranslations([prepareDBitem(key)], config);
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
     * Toggle checkboxes
     * @param {Event} e Event
     */
    const toggleAllCheckboxes = (e) => {
        // Check/uncheck checkboxes
        if (e.target.checked) {
            checkboxes.forEach((i) => {
                // Toggle check box upon visibility
                i.checked = !getParentRow(i).classList.contains('d-none');
                toggleStatus(i.getAttribute('data-key'), i.checked);
            });
        } else {
            checkboxes.forEach((i) => {
                i.checked = false;
                toggleStatus(i.getAttribute('data-key'), false);
            });
        }
        toggleAutotranslateButton();
        countWordAndChar();
    };
    /**
     * Toggle Autotranslate Button
     */
    const toggleAutotranslateButton = () => {
        autotranslateButton.disabled = true;
        for (let i in checkboxes) {
            let e = checkboxes[i];
            if (e.checked) {
                autotranslateButton.disabled = false;
                break;
            }
        }
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
            if (!icon.classList.contains('disable')) {
                icon.classList.add('disable');
            }
            if (icon.classList.contains('btn')) {
                icon.classList.remove('btn');
                icon.classList.remove('btn-outline-secondary');
            }
        } else {
            if (!icon.classList.contains('btn')) {
                icon.classList.add('btn');
                icon.classList.add('btn-outline-secondary');
            }
            if (icon.classList.contains('disable')) {
                icon.classList.remove('disable');
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
        showModal(s, error, 'Alert');
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
    const callDeeplServices = () => {
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
        Translation.callTranslations(keys, config, settings);
    };
    /**
     * Parse the advanced settings UI and map the values for cookies and Deepl.
     *
     * @returns {[{},{}]}
     */
    const prepareSettingsAndCookieValues = () => {
        let settings = {};
        let cookie = {};
        for (const selector in settingsUI) {
            if (settingsUI[selector] === null) {
                Log.warn(`prepareSettingsAndCookieValues. Could not find selector ${selector}`);
                Log.warn(settingsUI);
            } else {
                switch (settingsUI[selector].type) {
                    case 'select-one':
                        cookie[selector] = settingsUI[selector].value;
                        settings[selector] = settingsUI[selector].value;
                        break;
                    case 'textarea':
                        cookie[selector] = settingsUI[selector].value;
                        // Deepl needs an array.
                        settings[selector] = Utils.toJsonArray(cookie[selector]);
                        break;
                    case 'checkbox':
                        if (selector === Selectors.deepl.tagHandling) {
                            cookie[selector] = settingsUI[selector].checked;
                            // Exception for tag_handling that checkbox but not boolean value for Deepl.
                            settings[selector] = settingsUI[selector].checked ? 'html' : 'xml';
                        } else {
                            settings[selector] = cookie[selector] = settingsUI[selector].checked;
                        }
                        break;
                    case 'radio':
                        settings[selector] = cookie[selector] = queryRadioValue(selector);
                        break;
                    default: // Text.
                        settings[selector] = cookie[selector] = settingsUI[selector].value;
                        break;
                }
            }
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
        const status = domQuery(Selectors.actions.validatorBtn, key).dataset.status;
        switch (status) {
            case Selectors.statuses.wait :
                Translation.initTemp(key); // Reset the translation.
                if (checked) {
                    setIconStatus(key, Selectors.statuses.totranslate);
                    // RefreshTempTranslation(key);
                }
                break;
            case Selectors.statuses.totranslate :
                // If (checked && Translation.tempTranslations[key]?.translation?.length > 0) {
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
     * @param {string} selector
     * @param {boolean} selected
     */
    const showRows = (selector, selected) => {
        const items = domQueryAll(selector);
        const allSelected = domQuery(Selectors.actions.selectAllBtn).checked;
        const shoudlcheck = allSelected && selected;
        items.forEach((item) => {
            let k = item.getAttribute('data-row-id');
            if (selected) {
                item.classList.remove("d-none");
            } else {
                item.classList.add("d-none");
            }
            // When a row is toggled then we don't want it to be selected and sent from translation.
            try {
                const single = domQuery(Selectors.editors.multiples.checkBoxesWithKey, k);
                if (single !== null) {
                    single.checked = shoudlcheck;
                    toggleStatus(k, false);
                }
                const allchilds = domQueryAll(Selectors.editors.multiples.checkBoxesWithKeyHidden, k);
                if (allchilds !== null && allchilds.length > 0) {
                    allchilds.forEach(c => {
                        const key = c.getAttribute('data-key');
                        c.checked = shoudlcheck;
                        toggleStatus(key, false);
                    });
                }
            } catch (e) {
                Log.warn(`${k} translation is disalbled`);
            }

        });
        toggleAutotranslateButton();
        countWordAndChar();
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
            parent.removeChild(alertChild);
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
            prevTransStatus.classList = "badge badge-pill badge-success";
            if (multilangPill.classList.contains("disabled")) {
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
     * Event listener to switch target lang.
     * @param {Event} e
     */
    const switchTarget = (e) => {
        let url = new URL(window.location.href);
        let searchParams = url.searchParams;
        // Pass the target lang in the url and refresh, not forgetting to remove the rephrase prefix indicator.
        searchParams.set("target_lang", e.target.value.replace(config.rephrasesymbol, '').trim());
        window.location = url.toString();
    };
    /**
     * Event listener to switch source lang,
     * Hence reload the page and change the site main lang.
     * @param {Event} e
     */
    const switchSource = (e) => {
        let url = new URL(window.location.href);
        let searchParams = url.searchParams;
        searchParams.set("lang", e.target.value);
        window.location = url.toString();
    };
    /**
     * Launch, display count of Words And Chars.
     */
    const countWordAndChar = () => {
        let wrdsc = 0;
        let cws = 0;
        let cwos = 0;
        domQueryAll(Selectors.statuses.checkedCheckBoxes)
            .forEach((ckBox) => {
                let key = ckBox.getAttribute("data-key");
                let results = getCount(key);
                wrdsc += results.wordCount;
                cwos += results.charNumWithOutSpace;
                cws += results.charNumWithSpace;
            });
        const wordCount = domQuery(Selectors.statuses.wordcount);
        const charWithSpace = domQuery(Selectors.statuses.charNumWithSpace);
        const charWOSpace = domQuery(Selectors.statuses.charNumWithOutSpace);
        const deeplUseSpan = domQuery(Selectors.statuses.deeplUsage);
        const deeplMaxSpan = domQuery(Selectors.statuses.deeplMax);
        const parent = domQuery(Selectors.statuses.deeplStatusContainer);
        let current = cws + config.usage.character.count;
        wordCount.innerText = wrdsc;
        charWithSpace.innerText = cws;
        charWOSpace.innerText = cwos;
        deeplUseSpan.innerText = format.format(current);
        deeplMaxSpan.innerText = config.usage.character.limit === null ? '∞' : format.format(config.usage.character.limit);
        if (current >= config.usage.character.limit) {
            parent.classList.remove('alert-success');
            parent.classList.add('alert-danger');
        } else {
            parent.classList.add('alert-success');
            parent.classList.remove('alert-danger');
        }
    };
    /**
     * Get the editor container based on recieved current user's editor preference.
     *
     * @param {string} key Translation Key
     */
    const findEditor = (key) => {
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
        const q = key === '' ? selector : selector.replace("<KEY>", key);
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
        const q = key === '' ? selector : selector.replace("<KEY>", key);
        return el.querySelectorAll(q);
    };
    /**
     * Event listener to switch source lang.
     * @param {*} cfg
     */
    const init = (cfg) => {
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
        showRows(Selectors.statuses.updated, domQuery(Selectors.actions.showUpdated).checked);
        showRows(Selectors.statuses.needsupdate, domQuery(Selectors.actions.showNeedUpdate).checked);
    };
    /**
     * Api to be used by the other modules.
     */
    return {
        init: init
    };
});
