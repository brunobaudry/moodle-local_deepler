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
 * @module     'local_deepler'; // Full name of the plugin (used for diagnostics)./local/uiHelpers
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/modal',
    'core/log',
    'editor_tiny/loader',
    'editor_tiny/editor',
    './utils',
    './selectors',
    './customevents',
    './translation'
    ], (
        Modal,
        Log,
        TinyMCEinit,
        TinyMCE,
        Utils,
        Selectors,
        Events,
        Translation)=>{
    // Let preloader;
    let config = {};
    let format = new Intl.NumberFormat();
    let autotranslateButton = {};
    let saveAllBtn = {};
    let saveAllModal = {};
    let langstrings = {};
    let selectAllBtn = {};
    let removedIframes = [];
    let allDataFormatOne = [];
    let checkboxes = [];
    let glossaryDetailViewr;
    let checkBoxParentMap = new Map();
    const optionsForTiny = {
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
    const ON_STATUS_CHANGED = 'onStatusChanged';
    // Cached DOM elements
    const cachedSelectors = {
        wordCount: document.querySelector(Selectors.statuses.wordcount),
        charWithSpace: document.querySelector(Selectors.statuses.charNumWithSpace),
        charWOSpace: document.querySelector(Selectors.statuses.charNumWithOutSpace),
        deeplUseSpan: document.querySelector(Selectors.statuses.deeplUsage),
        deeplMaxSpan: document.querySelector(Selectors.statuses.deeplMax),
        deeplStatusContainer: document.querySelector(Selectors.statuses.deeplStatusContainer)
    };
    /**
     * Toggle iFrames in sourcetexts.
     */
    function doHideiframes() {
        const isChecked = Utils.domQuery(Selectors.actions.hideiframes);
        const allIframes = Utils.domQueryAll(Selectors.sourcetexts.iframes);
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
     * Get the editor container based on recieved current user's editor preference.
     *
     * @param {string} key Translation Key
     */
    const findEditor = (key) => {
        let e = Utils.domQuery(Selectors.editors.types.basic, key);
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
                ed = Utils.domQuery(Selectors.editors.types.atto, key);
                break;
            case "tiny":
                et = 'iframe';
                ed = findTinyInstanceByKey(key);
                break;
            case 'marklar':
            case "textarea" :
                ed = Utils.domQuery(Selectors.editors.types.other, key);
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
            if (v.attributes.name.value.indexOf(key) === 0) {
                editor = k.getBody();
                return false;
            }
            return true;
        });
        return editor;
    };
    /**
     * Hides an item's error message.
     *
     * @param {String} key
     */
    const hideErrorMessage = (key) => {
        let parent = Utils.domQuery(Selectors.editors.multiples.editorsWithKey, key);
        let alertChild = Utils.domQuery('.alert-danger', '', parent);
        if (alertChild) {
            parent.removeChild(alertChild);
        }
    };
    /**
     * Display an error message attached to the item's editor.
     * @param {String} key
     * @param {String} message
     */
    const showErrorMessageForEditor = (key, message) => {
        let parent = Utils.domQuery(Selectors.editors.multiples.editorsWithKey, key);
        const errorMsg = document.createElement('div');
        const indexOfSET = message.indexOf("Data too long");// Probably a text too long for the field if not -1.
        // Display granular error messages.
        if (indexOfSET > -1) {
            message = message.substring(0, message.indexOf('WHERE id=?'));
            message = message + ' ' + langstrings.uistrings.errortoolong;
        }
        errorMsg.id = 'local_deepler__errormsg';
        errorMsg.classList = ['alert alert-danger'];
        errorMsg.innerHTML = message;
        parent.appendChild(errorMsg);
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
    /**
     * Launch, display count of Words And Chars.
     */
    const countWordAndChar = () => {
        let wrdsc = 0,
            cws = 0,
            cwos = 0;

        // Cache checkboxes matching selector once, convert to array if needed.
        const checkedBoxes = Array.from(Utils.domQueryAll(Selectors.statuses.checkedCheckBoxes));

        // Aggregate counts in one loop.
        checkedBoxes.forEach(ckBox => {
            const key = ckBox.getAttribute("data-key");
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
     * Compile the needed counts for info.
     *
     * @param {string} key
     * @returns {{wordCount: *, charNumWithSpace: *, charNumWithOutSpace: *}}
     */
    const getCount = (key) => {
        const item = Utils.domQuery(Selectors.sourcetexts.keys, key);
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
     * Factory to display process' statuses for each item.
     *
     * @param {String} key
     * @param {Boolean} checked
     * @param {Boolean} translated
     * @todo MDL-0000 optimize
     */
    const toggleStatus = (key, checked, translated) => {
        const icon = Utils.domQuery(Selectors.actions.validatorBtn, key);
        const status = icon?.dataset.status;

        if (!status) {
            return;
        }

        switch (status) {
            case Selectors.statuses.wait:
                Events.emit(ON_STATUS_CHANGED, key);
                if (checked) {
                    setIconElementStatus(icon, Selectors.statuses.totranslate);
                }
                break;

            case Selectors.statuses.totranslate:
                if (checked && translated) {
                    setIconElementStatus(icon, Selectors.statuses.tosave, true);
                } else {
                    setIconElementStatus(icon, Selectors.statuses.wait);
                }
                break;

            case Selectors.statuses.tosave:
                if (!checked) {
                    setIconElementStatus(icon, Selectors.statuses.totranslate);
                }
                break;

            case Selectors.statuses.failed:
                if (checked) {
                    setIconElementStatus(icon, Selectors.statuses.totranslate);
                }
                break;

            case Selectors.statuses.success:
                break;

            case Selectors.statuses.saved:
                if (checked) {
                    setIconElementStatus(icon, Selectors.statuses.totranslate);
                }
                Events.emit(ON_STATUS_CHANGED, key);
                break;
        }
    };
    /**
     * Change translation process status icon.
     *
     * @param {element} icon
     * @param {string} status
     * @param {boolean} isBtn
     * @todo MDL-0000 optimize
     */
    const setIconElementStatus = (icon, status = Selectors.statuses.wait, isBtn = false) => {
        if (!icon) {
         return;
        }

        const classList = icon.classList;

        // Update classes only if needed
        if (isBtn) {
            if (!classList.contains('btn')) {
                classList.add('btn', 'btn-outline-secondary');
            }
            if (classList.contains('disable')) {
                classList.remove('disable');
            }
        } else {
            if (!classList.contains('disable')) {
                classList.add('disable');
            }
            if (classList.contains('btn')) {
                classList.remove('btn', 'btn-outline-secondary');
            }
        }

        // Update attributes only if changed
        if (icon.getAttribute('role') !== (isBtn ? 'button' : 'status')) {
            icon.setAttribute('role', isBtn ? 'button' : 'status');
        }

        if (icon.dataset.status !== status) {
            icon.setAttribute('data-status', status);
        }

        const title = langstrings.statusstrings[status.replace('local_deepler/', '')];
        if (icon.getAttribute('title') !== title) {
            icon.setAttribute('title', title);
        }
    };

   /* Const toggleStatus = (key, checked, translated) => {
        const status = Utils.domQuery(Selectors.actions.validatorBtn, key).dataset.status;
        switch (status) {
            case Selectors.statuses.wait :
                Events.emit(ON_STATUS_CHANGED, key);
                if (checked) {
                    setIconStatus(key, Selectors.statuses.totranslate);
                }
                break;
            case Selectors.statuses.totranslate :
                if (checked && translated) {
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
                Events.emit(ON_STATUS_CHANGED, key);
                break;
        }
    };*/

    const setIconStatus = (key, status = Selectors.statuses.wait, isBtn = false) => {
        let icon = Utils.domQuery(Selectors.actions.validatorBtn, key);
        setIconElementStatus(icon, status, isBtn);
       /* If (!isBtn) {
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
        icon.setAttribute('title', langstrings.statusstrings[status.replace('local_deepler/', '')]);*/
    };
    /**
     * Get the translation row status icon.
     *
     * @param {string} key
     * @returns {*}
     */
    const getIconStatus = (key)=> {
        return Utils.domQuery(Selectors.actions.validatorBtn, key).getAttribute('data-status');
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
    const launchTranslatingModal = ()=>{
        return launchModal(
            {
                title: langstrings.uistrings.translatemodaltitle,
                body: langstrings.uistrings.translatemodalbody,
            }
        );
    };
    const launchSaveAllModal = ()=>{
        launchModal(
            {
                title: langstrings.uistrings.saveallmodaltitle,
                body: langstrings.uistrings.saveallmodalbody,
            }
        ).then(r => Log.info('SaveAll Modal launched ' + r)).catch((reason)=>{
            Log.error(reason);
        });
    };
    const dbErrorPartialModal = (stringVar)=>{
        const body = langstrings.uistrings.errordbpartial.replace('{$a}', stringVar);
        dbErrorModal(body);
    };
    const dbErrorModal = (body, titleSuffix = '')=>{
        let title = langstrings.uistrings.errordbtitle;
        if (titleSuffix !== '') {
            title += titleSuffix;
        }
        presentDialogModal(title, body, 'alert');
    };
    const deeplErrorModal = (body)=>{
        presentDialogModal(langstrings.uistrings.deeplapiexception, body, 'Alert');
    };
    const presentDialogModal = (title, body, type = 'default') => {
        Modal.create({
            title: title,
            body: body,
            type: type,
            show: true,
            removeOnClose: true,
        });
    };

    const hideModal = ()=>{
        if (saveAllModal !== null && saveAllModal.isVisible) {
            saveAllModal.hide();
        }
    };
    const backToBase = () => {
        const offsetTop = Utils.domQuery(Selectors.config.langstrings).offsetTop;
        window.scrollTo({top: offsetTop - 5, behavior: 'smooth'});
    };
    const resizeEditors = ()=>{

        allDataFormatOne.forEach((editable)=>{
            const key = editable.id.replace('tiny_', '');
            const selector = `[data-sourcetext-key="${key}"]`;
            let parent = Utils.domQuery(selector);
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
     * Toggle Autotranslate Button
     */
    const toggleAutotranslateButton = () => {
        // Use Array.some() for early exit when a checked checkbox is found
        autotranslateButton.disabled = !Array.from(checkboxes).some(e => e.checked);
    };

    /**
     * Toggle checkboxes
     * @param {Boolean} checked
     */
    const toggleAllCheckboxes = (checked) => {
        const updates = [];
        // Prepare all updates without applying immediately.
        checkboxes.forEach(checkbox => {
            // Parent row.
            let parent;
            if (!checkBoxParentMap.has(checkbox)) {
                parent = Utils.getParentRow(checkbox, Selectors.sourcetexts.parentrow);
                checkBoxParentMap.set(checkbox, parent);
            } else {
                parent = checkBoxParentMap.get(checkbox);
            }
            // If the row is checked, verifiy that the parent is not hidden else do not select.
            const shouldCheck = checked ? !parent.classList.contains('d-none') : false;
            if (checkbox.checked !== shouldCheck) {
                updates.push({checkbox, shouldCheck});
            }
        });
        // Apply updates in the next animation frame, batching DOM writes.
        requestAnimationFrame(() => {
            updates.forEach(({checkbox, shouldCheck}) => {
                checkbox.checked = shouldCheck;
                const key = checkbox.getAttribute('data-key');
                toggleStatus(key, shouldCheck, Translation.translated(key));
            });
            toggleAutotranslateButton();
            countWordAndChar();
        });
    };
    const toggleGlossaryDetails = (glossaryId)=> {
        if (glossaryId !== '') {
            glossaryDetailViewr.style.display = 'block';
        } else {
            glossaryDetailViewr.style.display = 'none';
        }
    };
    /**
     * Wrap debounce for displaying rows.
     * @type {(function(...[*]): void)|*}
     */
    const debouncedShowRows = Utils.debounce(() => {
        showRows();
    }, 100);
    /**
     * Shows/hides rows.
     */
    const showRows = () => {
        // Map each selector to its corresponding checkbox checked state
        const selectorMap = {
            [Selectors.statuses.updated]: Utils.domQuery(Selectors.actions.showUpdated).checked,
            [Selectors.statuses.needsupdate]: Utils.domQuery(Selectors.actions.showNeedUpdate).checked,
            [Selectors.statuses.hidden]: Utils.domQuery(Selectors.actions.showHidden).checked
        };

        // Combine all selectors into one comma-separated string for batch querying.
        const mergedSelector = Object.keys(selectorMap).join(",");

        // Query all items matching any of the selectors once.
        const allItems = Utils.domQueryAll(mergedSelector);

        // Cache the global "select all" button checked state once.
        const allSelected = Utils.domQuery(Selectors.actions.selectAllBtn).checked;

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
                const childs = Utils.domQueryAll(Selectors.statuses.hiddenForStudentRows, '', item);
                childs.forEach(child => {
                    const childId = child.getAttribute('data-row-id');
                    toggleChildCheckBoxSelection(childId, shouldCheck);
                });
            } else {
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
        const single = Utils.domQuery(Selectors.editors.multiples.checkBoxesWithKey, key);
        single.checked = shouldBeChecked;
        toggleStatus(key, false, Translation.translated(key));
    };
/*    Const togglePreloader = () => {
        if (preloader?.classList.contains('show') || preloader?.classList.contains('d-block')) {
            preloader.classList.remove('show', 'd-block');
            preloader.classList.add('d-none');
        } else {
            preloader.classList.remove('d-none');
            preloader.classList.add('show', 'd-block');
        }
    };*/

    const wrapTinyOnTarget = (element)=>{
        const status = getIconStatus(element.id.replace('tiny_', ''));
        if (status === Selectors.statuses.tosave) {
            // eslint-disable-next-line promise/catch-or-return
            TinyMCEinit.getTinyMCE().then(
                // eslint-disable-next-line promise/always-return
                ()=>{
                    // eslint-disable-next-line promise/no-nesting
                    TinyMCE.setupForTarget(element, optionsForTiny)
                        // eslint-disable-next-line promise/always-return
                        .then(()=>{
                            Log.info('tiny loaded for ' + element.id);
                        })
                        .catch((r)=>{
                            Log.error(r);
                        });
                }
            );
        }
    };
    const disableSaveButton = ()=>{
        saveAllBtn.disabled = true;
    };
    const enableSaveButton = ()=>{
        saveAllBtn.disabled = false;
    };
    const disableTranslateButton = ()=>{
        autotranslateButton.disabled = true;
    };
    const enableTranslateButton = ()=>{
        autotranslateButton.disabled = true;
    };
    const registerUI = () => {
        if (!glossaryDetailViewr && document.querySelector(Selectors.glossary.entriesviewerPage)) {
            glossaryDetailViewr = document.querySelector(Selectors.glossary.entriesviewerPage);
        }
        autotranslateButton = Utils.domQuery(Selectors.actions.autoTranslateBtn);
        checkboxes = Utils.domQueryAll(Selectors.actions.checkBoxes);
        allDataFormatOne = Utils.domQueryAll(Selectors.editors.targetarea);
        selectAllBtn = Utils.domQuery(Selectors.actions.selectAllBtn);
        saveAllBtn = Utils.domQuery(Selectors.actions.saveAll);
        langstrings = JSON.parse(Utils.domQuery(Selectors.config.langstrings).getAttribute('data-langstrings'));
    };
    const init = (cfg) => {
        // Preloader = pr;
        config = cfg;
        registerUI();
        // Make the main UI adjustments
        resizeEditors();
        doHideiframes();
        toggleAutotranslateButton();
        // SaveAllBtn.disabled = true;
        disableSaveButton();
        selectAllBtn.disabled = config.targetlang === '';
        checkboxes.forEach((node) => {
            node.disabled = selectAllBtn.disabled;
        });
        debouncedShowRows();
    };
    return {
        ON_STATUS_CHANGED: ON_STATUS_CHANGED,
        backToBase: backToBase,
        countWordAndChar: countWordAndChar,
        dbErrorModal: dbErrorModal,
        dbErrorPartialModal: dbErrorPartialModal,
        disableSaveButton: disableSaveButton,
        disableTranslateButton: disableTranslateButton,
        enableSaveButton: enableSaveButton,
        enableTranslateButton: enableTranslateButton,
        debouncedShowRows: debouncedShowRows,
        deeplErrorModal: deeplErrorModal,
        doHideiframes: doHideiframes,
        findEditor: findEditor,
        getIconStatus: getIconStatus,
        hideModal: hideModal,
        hideErrorMessage: hideErrorMessage,
        init: init,
        launchModal: launchModal,
        launchSaveAllModal: launchSaveAllModal,
        launchTranslatingModal: launchTranslatingModal,
        presentDialogModal: presentDialogModal,
        setIconStatus: setIconStatus,
        showEntriesModal: showEntriesModal,
        showErrorMessageForEditor: showErrorMessageForEditor,
        toggleAllCheckboxes: toggleAllCheckboxes,
        toggleAutotranslateButton: toggleAutotranslateButton,
        toggleGlossaryDetails: toggleGlossaryDetails,
        toggleStatus: toggleStatus,
        wrapTinyOnTarget: wrapTinyOnTarget
    };
});
