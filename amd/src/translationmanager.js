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

/*
 * @module     local_deepler/deepler
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


import Selectors from "./selectors";
import ajax from 'core/ajax';
import './eventbus';
import {eventBus} from "./eventbus";

export class TranslationManager {
    constructor(config, logger) {
        this.config = config;
        this.logger = logger;
        this.tempTranslations = {};
    }
    saveTranslations(keys) {

        const data = [];
        keys.forEach((key) => {
            // Const icon = document.querySelector(replaceKey(Selec,tors.actions.validatorBtn, key));
            const icon = Selectors.actions.validatorBtn.querySelectorKey(key);
                const currentStatus = icon.getAttribute('data-status');
                if (currentStatus === Selectors.statuses.tosave.toString()) {
                    eventBus.publish('savingtodb', key);
                    // HideErrorMessage(key);
                    data.push(prepareDbUpdatdeItem(key));
                }
            }
        );
        ajax.call([
            {
                methodname: "local_deepler_update_translation",
                args: {
                    data: data,
                },
                done: (data) => {
                    if (saveAllModal !== null && saveAllModal.isVisible) {
                        saveAllModal.hide();
                    }
                    if (data.length > 0) {
                        handleAjaxUpdateDBResponse(data);
                    } else {
                        keys.forEach((key) => {
                            errorMessageItem(key, tempTranslations[key].editor, 'Something went wrong with the data');
                        });
                    }
                },
                fail: (err) => {
                    // An error occurred
                    keys.forEach((key) => {
                        errorMessageItem(key, tempTranslations[key].editor, err.toString());
                    });
                },
            }
        ]);
    }

    doAutotranslate() {
        // Implementation
    }
    /**
     * Compile data to be sent to deepl.
     *
     * @param {String} key
     * @returns {{field: *, id: number, text: string, courseid, tid: *, table: *}}
     */
    prepareDbUpdatdeItem(key) {
        const editor = tempTranslations[key].editor;
        const textTranslated = getEditorText(editor);
        const sourceText = getSourceText(key);
        const fieldText = tempTranslations[key].fieldText;
        const element = Selectors.editors.multiples.editorsWithKey.queryKey(key);
        const {id, tid, field, table} = getElementAttributes(element);
        const textTosave = getupdatedtext(fieldText, textTranslated, sourceText, tempTranslations[key].sourceLang);
        return {
            courseid: config.courseid,
            id: id,
            tid: tid,
            field: field,
            table: table,
            text: textTosave
        };
    }
}
