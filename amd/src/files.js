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
    'jquery',
    'core/str',
    'core/notification',
    'core/modal_save_cancel',
    'core/modal_events',
    'core/templates',
    'local_deepler/local/api',
    'local_deepler/local/selectors'
], ($, Str, Notification, ModalSaveCancel, ModalEvents, Templates, Api, Selectors) => {

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
            {key: 'files:billedchars', component: 'local_deepler'}
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
            strings.settingsmodal_title = results[10];
            strings.save = results[11];
            strings.reset = results[12];
            strings.close = results[13];
            strings.glossarynotselected = results[14];
            strings.justnow = results[15];
            strings.billedchars = results[16];
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
     * Update the global glossary selector options based on global source/target language.
     */
    const updateGlobalGlossarySelector = () => {
        const sourceLang = $(Selectors.files.globalSourceLang).val() || '';
        const targetLang = $(Selectors.files.globalTargetLang).val() || '';
        const matching = getFilteredGlossaries(sourceLang, targetLang);
        const glossarySelect = $('[data-id="local_deepler/glossary_id"]');

        if (glossarySelect.length > 0) {
            glossarySelect.empty();
            glossarySelect.append(`<option value="">${strings.glossarynotselected || 'No Glossary selected'}</option>`);
            matching.forEach((g) => {
                glossarySelect.append(`<option value="${g.id}">${g.name}</option>`);
            });
        }
    };

    /**
     * Update the Translate button disabled state depending on selections.
     */
    const updateButtonState = () => {
        const checkedCount = $(Selectors.files.fileCheckboxChecked).length;
        const btn = $(Selectors.files.translateSelectedBtn);
        if (checkedCount > 0) {
            btn.removeAttr('disabled');
        } else {
            btn.attr('disabled', 'disabled');
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
        const badge = $(`[data-status-for="${fileId}"]`);
        badge.removeClass('badge-secondary badge-info badge-warning badge-success badge-danger badge-primary')
             .addClass(badgeClass)
             .text(statusText);

        const detail = $(`[data-detail-for="${fileId}"]`);
        if (detailText) {
            detail.text(detailText).show();
        } else {
            detail.text('').hide();
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
        const translationsRow = $(`#file_translations_${fileId}`);
        const list = translationsRow.find(`[data-list-for="${fileId}"]`);
        list.find('.no-translations-msg').remove();

        let billedStr = '';
        if (finalRes.billedcharacters) {
            billedStr = (strings.billedchars || 'Billed chars: {$a}').replace('{$a}', finalRes.billedcharacters);
        }

        const context = {
            targetlang: targetLang.toUpperCase(),
            timecreated: strings.justnow || 'Just now',
            newcmurl: finalRes.newcmurl || '',
            statusbadge: 'badge-success',
            statusstr: strings.done,
            str_billedchars: billedStr
        };

        Templates.render('local_deepler/files_translation_item', context)
            .then((html, js) => {
                Templates.prependNodeContents(list, html, js);
                return null;
            })
            .catch(Notification.exception);

        // Update translation count badge and toggle button.
        const row = $(`tr.file-row[data-fileid="${fileId}"]`);
        const toggleBtn = row.find('.toggle-translations-btn');
        toggleBtn.removeClass('disabled text-muted');

        // Automatically show the accordion row so user sees result.
        translationsRow.addClass('show collapse');
        toggleBtn.find('.toggle-icon').removeClass('fa-chevron-right').addClass('fa-chevron-down');
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
                const statusCell = $(`td.status-cell[data-fileid="${fileId}"]`);
                if (statusCell.find('.open-duplicate-link').length === 0) {
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
            $(`input[data-action="select-file"][data-fileid="${fileId}"]`).prop('checked', false);

        } catch (err) {
            setFileStatus(fileId, strings.error, 'badge-danger', err.message || strings.error);
        }
    };

    /**
     * Process all selected files sequentially.
     */
    const processSelectedFiles = async() => {
        const checkedBoxes = $(Selectors.files.fileCheckboxChecked);
        if (checkedBoxes.length === 0) {
            return;
        }

        const globalFormality = $('input[name="global_formality"]:checked').val() || '';
        const globalGlossary = $('[data-id="local_deepler/glossary_id"]').val() || '';
        const globalMinification = $(Selectors.files.globalMinification).is(':checked');

        const items = [];
        checkedBoxes.each((_, el) => {
            const fileId = parseInt($(el).data('fileid'), 10);
            const row = $(el).closest('tr');
            const sourceLang = row.find(Selectors.files.fileSourceSelect).val() || '';
            const targetLang = row.find(Selectors.files.targetSelect).val() || '';

            const custom = customSettingsMap[fileId] || {};
            const options = {
                formality: custom.formality !== undefined && custom.formality !== '' ? custom.formality : globalFormality,
                glossary_id: custom.glossary_id !== undefined && custom.glossary_id !== '' ? custom.glossary_id : globalGlossary,
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
        $(Selectors.files.translateSelectedBtn).attr('disabled', 'disabled');

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
        const row = $(`tr.file-row[data-fileid="${fileId}"]`);
        const sourceLang = row.find(Selectors.files.fileSourceSelect).val() || '';
        const targetLang = row.find(Selectors.files.targetSelect).val() || '';
        const matchingGlossaries = getFilteredGlossaries(sourceLang, targetLang);

        const currentCustom = customSettingsMap[fileId] || {};

        const context = {
            fileid: fileId,
            filename: fileName,
            formality: currentCustom.formality || '',
            minification: currentCustom.enable_document_minification || false,
            glossaries: matchingGlossaries.map((g) => ({
                id: g.id,
                name: g.name,
                selected: (currentCustom.glossary_id === g.id)
            }))
        };

        ModalSaveCancel.create({
            title: `${strings.settingsmodal_title} - ${fileName}`,
            body: Templates.render('local_deepler/files_settings_modal', context),
        }).then((modal) => {
            modal.show();

            // Set current formality select
            const root = modal.getRoot();
            if (currentCustom.formality) {
                root.find(`#modal_formality_${fileId}`).val(currentCustom.formality);
            }

            modal.setSaveButtonText(strings.save);

            root.on(ModalEvents.save, () => {
                const formalityVal = root.find(`#modal_formality_${fileId}`).val();
                const glossaryVal = root.find(`#modal_glossary_${fileId}`).val();
                const minificationVal = root.find(`#modal_minification_${fileId}`).is(':checked');

                const hasCustom = (formalityVal !== '' || glossaryVal !== '' || minificationVal === true);
                if (hasCustom) {
                    customSettingsMap[fileId] = {
                        formality: formalityVal,
                        glossary_id: glossaryVal,
                        enable_document_minification: minificationVal
                    };
                    $(`[data-indicator-fileid="${fileId}"]`).removeClass('d-none');
                } else {
                    delete customSettingsMap[fileId];
                    $(`[data-indicator-fileid="${fileId}"]`).addClass('d-none');
                }
            });

            root.on(ModalEvents.hidden, () => {
                modal.destroy();
            });

            return modal;
        }).catch(Notification.exception);
    };

    /**
     * Register DOM event listeners.
     */
    const registerEventListeners = () => {
        // 1. Global Source Language change
        $(document).on('change', Selectors.files.globalSourceLang, function() {
            const val = $(this).val();
            $(Selectors.files.fileSourceSelect).val(val);
            updateGlobalGlossarySelector();
        });

        // 2. Global Target Language change
        $(document).on('change', Selectors.files.globalTargetLang, function() {
            const val = $(this).val();
            $(Selectors.files.targetSelect).val(val);
            updateGlobalGlossarySelector();
        });

        // 3. Select all button
        $(document).on('click', Selectors.files.selectAllBtn, () => {
            const checkboxes = $(Selectors.files.fileCheckbox);
            const allChecked = checkboxes.length === $(Selectors.files.fileCheckboxChecked).length;
            checkboxes.prop('checked', !allChecked);
            updateButtonState();
        });

        // 4. File checkbox change
        $(document).on('change', Selectors.files.fileCheckbox, () => {
            updateButtonState();
        });

        // 5. Translate selected button
        $(document).on('click', Selectors.files.translateSelectedBtn, () => {
            processSelectedFiles();
        });

        // 6. Item settings modal button
        $(document).on('click', Selectors.files.rowSettingsBtn, function() {
            const fileId = parseInt($(this).data('fileid'), 10);
            const fileName = $(this).data('filename') || '';
            openItemSettingsModal(fileId, fileName);
        });

        // 7. Toggle translations accordion icon rotation
        $(document).on('click', Selectors.files.toggleTranslationsBtn, function() {
            const icon = $(this).find('.toggle-icon');
            const targetId = $(this).data('target') || $(this).data('bs-target');
            const targetEl = $(targetId);
            setTimeout(() => {
                if (targetEl.hasClass('show')) {
                    icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                } else {
                    icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                }
            }, 350);
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
            updateGlobalGlossarySelector();
            updateButtonState();
        }).catch(Notification.exception);
    };

    return {
        init: init
    };
});
