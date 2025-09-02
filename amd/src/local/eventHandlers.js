define([
    'core/log',
    'core/modal',
    './selectors',
    './translation',
    './utils',
    './customevents',
    './api',
    'editor_tiny/loader',
    'editor_tiny/editor'
], (Log, Modal, Selectors, Translation, Utils, Events, Api, TinyMCEinit, TinyMCE,) =>{
    let config;
    let url;
    let searchParams;
    /**
     * Init function.
     * @param cfg
     */
    const registerEventListeners = (cfg) => {
        config = cfg;
        url = new URL(window.location.href);
        searchParams = url.searchParams;
        document.addEventListener('change', handleChangeEvent);
        document.addEventListener('click', handleClickEvent);
        document.addEventListener('focusin', handleFocusEvent);

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
        Events.on(Api.GLOSSARY_ENTRIES_SUCCESS, showEntriesModal);
        Events.on(Api.GLOSSARY_ENTRIES_FAILED, (e)=>Log.error(e));
    };

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

    const handleFocusEvent = (e) => {

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

    const onToggleMultilang = (e) => {
        const keyid = e.getAttribute('aria-controls');
        const key = Utils.keyidToKey(keyid);
        if (key === null) {
            Log.error(`KEY ${keyid} BAD FORMAT should be TABLE-ID-FIELD-CMID`);
        } else {
            const source = domQuery(Selectors.sourcetexts.keys, key);
            const multilang = domQuery(Selectors.sourcetexts.multilangs, keyid);
            source.classList.toggle("show");
            multilang.classList.toggle("show");
        }
    };

    const onItemChecked = (e) => {
        if (e.target.getAttribute('data-action') === "local_deepler/checkbox") {
            toggleStatus(e.target.getAttribute('data-key'), e.target.checked);
            countWordAndChar();
        }
    };
    const switchTarget = (e) => {
       /* Const url = new URL(window.location.href);
        const searchParams = url.searchParams;*/
        // Pass the target lang in the url and refresh, not forgetting to remove the rephrase prefix indicator.
        searchParams.set("target_lang", e.target.value.replace(config.rephrasesymbol, '').trim());
        window.location = url.toString();
    };

    const switchSection = (e) => {
/*        Const url = new URL(window.location.href);
        const searchParams = url.searchParams;*/
        // Pass the target lang in the url and refresh, not forgetting to remove the rephrase prefix indicator.
        searchParams.set("section_id", e.target.value.trim());
        if (searchParams.has("activity_id")) {
            searchParams.delete("activity_id");
        }
        window.location = url.toString();
    };

    const switchModules = (e) => {
/*        Const url = new URL(window.location.href);
        const searchParams = url.searchParams;*/
        // Pass the target lang in the url and refresh, not forgetting to remove the rephrase prefix indicator.
        searchParams.set("activity_id", e.target.value.trim());
        window.location = url.toString();
    };


    const onGlossaryDbAllfailed = (obj) => {
        Log.info('onGlossaryDbAllfailed');
        Log.error(obj);
    };

    const onGlossaryDbfailed = (obj) => {
        Log.info('onGlossaryDbfailed');
        Log.error(obj);
    };

    const onGlossaryDbSuccess = (obj) => {
        Log.info('onGlossaryDbSuccess');
        Log.info(obj);
    };

    const showEntriesModal = (ajaxResponse) => {
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

    const onTranslationFailed = (error) => {
        let s = langstrings.uistrings.deeplapiexception;
        onTranslationDone();
        showModal(s, error, 'Alert');
    };

    const onTranslationDone = () => {
        // Implementation from ui_deepler.js
    };

    const onItemTranslated = (key) => {
        // Implementation from ui_deepler.js
    };

    const onSuccessMessageItem = (key, savedText) => {
        // Implementation from ui_deepler.js
    };

    const onErrorMessageItem = (key, error) => {
        // Implementation from ui_deepler.js
    };

    const onDBFailed = (error, status) => {
        // Implementation from ui_deepler.js
    };

    const onDbSavedSuccess = (errors) => {
        // Implementation from ui_deepler.js
    };

    return {
        init: registerEventListeners
    };
});
