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
    const callApi = (methodname, args) => {
        return Ajax.call([{
            methodname: methodname,
            args: args
        }]);
    };
    const TR_DB_SUCCESS = 'onTranslationUpdateSuccess';
    const DEEPL_SUCCESS = 'onDeeplUpdateSuccess';
    const TR_DB_FAILED = 'onTranslationUpdateFailed';
    const DEEPL_FAILED = 'onDeeplUpdateFailed';
    const updateTranslationsInDb = (data, userid) => {
        Ajax.call([
            {
                methodname: "local_deepler_update_translation",
                args: {
                    data: data,
                    userid: userid,
                },
                done: (response) => {
                     Events.emit(TR_DB_SUCCESS, response);
                },
                fail: (jqXHR, status, error) => {
                     Events.emit(TR_DB_FAILED, status, error);
                }
            }]
        );
    };
    const translate = (data, options) => {
        Ajax.call([{
            methodname: "local_deepler_get_translation",
            args: {
                translations: data, // Array of text, keys, source_lang
                options: options // Object with DeepL's settings options including target_lang.
            },
            done: (response) => {
                Events.emit(DEEPL_SUCCESS, response);
                // SuccessCallback(response);
            },
            fail: (jqXHR, status, error) => {
                Log.error(jqXHR);
                Log.error(status);
                Log.error(error);
                Events.emit(DEEPL_FAILED, status, error);
                // FailCallback(jqXHR, status, error);
            }
        }]);
    };

    return {
        TR_DB_SUCCESS: TR_DB_SUCCESS,
        TR_DB_FAILED: TR_DB_FAILED,
        DEEPL_SUCCESS: DEEPL_SUCCESS,
        DEEPL_FAILED: DEEPL_FAILED,
        callApi: callApi,
        updateTranslationsInDb: updateTranslationsInDb,
        translate: translate
    };
});
