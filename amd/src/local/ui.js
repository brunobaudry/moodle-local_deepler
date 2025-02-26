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
 * @file       amd/src/local/ui.js
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


define(['core/log', 'editor_tiny/editor', 'core/str', 'core/modal', './selectors', './translation', './utils', './customevents'],
    (Log, TinyMCE, Str, Modal, Selectors, Translation, Utils, Events) => {
    const {getString} = Str;
    let config = {};
    let autotranslateButton = {};
    let saveAllBtn = {};
    let selectAllBtn = {};
    let checkboxes = [];
    // Let sourceLang = "";
    // let targetLang = "";
    let format = new Intl.NumberFormat();
    let saveAllModal = {};
    let errordbtitle = '';

    const onDBFailed = () => {
        showModal(errordbtitle, 'DB failed to save translations', 'Alert');
    };
    const onTranslationSuccess = (errors) => {
        if (saveAllModal !== null && saveAllModal.isVisible) {
            saveAllModal.hide();
        }
        if (errors.length > 0) {
                getString('partialerrorstring', 'local_deepler', errors.length)
                    .then((s) => {
                     showModal(errordbtitle, s, 'Alert');
                    });
        }
    };
        // Const ON_TARGET_LANG_CHANGE = 'onTargetLangChange';
    /**
     * Event factories.
     */
    const registerEventListeners = () => {
        document.addEventListener('change', handleChangeEvent);
        document.addEventListener('click', handleClickEvent);

        // Translation events.
        Events.on(Translation.ON_ITEM_TRANSLATED, onItemTranslated);
        Events.on(Translation.ON_TRANSLATION_FAILED, onTranslationFailed);
        Events.on(Translation.ON_TRANSLATION_SUCCESS, onTranslationSuccess);
        Events.on(Translation.ON_DB_FAILED, onDBFailed);
        Events.on(Translation.ON_ITEM_SAVED, successMessageItem);
        Events.on(Translation.ON_ITEM_NOT_SAVED, errorMessageItem);
    };
    /**
     * Opens a modal infobox to warn user trunks of fields are saving.
     * @returns {Promise<void>}
     * ui.js
     */
    const launchModal = async() => {
        saveAllModal = await Modal.create({
            title: getString('saveallmodaltitle', 'local_deepler'),
            body: getString('saveallmodalbody', 'local_deepler'),
        });
        await saveAllModal.show();
    };
    /**
     * Event listener for change events.
     * @param {event} e
     */
    const handleChangeEvent = (e) => {
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
        if (e.target.closest(Selectors.actions.checkBoxes) || e.target.closest(Selectors.actions.sourceselect)) {
            onItemChecked(e);
        }
    };
    /**
     * Multilang button handler
     * @param {Event} e Event
     * ui.js ok
     */
    const onToggleMultilang = (e) => {
        let keyid = e.getAttribute('aria-controls');
        let key = Utils.keyidToKey(keyid);
        let source = domQuery(Selectors.sourcetexts.keys, key);
        let multilang = domQuery(Selectors.sourcetexts.multilangs, keyid);
        source.classList.toggle("show");
        multilang.classList.toggle("show");
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
            if (config.currentlang === config.lang || config.lang === undefined) {
                showModal('Cannot call deepl', `<p>Both languages are the same ${config.lang}</p>`);
            } else {
                doAutotranslate();
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
    };

        /**
         * @returns
         */
    const saveTranslations = () => {
        const selectedCheckboxes = domQueryAll(Selectors.statuses.checkedCheckBoxes);

        if (selectedCheckboxes.length === 0) {
            return;
        }
        saveAllBtn.disabled = true;
        launchModal().then(r => Log.info('SaveAll Modal launched ' + r));
        const data = [];
        const keys = Array.from(selectedCheckboxes).map((e) => e.dataset.key);
        keys.forEach((key) => {
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
        };
    };
    /**
     * Event listener for selection checkboxes.
     * @param {Event} e
     * ui.js
     */
    const onItemChecked = (e) => {
        Log.info("SELECTION", e.target.getAttribute('data-key'), e.target.getAttribute('data-action'));
        // Check/uncheck checkboxes changes the charcount and icon status.
        if (e.target.getAttribute('data-action') === "local_deepler/checkbox") {
            toggleStatus(e.target.getAttribute('data-key'), e.target.checked);
            countWordAndChar();
        }
        const editor = findEditor(e.target.getAttribute('data-key'));
        Log.debug(`ui/onItemChecked:197`);
        Log.debug(editor);
    };
    const registerUI = () => {
        try {
            saveAllBtn = document.querySelector(Selectors.actions.saveAll);
            selectAllBtn = domQuery(Selectors.actions.selectAllBtn);
           // SourceLang = document.querySelector(Selectors.actions.sourceSwitcher).value;
            // targetLang = document.querySelector(Selectors.actions.targetSwitcher).value;
            autotranslateButton = domQuery(Selectors.actions.autoTranslateBtn);
            checkboxes = domQueryAll(Selectors.actions.checkBoxes);

        } catch (e) {
            if (config.debug) {
                Log.error(e.message);
            }
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
     *Get the translation row status icon.
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
        icon.setAttribute('title', config.statusstrings[status.replace('local_deepler/', '')]);
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
        const onTranslationFailed = (data) => {
            const error = data.error;
            const status = data.status;
            showModal(errordbtitle, `${status} ${error}`, 'Alert');
        };
        /**
         * Event listener for failed translations per item.
         * @param {object} data
         *
        const onItemNotTranslated = (data) => {
            errorMessageItem(data.key, findEditor(data.key), data.error);
        };
         */
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
     * Launch autotranslation.
     * ui.js + translation.js (split)
     */
    const doAutotranslate = () => {
        const keys = [];
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

        Translation.callTranslations(keys);
    };
    /**
     * Row visibility.
     *
     * @param {HTMLElement} row
     * @param {Boolean} checked
     * ui.js
     */
    const toggleRowVisibility = (row, checked) => {
        if (checked) {
            row.classList.remove("d-none");
        } else {
            row.classList.add("d-none");
        }
    };
        /**
         * Factory to display process' statuses for each item.
         *
         * @param {String} key
         * @param {Boolean} checked
         * ui.js
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
                    break;
                case Selectors.statuses.success :
                    break;
                case Selectors.statuses.saved :
                    Translation.initTemp(key);
                    break;
            }
        };
        /* Const refreshTempTranslation = (key)=>{
            Translation.initTempForKey(
                key, findEditor(key),
                domQuery(Selectors.sourcetexts.keys, key).getAttribute("data-sourcetext-raw"),
                domQuery(Selectors.sourcetexts.keys, key).getAttribute("data-filedtext-raw"),
                domQuery(Selectors.sourcetexts.sourcelangs, key).value
            );
        };*/
    /**
     * Shows/hides rows.
     * @param {string} selector
     * @param {boolean} selected
     * ui.js ok
     */
    const showRows = (selector, selected) => {
        const items = domQueryAll(selector);
        const allSelected = domQuery(Selectors.actions.selectAllBtn).checked;
        items.forEach((item) => {
            let k = item.getAttribute('data-row-id');
            toggleRowVisibility(item, selected);
            // When a row is toggled then we don't want it to be selected and sent from translation.
            try {
                domQuery(Selectors.editors.multiples.checkBoxesWithKey, k).checked = allSelected && selected;
                toggleStatus(k, false);
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
    const errorMessageItem = (key, error) => {
        Log.warn(`ui/errorMessageItem:440`);
        Log.warn(key);
        Log.warn(error);
        const editor = domQuery(Selectors.editors.multiples.editorsWithKey, key);
        editor.classList.add("local_deepler__error");
        // Display granular error messages.
        const indexOfSET = error.indexOf("SET");// Probably a text too long for the field if not -1.
        const msg = indexOfSET === -1 ? error : error.substring(0, indexOfSET);
        setIconStatus(key, Selectors.statuses.failed);
        showErrorMessageForEditor(key, msg);
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
    const successMessageItem = (key, savedText) => {
        Log.debug(`ui/successMessageItem:471 > savedText`);
        Log.debug(savedText);
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

            Log.debug(`ui/:488 > multilangPill`);
            Log.debug(multilangPill);
            Log.debug(`ui/:492 > multilangPill.classList.contains("disabled")`);
            Log.debug(multilangPill.classList.contains("disabled"));

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
     * ui.js
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
     * ui.js
     */
    const switchTarget = (e) => {
        let url = new URL(window.location.href);
        let searchParams = url.searchParams;
        searchParams.set("target_lang", e.target.value);
        window.location = url.toString();

        // Translation.setMainLangs('', e.target.value);
        // Events.emit(ON_TARGET_LANG_CHANGE, e.target.value);
        // DomQuery(Selectors.sourcetexts.targetlang).innerText = e.target.value;


    };
        /**
         * Event listner for changes of target language.
         * @param {string} e
         *
    const onTagrgetChanged = (e) => {
        Translation.setMainLangs('', e);
        saveAllBtn.disabled = true;
        selectAllBtn.disabled = !Translation.isTranslatable();
    };
         */
    /**
     * Event listener to switch source lang,
     * Hence reload the page and change the site main lang.
     * @param {Event} e
     * ui.js
     */
    const switchSource = (e) => {
        let url = new URL(window.location.href);
        let searchParams = url.searchParams;
        searchParams.set("lang", e.target.value);
        window.location = url.toString();
    };
    /**
     * Launch, display count of Words And Chars.
     *
     * ui.js
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
        let current = cwos + config.usage.character.count;
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
         * @todo MDL-0 get the editor from moodle db in the php.
         * ui.js
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
         * ui.js
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
     * ui.js
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
        config = cfg;
        // Utils.registerLoggers(cfg.debug);
        Log.info(cfg);
        getString('errordbtitle', 'local_deepler')
            .then((s)=>{
                errordbtitle = s;
            })
            .catch((e)=>{
                e('errordbtitle, could not get Moodle string!!!');
            }
        );
        registerUI();
        registerEventListeners();
        toggleAutotranslateButton();
        // OnTagrgetChanged(config.lang);
        saveAllBtn.disabled = true;
        selectAllBtn.disabled = !Translation.isTranslatable();
        checkboxes.forEach((node) => {
            node.disabled = selectAllBtn.disabled;
        });
        showRows(Selectors.statuses.updated, domQuery(Selectors.actions.showUpdated).checked);
        showRows(Selectors.statuses.needsupdate, domQuery(Selectors.actions.showNeedUpdate).checked);
    };
    return {
        init: init,
        setIconStatus: setIconStatus,
        findEditor: findEditor,
        findEditorByType: findEditorByType,
    };
});
