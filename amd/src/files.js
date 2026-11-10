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
 * Controller for Deepler file translation UI.
 *
 * @module     local_deepler/files
 * @copyright  2026 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'core/str',
    'core/notification',
    'core/modal_save_cancel',
    'core/modal_events',
    'core/templates',
    'local_deepler/local/api',
    'local_deepler/local/selectors',
    'local_deepler/local/utils'
], (
    Str,
    Notification,
    ModalSaveCancel,
    ModalEvents,
    Templates,
    Api,
    Selectors,
    Utils
) => {

    let appConfig = null;
    const strings = {};
    const customSettingsMap = {};

    /**
     * Load required language strings.
     *
     * @returns {Promise}
     */
    const loadStrings = () => {
        const stringRequests = [
            {key: 'files:queued', component: 'local_deepler'},
            {key: 'files:uploading', component: 'local_deepler'},
            {key: 'files:translating', component: 'local_deepler'},
            {key: 'files:downloading', component: 'local_deepler'},
            {key: 'files:duplicating', component: 'local_deepler'},
            {key: 'files:done', component: 'local_deepler'},
            {key: 'files:error', component: 'local_deepler'},
            {key: 'files:openactivity', component: 'local_deepler'},
            {key: 'files:translations', component: 'local_deepler', param: 0},
            {key: 'files:notranslations', component: 'local_deepler'},
            {key: 'files:settingsmodal_title', component: 'local_deepler'},
            {key: 'save', component: 'core'},
            {key: 'reset', component: 'core'},
            {key: 'closebuttontitle', component: 'core'},
            {key: 'glossary:notselected', component: 'local_deepler'},
            {key: 'files:justnow', component: 'local_deepler'},
            {key: 'files:billedchars', component: 'local_deepler'},
            {key: 'seesetting', component: 'local_deepler'},
            {key: 'resettodefaults', component: 'local_deepler'}
        ];

        return Str.get_strings(stringRequests).then((results) => {
            strings.queued = results[0];
            strings.uploading = results[1];
            strings.translating = results[2];
            strings.downloading = results[3];
            strings.duplicating = results[4];
            strings.done = results[5];
            strings.error = results[6];
            strings.openactivity = results[7];
            strings.translations = results[8];
            strings.notranslations = results[9];
            strings.settingsmodalTitle = results[10];
            strings.save = results[11];
            strings.reset = results[12];
            strings.close = results[13];
            strings.glossarynotselected = results[14];
            strings.justnow = results[15];
            strings.billedchars = results[16];
            strings.seesetting = results[17];
            strings.resettodefaults = results[18];
            return strings;
        });
    };

    /**
     * Filter glossaries for a given source and target language pair.
     *
     * @param {string} sourceLang
     * @param {string} targetLang
     * @returns {Array}
     */
    const getFilteredGlossaries = (sourceLang, targetLang) => {
        if (!appConfig.glossaries || !Array.isArray(appConfig.glossaries)) {
            return [];
        }
        const s = (sourceLang || '').toLowerCase();
        const t = (targetLang || '').toLowerCase();
        return appConfig.glossaries.filter((g) => {
            return g.sourcelang.toLowerCase() === s && g.targetlang.toLowerCase() === t;
        });
    };

    /**
     * Synchronize disabled options between a source language select and target language select.
     * When a language is selected as source, its counterpart is disabled (grayed out) in target, and vice versa.
     *
     * @param {HTMLElement|string} sourceSelect
     * @param {HTMLElement|string} targetSelect
     */
    const syncCounterpartDisabledOptions = (sourceSelect, targetSelect) => {
        const sourceEl = typeof sourceSelect === 'string' ? Utils.domQuery(sourceSelect) : sourceSelect;
        const targetEl = typeof targetSelect === 'string' ? Utils.domQuery(targetSelect) : targetSelect;
        if (!sourceEl || !targetEl) {
            return;
        }

        // 1. In target select: disable options matching source language root.
        const sourceVal = sourceEl.value || '';
        const sourceRoot = Utils.removeRegionalVariant(sourceVal);

        targetEl.querySelectorAll('option').forEach((opt) => {
            const optVal = opt.value;
            const optRoot = Utils.removeRegionalVariant(optVal);
            opt.disabled = Boolean(optRoot && optRoot === sourceRoot);
        });

        // If currently selected target option became disabled, select first available enabled option.
        const selectedTargetOpt = targetEl.querySelector('option:checked');
        if (selectedTargetOpt && selectedTargetOpt.disabled) {
            const firstEnabled = targetEl.querySelector('option:not(:disabled)');
            if (firstEnabled) {
                targetEl.value = firstEnabled.value;
            }
        }

        // 2. In source select: disable options matching target language root.
        const targetVal = targetEl.value || '';
        const targetRoot = Utils.removeRegionalVariant(targetVal);

        sourceEl.querySelectorAll('option').forEach((opt) => {
            const optVal = opt.value;
            const optRoot = Utils.removeRegionalVariant(optVal);
            opt.disabled = Boolean(optRoot && optRoot === targetRoot);
        });

        // If currently selected source option became disabled, select first available enabled option.
        const selectedSourceOpt = sourceEl.querySelector('option:checked');
        if (selectedSourceOpt && selectedSourceOpt.disabled) {
            const firstEnabled = sourceEl.querySelector('option:not(:disabled)');
            if (firstEnabled) {
                sourceEl.value = firstEnabled.value;
            }
        }
    };

    /**
     * Synchronize counterpart language options for all file rows and global dropdowns.
     */
    const syncAllCounterpartOptions = () => {
        // Sync global selectors.
        syncCounterpartDisabledOptions(Selectors.files.globalSourceLang, Selectors.files.globalTargetLang);

        // Sync each row selector pair.
        Utils.domQueryAll('tr.file-row').forEach((row) => {
            const sourceSelect = Utils.domQuery(Selectors.files.fileSourceSelect, '', row);
            const targetSelect = Utils.domQuery(Selectors.files.targetSelect, '', row);
            if (sourceSelect && targetSelect) {
                syncCounterpartDisabledOptions(sourceSelect, targetSelect);
            }
        });
    };

    /**
     * Update global glossary selector options based on current global source/target language selection.
     */
    const updateGlobalGlossarySelector = () => {
        const sourceSelect = Utils.domQuery(Selectors.files.globalSourceLang);
        const targetSelect = Utils.domQuery(Selectors.files.globalTargetLang);
        const sourceLang = sourceSelect ? sourceSelect.value || '' : '';
        const targetLang = targetSelect ? targetSelect.value || '' : '';
        const matching = getFilteredGlossaries(sourceLang, targetLang);

        const glossarySelect = Utils.domQuery('#local_deepler__additionalsettings [data-id="local_deepler/glossary_id"]') ||
            Utils.domQuery(Selectors.files.globalGlossary);

        if (glossarySelect) {
            const currentVal = glossarySelect.value;
            glossarySelect.innerHTML = '';

            const defaultOpt = document.createElement('option');
            defaultOpt.value = '';
            defaultOpt.textContent = strings.glossarynotselected || 'No Glossary selected';
            glossarySelect.appendChild(defaultOpt);

            matching.forEach((g) => {
                const opt = document.createElement('option');
                opt.value = g.id;
                opt.textContent = g.name;
                if (g.id === currentVal) {
                    opt.selected = true;
                }
                glossarySelect.appendChild(opt);
            });
        }
    };

    /**
     * Update the Translate button disabled state depending on selections.
     */
    const updateButtonState = () => {
        const checkedCount = Utils.domQueryAll(Selectors.files.fileCheckboxChecked).length;
        const btn = Utils.domQuery(Selectors.files.translateSelectedBtn);
        if (btn) {
            if (checkedCount > 0) {
                btn.removeAttribute('disabled');
            } else {
                btn.setAttribute('disabled', 'disabled');
            }
        }
    };

    /**
     * Set badge status and text for a given file.
     *
     * @param {number} fileId
     * @param {string} statusText
     * @param {string} badgeClass
     * @param {string} detailText
     */
    const setFileStatus = (fileId, statusText, badgeClass, detailText = '') => {
        const badge = Utils.domQuery(`[data-status-for="${fileId}"]`);
        if (badge) {
            badge.classList.remove(
                'badge-secondary',
                'badge-info',
                'badge-warning',
                'badge-success',
                'badge-danger',
                'badge-primary'
            );
            badge.classList.add(badgeClass);
            badge.textContent = statusText;
        }

        const detail = Utils.domQuery(`[data-detail-for="${fileId}"]`);
        if (detail) {
            if (detailText) {
                detail.textContent = detailText;
                detail.style.display = '';
            } else {
                detail.textContent = '';
                detail.style.display = 'none';
            }
        }
    };

    /**
     * Sleep helper for polling delay.
     *
     * @param {number} ms
     * @returns {Promise}
     */
    const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

    /**
     * Poll job status until complete or failed.
     *
     * @param {number} jobId
     * @param {number} fileId
     * @returns {Promise<object>}
     */
    const pollStatus = async(jobId, fileId) => {
        let attempts = 0;
        const maxAttempts = 120; // 10 minutes max with 5s interval.

        while (attempts < maxAttempts) {
            await sleep(5000);
            attempts++;

            const res = await Api.getDocumentStatus(jobId, appConfig.version);
            if (res.status === 'done') {
                return res;
            }
            if (res.status === 'error') {
                throw new Error(res.error || strings.error);
            }

            if (res.secondsremaining > 0) {
                setFileStatus(fileId, strings.translating, 'badge-warning', `~${res.secondsremaining}s`);
            } else {
                setFileStatus(fileId, strings.translating, 'badge-warning');
            }
        }

        throw new Error('Timeout waiting for translation.');
    };

    /**
     * Add a completed translation to the file's accordion sub-table.
     *
     * @param {number} fileId
     * @param {string} targetLang
     * @param {object} finalRes
     */
    const addTranslationToAccordion = (fileId, targetLang, finalRes) => {
        const translationsRow = Utils.domQuery(`#file_translations_${fileId}`);
        if (!translationsRow) {
            return;
        }
        const list = Utils.domQuery(`[data-list-for="${fileId}"]`, '', translationsRow);
        if (list) {
            const noTranslationsMsg = Utils.domQuery('.no-translations-msg', '', list);
            if (noTranslationsMsg) {
                noTranslationsMsg.remove();
            }
        }

        let billedStr = '';
        if (finalRes.billedcharacters) {
            billedStr = (strings.billedchars || 'Billed chars: {$a}').replace('{$a}', finalRes.billedcharacters);
        }

        const context = {
            targetlang: targetLang.toUpperCase(),
            filename: finalRes.filename || '',
            timecreated: strings.justnow || 'Just now',
            newcmurl: finalRes.newcmurl || '',
            statusbadge: 'badge-success',
            statusstr: strings.done,
            // eslint-disable-next-line camelcase
            str_billedchars: billedStr
        };

        if (list) {
            Templates.render('local_deepler/files_translation_item', context)
                .then((html, js) => {
                    Templates.prependNodeContents(list, html, js);
                    return null;
                })
                .catch(Notification.exception);
        }

        // Update translation count badge and toggle button.
        const row = Utils.domQuery(`tr.file-row[data-fileid="${fileId}"]`);
        if (row) {
            const toggleBtn = Utils.domQuery('.toggle-translations-btn', '', row);
            if (toggleBtn) {
                toggleBtn.classList.remove('disabled', 'text-muted');
                const toggleIcon = Utils.domQuery('.toggle-icon', '', toggleBtn);
                if (toggleIcon) {
                    toggleIcon.classList.remove('fa-chevron-right');
                    toggleIcon.classList.add('fa-chevron-down');
                }
            }
        }

        // Automatically show the accordion row so user sees result.
        translationsRow.classList.add('show', 'collapse');
    };

    /**
     * Process a single file translation task.
     *
     * @param {object} item
     * @returns {Promise}
     */
    const processFile = async(item) => {
        const fileId = item.fileId;
        const sourceLang = item.sourceLang || '';
        const targetLang = item.targetLang;
        const options = item.options || {};

        try {
            // 1. Upload
            setFileStatus(fileId, strings.uploading, 'badge-info');
            const uploadRes = await Api.uploadDocument(fileId, sourceLang, targetLang, options, appConfig.version);
            if (uploadRes.status === 'error' || !uploadRes.jobid) {
                throw new Error(uploadRes.error || strings.error);
            }

            const jobId = uploadRes.jobid;

            // 2. Poll status
            setFileStatus(fileId, strings.translating, 'badge-warning');
            await pollStatus(jobId, fileId);

            // 3. Finalize
            setFileStatus(fileId, strings.duplicating, 'badge-primary');
            const finalRes = await Api.finalizeDocument(jobId, appConfig.version);
            if (finalRes.status === 'error') {
                throw new Error(finalRes.error || strings.error);
            }

            // 4. Done
            setFileStatus(fileId, strings.done, 'badge-success');

            // Render link to duplicated cm if available
            if (finalRes.newcmurl) {
                const statusCell = Utils.domQuery(`td.status-cell[data-fileid="${fileId}"]`);
                if (statusCell && !Utils.domQuery('.open-duplicate-link', '', statusCell)) {
                    Templates.render('local_deepler/files_open_duplicate_link', {newcmurl: finalRes.newcmurl})
                        .then((html, js) => {
                            Templates.appendNodeContents(statusCell, html, js);
                            return null;
                        })
                        .catch(Notification.exception);
                }
            }

            // Add translation item to accordion
            addTranslationToAccordion(fileId, targetLang, finalRes);

            if (finalRes.warning) {
                Notification.addNotification({
                    message: finalRes.warning,
                    type: 'warning'
                });
            }

            // Uncheck the completed row
            const checkbox = Utils.domQuery(`input[data-action="select-file"][data-fileid="${fileId}"]`);
            if (checkbox) {
                checkbox.checked = false;
            }

        } catch (err) {
            setFileStatus(fileId, strings.error, 'badge-danger', err.message || strings.error);
        }
    };

    /**
     * Process all selected files sequentially.
     */
    const processSelectedFiles = async() => {
        const checkedBoxes = Utils.domQueryAll(Selectors.files.fileCheckboxChecked);
        if (checkedBoxes.length === 0) {
            return;
        }

        const globalFormalityEl = Utils.domQuery('#local_deepler__additionalsettings [data-id="local_deepler/formality"]') ||
            Utils.domQuery(Selectors.files.globalFormality) ||
            Utils.domQuery('input[name="global_formality"]:checked');
        const globalFormality = globalFormalityEl ? globalFormalityEl.value || '' : '';

        const globalGlossaryEl = Utils.domQuery('#local_deepler__additionalsettings [data-id="local_deepler/glossary_id"]') ||
            Utils.domQuery(Selectors.files.globalGlossary);
        const globalGlossary = globalGlossaryEl ? globalGlossaryEl.value || '' : '';

        const globalMinificationEl = Utils.domQuery('#local_deepler__additionalsettings [data-id="local_deepler/minification"]') ||
            Utils.domQuery(Selectors.files.globalMinification);
        const globalMinification = globalMinificationEl ? globalMinificationEl.checked : false;

        const items = [];
        checkedBoxes.forEach((el) => {
            const fileId = parseInt(el.dataset.fileid, 10);
            const row = el.closest('tr');
            const sourceSelect = Utils.domQuery(Selectors.files.fileSourceSelect, '', row);
            const targetSelect = Utils.domQuery(Selectors.files.targetSelect, '', row);
            const sourceLang = sourceSelect ? sourceSelect.value || '' : '';
            const targetLang = targetSelect ? targetSelect.value || '' : '';

            const custom = customSettingsMap[fileId] || {};
            const options = {
                formality: custom.formality !== undefined && custom.formality !== '' ? custom.formality : globalFormality,
                // eslint-disable-next-line camelcase
                glossary_id: custom.glossary_id !== undefined && custom.glossary_id !== '' ? custom.glossary_id : globalGlossary,
                // eslint-disable-next-line camelcase
                enable_document_minification: custom.enable_document_minification !== undefined ?
                    custom.enable_document_minification : globalMinification
            };

            items.push({
                fileId: fileId,
                sourceLang: sourceLang,
                targetLang: targetLang,
                options: options
            });
        });

        // Disable translate button during processing
        const translateBtn = Utils.domQuery(Selectors.files.translateSelectedBtn);
        if (translateBtn) {
            translateBtn.setAttribute('disabled', 'disabled');
        }

        for (const item of items) {
            await processFile(item);
        }

        updateButtonState();
    };

    /**
     * Open item-level settings modal for a specific file.
     *
     * @param {number} fileId
     * @param {string} fileName
     */
    const openItemSettingsModal = (fileId, fileName) => {
        const row = Utils.domQuery(`tr.file-row[data-fileid="${fileId}"]`);
        const sourceSelect = Utils.domQuery(Selectors.files.fileSourceSelect, '', row);
        const targetSelect = Utils.domQuery(Selectors.files.targetSelect, '', row);
        const sourceLang = sourceSelect ? sourceSelect.value || '' : '';
        const targetLang = targetSelect ? targetSelect.value || '' : '';
        const matchingGlossaries = getFilteredGlossaries(sourceLang, targetLang);

        const globalFormalityEl = Utils.domQuery('#local_deepler__additionalsettings [data-id="local_deepler/formality"]') ||
            Utils.domQuery(Selectors.files.globalFormality);
        const globalFormality = globalFormalityEl ? globalFormalityEl.value || 'default' : 'default';

        const globalGlossaryEl = Utils.domQuery('#local_deepler__additionalsettings [data-id="local_deepler/glossary_id"]') ||
            Utils.domQuery(Selectors.files.globalGlossary);
        const globalGlossary = globalGlossaryEl ? globalGlossaryEl.value || '' : '';

        const globalMinificationEl = Utils.domQuery('#local_deepler__additionalsettings [data-id="local_deepler/minification"]') ||
            Utils.domQuery(Selectors.files.globalMinification);
        const globalMinification = globalMinificationEl ? globalMinificationEl.checked : false;

        const currentCustom = customSettingsMap[fileId] || {};
        const effectiveFormality = currentCustom.formality !== undefined && currentCustom.formality !== '' ?
            currentCustom.formality : globalFormality;
        const effectiveGlossary = currentCustom.glossary_id !== undefined && currentCustom.glossary_id !== '' ?
            currentCustom.glossary_id : globalGlossary;
        const effectiveMinification = currentCustom.enable_document_minification !== undefined ?
            currentCustom.enable_document_minification : globalMinification;

        const context = {
            fileid: fileId,
            filename: fileName,
            prefix: 'modal_',
            // eslint-disable-next-line camelcase
            formality_default: effectiveFormality === 'default',
            // eslint-disable-next-line camelcase
            formality_prefer_more: effectiveFormality === 'prefer_more',
            // eslint-disable-next-line camelcase
            formality_prefer_less: effectiveFormality === 'prefer_less',
            // eslint-disable-next-line camelcase
            formality_more: effectiveFormality === 'more',
            // eslint-disable-next-line camelcase
            formality_less: effectiveFormality === 'less',
            minification: effectiveMinification,
            glossaries: matchingGlossaries.map((g) => ({
                id: g.id,
                name: g.name,
                selected: (effectiveGlossary === g.id)
            }))
        };

        ModalSaveCancel.create({
            title: `${strings.settingsmodalTitle} - ${fileName}`,
            body: Templates.render('local_deepler/files_settings_modal', context),
        }).then((modal) => {
            const footer = modal.getFooter();
            let footerEl;
            if (footer instanceof HTMLElement) {
                footerEl = footer;
            } else {
                footerEl = footer && footer[0] ? footer[0] : null;
            }
            if (footerEl) {
                const resetBtn = document.createElement('button');
                resetBtn.type = 'button';
                resetBtn.className = 'btn btn-outline-danger me-auto';
                resetBtn.setAttribute('data-action', 'reset');
                resetBtn.textContent = strings.resettodefaults || 'Reset to defaults';
                resetBtn.addEventListener('click', () => {
                    delete customSettingsMap[fileId];
                    const indicator = Utils.domQuery(`[data-indicator-fileid="${fileId}"]`);
                    if (indicator) {
                        indicator.classList.add('d-none');
                    }
                    modal.destroy();
                });
                footerEl.prepend(resetBtn);
            }

            modal.show();

            const root = modal.getRoot();
            let rootEl;
            if (root instanceof HTMLElement) {
                rootEl = root;
            } else {
                rootEl = root && root[0] ? root[0] : null;
            }
            if (rootEl) {
                const formalityInput = Utils.domQuery(
                    `[data-id="local_deepler/formality"], #modal_formality_${fileId}`,
                    '',
                    rootEl
                );
                if (formalityInput) {
                    formalityInput.value = effectiveFormality;
                }
                const glossaryInput = Utils.domQuery(
                    `[data-id="local_deepler/glossary_id"], #modal_glossary_${fileId}`,
                    '',
                    rootEl
                );
                if (glossaryInput) {
                    glossaryInput.value = effectiveGlossary;
                }
                const minificationInput = Utils.domQuery(
                    `[data-id="local_deepler/minification"], #modal_minification_${fileId}`,
                    '',
                    rootEl
                );
                if (minificationInput) {
                    minificationInput.checked = effectiveMinification;
                }
            }

            modal.setSaveButtonText(strings.save);

            root.on(ModalEvents.save, () => {
                let formalityVal = 'default';
                let glossaryVal = '';
                let minificationVal = false;

                if (rootEl) {
                    const formalityInput = Utils.domQuery(`[data-id="local_deepler/formality"]`, '', rootEl) ||
                        Utils.domQuery(`#modal_formality_${fileId}`, '', rootEl);
                    if (formalityInput) {
                        formalityVal = formalityInput.value || 'default';
                    }

                    const glossaryInput = Utils.domQuery(`[data-id="local_deepler/glossary_id"]`, '', rootEl) ||
                        Utils.domQuery(`#modal_glossary_${fileId}`, '', rootEl);
                    if (glossaryInput) {
                        glossaryVal = glossaryInput.value || '';
                    }

                    const minificationInput = Utils.domQuery(`[data-id="local_deepler/minification"]`, '', rootEl) ||
                        Utils.domQuery(`#modal_minification_${fileId}`, '', rootEl);
                    if (minificationInput) {
                        minificationVal = minificationInput.checked;
                    }
                }

                customSettingsMap[fileId] = {
                    formality: formalityVal,
                    // eslint-disable-next-line camelcase
                    glossary_id: glossaryVal,
                    // eslint-disable-next-line camelcase
                    enable_document_minification: minificationVal
                };
                const indicator = Utils.domQuery(`[data-indicator-fileid="${fileId}"]`);
                if (indicator) {
                    indicator.classList.remove('d-none');
                }
            });

            root.on(ModalEvents.hidden, () => {
                modal.destroy();
            });
            return modal;
        }).catch(Notification.exception);
    };

    /**
     * Open global settings modal.
     */
    const openGlobalSettingsModal = () => {
        const settingsContainer = Utils.domQuery('#local_deepler__additionalsettings');
        if (!settingsContainer) {
            return;
        }

        const placeholder = document.createElement('div');
        placeholder.id = 'local_deepler__files_additionalsettings_placeholder';
        settingsContainer.parentNode.insertBefore(placeholder, settingsContainer);

        ModalSaveCancel.create({
            title: strings.seesetting || strings.settingsmodalTitle || 'Advanced settings',
            body: '',
            large: true,
        }).then((modal) => {
            modal.show();

            const body = modal.getBody();
            let bodyElement;
            if (body instanceof HTMLElement) {
                bodyElement = body;
            } else {
                bodyElement = body && body[0] ? body[0] : null;
            }
            if (bodyElement) {
                settingsContainer.classList.remove('d-none');
                bodyElement.appendChild(settingsContainer);
            }

            modal.setSaveButtonText(strings.save);

            const root = modal.getRoot();
            root.on(ModalEvents.save, () => {
                modal.hide();
            });

            root.on(ModalEvents.hidden, () => {
                settingsContainer.classList.add('d-none');
                if (placeholder.parentNode) {
                    placeholder.parentNode.insertBefore(settingsContainer, placeholder);
                    placeholder.remove();
                }
                modal.destroy();
            });

            return modal;
        }).catch(Notification.exception);
    };

    /**
     * Register DOM event listeners.
     */
    const registerEventListeners = () => {
        // Delegated click handler
        document.addEventListener('click', (e) => {
            // 0. Global settings button
            const globalSettingsBtn = e.target.closest('#local_deepler__settings, [data-action="global-settings"]');
            if (globalSettingsBtn) {
                e.preventDefault();
                openGlobalSettingsModal();
                return;
            }

            // 5. Translate selected button
            const translateBtn = e.target.closest(Selectors.files.translateSelectedBtn);
            if (translateBtn) {
                processSelectedFiles();
                return;
            }

            // 6. Item settings modal button
            const rowSettingsBtn = e.target.closest(Selectors.files.rowSettingsBtn);
            if (rowSettingsBtn) {
                const fileId = parseInt(rowSettingsBtn.dataset.fileid, 10);
                const fileName = rowSettingsBtn.dataset.filename || '';
                openItemSettingsModal(fileId, fileName);
                return;
            }

            // 7. Toggle translations accordion icon rotation
            const toggleTranslationsBtn = e.target.closest(Selectors.files.toggleTranslationsBtn);
            if (toggleTranslationsBtn) {
                const icon = Utils.domQuery('.toggle-icon', '', toggleTranslationsBtn);
                const targetId = toggleTranslationsBtn.dataset.target || toggleTranslationsBtn.dataset.bsTarget;
                const targetEl = targetId ? Utils.domQuery(targetId) : null;
                setTimeout(() => {
                    if (targetEl && targetEl.classList.contains('show')) {
                        if (icon) {
                            icon.classList.remove('fa-chevron-right');
                            icon.classList.add('fa-chevron-down');
                        }
                    } else if (icon) {
                        icon.classList.remove('fa-chevron-down');
                        icon.classList.add('fa-chevron-right');
                    }
                }, 350);
            }
        });

        // Delegated change handler
        document.addEventListener('change', (e) => {
            // 1. Global Source Language change
            if (e.target.matches(Selectors.files.globalSourceLang)) {
                const val = e.target.value;
                Utils.domQueryAll(Selectors.files.fileSourceSelect).forEach((sel) => {
                    sel.value = val;
                });
                syncAllCounterpartOptions();
                updateGlobalGlossarySelector();
                return;
            }

            // 2. Global Target Language change
            if (e.target.matches(Selectors.files.globalTargetLang)) {
                const val = e.target.value;
                Utils.domQueryAll(Selectors.files.targetSelect).forEach((sel) => {
                    sel.value = val;
                });
                syncAllCounterpartOptions();
                updateGlobalGlossarySelector();
                return;
            }

            // 2b. Row Source Language change
            if (e.target.matches(Selectors.files.fileSourceSelect)) {
                const row = e.target.closest('tr.file-row');
                const targetSelect = row ? Utils.domQuery(Selectors.files.targetSelect, '', row) : null;
                if (targetSelect) {
                    syncCounterpartDisabledOptions(e.target, targetSelect);
                }
                return;
            }

            // 2c. Row Target Language change
            if (e.target.matches(Selectors.files.targetSelect)) {
                const row = e.target.closest('tr.file-row');
                const sourceSelect = row ? Utils.domQuery(Selectors.files.fileSourceSelect, '', row) : null;
                if (sourceSelect) {
                    syncCounterpartDisabledOptions(sourceSelect, e.target);
                }
                return;
            }

            // 4. File checkbox change
            if (e.target.matches(Selectors.files.fileCheckbox)) {
                updateButtonState();
            }
            // 3. Select all button
            const selectAllBtn = e.target.closest(Selectors.files.selectAllBtn);
            if (selectAllBtn) {
                const checkboxes = Utils.domQueryAll(Selectors.files.fileCheckbox);
                const checkedCheckboxes = Utils.domQueryAll(Selectors.files.fileCheckboxChecked);
                const allChecked = checkboxes.length > 0 && checkboxes.length === checkedCheckboxes.length;
                checkboxes.forEach((cb) => {
                    cb.checked = !allChecked;
                });
                updateButtonState();
                return;
            }
        });
    };

    /**
     * Initialize the Deepler Files UI.
     *
     * @param {object} config
     */
    const init = (config) => {
        appConfig = config;
        loadStrings().then(() => {
            registerEventListeners();
            syncAllCounterpartOptions();
            updateGlobalGlossarySelector();
            updateButtonState();
            return null;
        }).catch(Notification.exception);
    };

    return {
        init: init
    };
});
