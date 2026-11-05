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
    'local_deepler/local/api',
    'local_deepler/local/selectors'
], ($, Str, Notification, Api, Selectors) => {

    let appConfig = null;
    let strings = {};

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
            {key: 'files:openactivity', component: 'local_deepler'}
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
            return strings;
        });
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
     * @param {string} badgeClass (badge-secondary, badge-info, badge-warning, badge-success, badge-danger, badge-primary)
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
     * Process a single file translation task.
     *
     * @param {object} item
     * @returns {Promise}
     */
    const processFile = async(item) => {
        const fileId = item.fileId;
        const sourceLang = item.sourceLang || appConfig.sourcelang || '';
        const targetLang = item.targetLang;

        try {
            // 1. Upload
            setFileStatus(fileId, strings.uploading, 'badge-info');
            const uploadRes = await Api.uploadDocument(fileId, sourceLang, targetLang, {}, appConfig.version);
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
                    const linkHtml = `<div class="mt-1 open-duplicate-link">
                        <a href="${finalRes.newcmurl}" target="_blank" class="btn btn-xs btn-outline-success py-0 px-1 small">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> ${strings.openactivity}
                        </a>
                    </div>`;
                    statusCell.append(linkHtml);
                }
            }

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

        const items = [];
        checkedBoxes.each((_, el) => {
            const fileId = parseInt($(el).data('fileid'), 10);
            const row = $(el).closest('tr');
            const sourceLang = row.data('sourcelang') || '';
            const targetLang = row.find('select[data-action="target-lang"]').val();
            items.push({
                fileId: fileId,
                sourceLang: sourceLang,
                targetLang: targetLang
            });
        });

        // Disable UI controls while running
        $(Selectors.files.translateSelectedBtn).attr('disabled', 'disabled');
        $(Selectors.files.selectAllBtn).attr('disabled', 'disabled');
        $(Selectors.files.fileCheckbox).attr('disabled', 'disabled');

        for (const item of items) {
            await processFile(item);
        }

        // Re-enable UI controls
        $(Selectors.files.selectAllBtn).removeAttr('disabled');
        $(Selectors.files.fileCheckbox).filter(':not(.table-secondary input)').removeAttr('disabled');
        updateButtonState();
    };

    /**
     * Initialize the files page event listeners.
     *
     * @param {object} config
     */
    const init = (config) => {
        appConfig = config;
        Api.APP_VERSION = config.version;

        loadStrings().then(() => {
            // Select all toggle
            $(document).on('click', Selectors.files.selectAllBtn, () => {
                const checkboxes = $(Selectors.files.fileCheckbox);
                const allChecked = checkboxes.length > 0 && checkboxes.filter(':checked').length === checkboxes.length;
                checkboxes.prop('checked', !allChecked);
                updateButtonState();
            });

            // Individual checkbox change
            $(document).on('change', Selectors.files.fileCheckbox, () => {
                updateButtonState();
            });

            // Translate selected button click
            $(document).on('click', Selectors.files.translateSelectedBtn, (e) => {
                e.preventDefault();
                processSelectedFiles();
            });
        });
    };

    return {
        init: init
    };
});
