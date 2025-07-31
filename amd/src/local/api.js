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
define(['core/log', 'core/ajax', './utils', './customevents'], (Log, Ajax, Utils, Events) => {
    const TR_DB_SUCCESS = 'onDbUpdateSuccess';
    const GLOSSARY_ENTRIES_SUCCESS = 'onGlossaryEntriesSuccess';
    const GLOSSARY_ENTRIES_FAILED = 'onGlossaryEntriesFailed';
    const GLOSSARY_DB_SUCCESS = 'onDbGlossaryUpdateSuccess';
    const TR_DB_FAILED = 'onDbUpdateFailed';
    const GLOSSARY_DB_FAILED = 'onDbGlossaryUpdateFailed';
    const GLOSSARY_DB_ALL_FAILED = 'onDbGlossaryUpdateAllFailed';
    const DEEPL_SUCCESS = 'onDeeplTrSuccess';
    const DEEPL_RF_SUCCESS = 'onDeeplRfSuccess';
    const DEEPL_FAILED = 'onDeeplTrFailed';
    const DEEPL_RF_FAILED = 'onDeeplRfFailed';
    let APP_VERSION = '';
    /**
     * Service to update used glossary timestamp.
     *
     * @param {object} glossaries
     */
    const updateGlossariesUsage = (glossaries)=>{
        Ajax.call([
            {
                methodname: "local_deepler_update_glossary",
                args: {'glossaryids': glossaries},
                done: (response)=>{

                    response.forEach((r)=>{
                        if (r.sattus === 'error') {
                            Events.emit(GLOSSARY_DB_FAILED, r);
                        } else {
                            Events.emit(GLOSSARY_DB_SUCCESS, r);
                        }
                    });
                },
                fail: (jqXHR, status, error) => {
                    Log.error(`api/updateGlossariesUsage/fail::jqXHR`);
                    Log.error(jqXHR);
                    Events.emit(GLOSSARY_DB_ALL_FAILED, error ?? jqXHR.debuginfo ?? jqXHR.message ?? jqXHR.errorcode ?? status);
                }
            }
        ]);
    };
    /**
     * Service to update used glossary visibility.
     *
     * @param {string} glossaryId
     * @param {int} visibility
     */
    const updateGlossariesVisibility = (glossaryId, visibility)=>{
        Ajax.call([
            {
                methodname: "local_deepler_update_glossary_visibility",
                args: {'glossaryid': glossaryId, 'shared': visibility},
                done: (response)=>{
                    Events.emit(GLOSSARY_DB_SUCCESS, response);
                },
                fail: (jqXHR, status, error) => {
                    Log.error(`api/updateGlossariesVisibility/fail::jqXHR`);
                    Log.error(jqXHR);
                    Events.emit(GLOSSARY_DB_FAILED, error ?? jqXHR.debuginfo ?? jqXHR.message ?? jqXHR.errorcode ?? status);
                }
            }
        ]);
    };
    /**
     * Service to update used glossary visibility.
     *
     * @param {string} glossaryId
     * @param {string} source
     * @param {string} target
     */
    const getGlossariesEntries = (glossaryId, source, target)=>{
        Ajax.call([
            {
                methodname: "local_deepler_get_glossary_entries",
                args: {
                    'glossaryid': glossaryId,
                    'source': source,
                    'target': target,
                },
                done: (response)=>{
                    Events.emit(GLOSSARY_ENTRIES_SUCCESS, response);
                },
                fail: (jqXHR, status, error) => {
                    Log.error(`api/getGlossariesEntries/fail::jqXHR`);
                    Log.error(jqXHR);
                    Events.emit(GLOSSARY_ENTRIES_FAILED, error ?? jqXHR.debuginfo ?? jqXHR.message ?? jqXHR.errorcode ?? status);
                }
            }
        ]);
    };
    /**
     *
     * @param {object} data
     * @param {int} userid
     * @param {int} courseid
     */
    const updateTranslationsInDb = (data, userid, courseid) => {
        Ajax.call([
            {
                methodname: "local_deepler_update_translation",
                args: {
                    data: data,
                    userid: userid,
                    courseid: courseid,
                    action: 'update'
                },
                done: (response) => {
                    if (response.length === 1 && response[0].error && response[0].keyid === '') {
                        Log.warn(`api/updateTranslationsInDb/done::response has errors`);
                        Events.emit(TR_DB_FAILED, response[0].error);
                        return;
                    }
                    Events.emit(TR_DB_SUCCESS, response);
                },
                fail: (jqXHR, status, error) => {
                    Log.error(`api/updateTranslationsInDb/fail::jqXHR`);
                    Log.error(jqXHR);
                     Events.emit(TR_DB_FAILED, error ?? jqXHR.debuginfo ?? jqXHR.message ?? jqXHR.errorcode ?? status);
                }
            }]
        );
    };
    /**
     * Parent DeepL external service caller.
     *
     * @param {object} args
     * @param {string} endPoint
     * @param {string} successEvent
     * @param {string} failedEvent
     */
    const deeplService = (args, endPoint, successEvent, failedEvent)=>{
        Ajax.call([{
            methodname: endPoint,
            args: args,
            done: (response) => {
                Events.emit(successEvent, response);
            },
            fail: (jqXHR, status, error) => {
                Log.debug(`${endPoint} api/translate/fail::jqXHR`);
                Log.debug(jqXHR);
                Events.emit(failedEvent, status ?? '', error ?? jqXHR.debuginfo ?? jqXHR.message ?? jqXHR.errorcode);
            }
        }]);
    };
    /**
     * Calls Deepl's translation.
     * @param {object} data
     * @param {object} options
     * @param {string} version
     */
    const translate = (data, options, version) => {
        const args = {
            translations: data, // Array of text, keys, source_lang
            options: options, // Object with DeepL's settings options including target_lang.
            version: version
        };
        deeplService(args, 'local_deepler_get_translation', DEEPL_SUCCESS, DEEPL_FAILED);
    };
    /**
     * Calls Deepl's rephrase.
     * @param {object} data
     * @param {object} options
     * @param {string} version
     */
    const rephrase = (data, options, version) => {
        const args = {
            rephrasings: data, // Array of text, keys, source_lang
            options: options, // Object with DeepL's settings options including target_lang.
            version: version
        };
        deeplService(args, 'local_deepler_get_rephrase', DEEPL_RF_SUCCESS, DEEPL_RF_FAILED);
    };
    /**
     * Api to be used by the other modules.
     */
    return {
        APP_VERSION: APP_VERSION,
        GLOSSARY_ENTRIES_SUCCESS: GLOSSARY_ENTRIES_SUCCESS,
        GLOSSARY_ENTRIES_FAILED: GLOSSARY_ENTRIES_FAILED,
        GLOSSARY_DB_SUCCESS: GLOSSARY_DB_SUCCESS,
        GLOSSARY_DB_FAILED: GLOSSARY_DB_FAILED,
        GLOSSARY_DB_ALL_FAILED: GLOSSARY_DB_ALL_FAILED,
        TR_DB_SUCCESS: TR_DB_SUCCESS,
        TR_DB_FAILED: TR_DB_FAILED,
        DEEPL_SUCCESS: DEEPL_SUCCESS,
        DEEPL_RF_SUCCESS: DEEPL_RF_SUCCESS,
        DEEPL_FAILED: DEEPL_FAILED,
        DEEPL_RF_FAILED: DEEPL_RF_FAILED,
        getGlossariesEntries: getGlossariesEntries,
        updateGlossariesUsage: updateGlossariesUsage,
        updateGlossariesVisibility: updateGlossariesVisibility,
        updateTranslationsInDb: updateTranslationsInDb,
        translate: translate,
        rephrase: rephrase
    };
});
