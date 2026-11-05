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
 * @file       amd/src/local/api.js
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/log', 'core/ajax'], (Log, Ajax) => {
    let APP_VERSION = '';

    /**
     * Service to update used glossary timestamp.
     *
     * @param {Array} glossaries
     * @returns {Promise<Array>}
     */
    const updateGlossariesUsage = (glossaries) => {
        return Ajax.call([
            {
                methodname: "local_deepler_update_glossary",
                args: {'glossaryids': glossaries},
            }
        ])[0];
    };

    /**
     * Service to update used glossary visibility.
     *
     * @param {string} glossaryId
     * @param {int} visibility
     * @returns {Promise<object>}
     */
    const updateGlossariesVisibility = (glossaryId, visibility) => {
        return Ajax.call([
            {
                methodname: "local_deepler_update_glossary_visibility",
                args: {'glossaryid': glossaryId, 'shared': visibility},
            }
        ])[0];
    };

    /**
     * Service to get glossary entries.
     *
     * @param {string} glossaryId
     * @param {string} version
     * @returns {Promise<object>}
     */
    const getGlossariesEntries = (glossaryId, version) => {
        return Ajax.call([
            {
                methodname: "local_deepler_get_glossary_entries",
                args: {'glossaryid': glossaryId, 'version': version},
            }
        ])[0];
    };

    /**
     * Service to update translations in Moodle DB.
     *
     * @param {Array} data
     * @param {int} userid
     * @param {int} courseid
     * @returns {Promise<Array>}
     */
    const updateTranslationsInDb = (data, userid, courseid) => {
        return Ajax.call([
            {
                methodname: "local_deepler_update_translation",
                args: {
                    data: data,
                    userid: userid,
                    courseid: courseid,
                    action: 'update'
                },
            }
        ])[0];
    };

    /**
     * Parent DeepL external service caller.
     *
     * @param {object} args
     * @param {string} endPoint
     * @returns {Promise<Array>}
     */
    const deeplService = (args, endPoint) => {
        return Ajax.call([{
            methodname: endPoint,
            args: args,
        }])[0];
    };

    /**
     * Calls Deepl's translation.
     *
     * @param {Array} data
     * @param {object} options
     * @param {string} version
     * @returns {Promise<Array>}
     */
    const translate = (data, options, version) => {
        const args = {
            translations: data, // Array of text, keys, source_lang
            options: options, // Object with DeepL's settings options including target_lang.
            version: version
        };
        return deeplService(args, 'local_deepler_get_translation');
    };

    /**
     * Calls Deepl's rephrase.
     *
     * @param {Array} data
     * @param {object} options
     * @param {string} version
     * @returns {Promise<Array>}
     */
    const rephrase = (data, options, version) => {
        const args = {
            rephrasings: data, // Array of text, keys, source_lang
            options: options, // Object with DeepL's settings options including target_lang.
            version: version
        };
        return deeplService(args, 'local_deepler_get_rephrase');
    };

    /**
     * Uploads a document file to DeepL for translation.
     *
     * @param {number} fileId
     * @param {string} sourceLang
     * @param {string} targetLang
     * @param {object} options
     * @param {string} version
     * @returns {Promise<object>}
     */
    const uploadDocument = (fileId, sourceLang, targetLang, options, version) => {
        return Ajax.call([{
            methodname: 'local_deepler_upload_document',
            args: {
                fileid: fileId,
                sourcelang: sourceLang,
                targetlang: targetLang,
                options: options || {},
                version: version
            }
        }])[0];
    };

    /**
     * Checks status of a document translation job.
     *
     * @param {number} jobId
     * @param {string} version
     * @returns {Promise<object>}
     */
    const getDocumentStatus = (jobId, version) => {
        return Ajax.call([{
            methodname: 'local_deepler_get_document_status',
            args: {
                jobid: jobId,
                version: version
            }
        }])[0];
    };

    /**
     * Finalizes document translation by creating duplicate activity / saving file.
     *
     * @param {number} jobId
     * @param {string} version
     * @returns {Promise<object>}
     */
    const finalizeDocument = (jobId, version) => {
        return Ajax.call([{
            methodname: 'local_deepler_finalize_document',
            args: {
                jobid: jobId,
                version: version
            }
        }])[0];
    };

    /**
     * Api to be used by the other modules.
     */
    return {
        get APP_VERSION() {
            return APP_VERSION;
        },
        set APP_VERSION(val) {
            APP_VERSION = val;
        },
        getGlossariesEntries: getGlossariesEntries,
        updateGlossariesUsage: updateGlossariesUsage,
        updateGlossariesVisibility: updateGlossariesVisibility,
        updateTranslationsInDb: updateTranslationsInDb,
        translate: translate,
        rephrase: rephrase,
        uploadDocument: uploadDocument,
        getDocumentStatus: getDocumentStatus,
        finalizeDocument: finalizeDocument
    };
});
