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
    const TR_DB_SUCCESS = 'onTranslationUpdateSuccess';
    const DEEPL_SUCCESS = 'onDeeplUpdateSuccess';
    const TR_DB_FAILED = 'onTranslationUpdateFailed';
    const DEEPL_FAILED = 'onDeeplUpdateFailed';
    let APP_VERSION = '';
    const updateTranslationsInDb = (data, userid, courseid) => {
        Log.debug(`api.updateTranslationsInDb.33`);
        Log.debug(data);
        Log.debug(courseid);
        Ajax.call([
            {
                methodname: "local_deepler_update_translation",
                args: {
                    data: data,
                    userid: userid,
                    courseid: courseid
                },
                done: (response) => {
                   Log.info(`api/updateTranslationsInDb/done::response`);
                   Log.info(response);
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
    const translate = (data, options, version) => {
        const args = {
            translations: data, // Array of text, keys, source_lang
            options: options, // Object with DeepL's settings options including target_lang.
            version: version
        };
        Log.info(`api/translate > args`);
        Log.info(args);
        Ajax.call([{
            methodname: "local_deepler_get_translation",
            args: args,
            done: (response) => {
                Log.info(`api/translate/done::response`);
                Log.info(response);
                Events.emit(DEEPL_SUCCESS, response);
            },
            fail: (jqXHR, status, error) => {
                Log.error(`api/translate/fail::jqXHR`);
                Log.error(jqXHR);
                Events.emit(DEEPL_FAILED, status ?? '', error ?? jqXHR.debuginfo ?? jqXHR.message ?? jqXHR.errorcode);
            }
        }]);
    };

    return {
        APP_VERSION: APP_VERSION,
        TR_DB_SUCCESS: TR_DB_SUCCESS,
        TR_DB_FAILED: TR_DB_FAILED,
        DEEPL_SUCCESS: DEEPL_SUCCESS,
        DEEPL_FAILED: DEEPL_FAILED,
        updateTranslationsInDb: updateTranslationsInDb,
        translate: translate
    };
});
