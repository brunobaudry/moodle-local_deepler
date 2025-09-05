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
 * @module     'local_deepler'; // Full name of the plugin (used for diagnostics)./local/settings
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
    ['./selectors',
    './utils',
    'core/log'],
    (Selectors,
    Utils, Log)=>{
        let config;
        let settingsUI = {};
        const registerUI = () => {
            try {
            settingsUI[Selectors.deepl.glossaryId] = Utils.domQuery(Selectors.deepl.glossaryId);
            settingsUI[Selectors.deepl.context] = Utils.domQuery(Selectors.deepl.context);
            settingsUI[Selectors.deepl.formality] = Utils.domQuery(Selectors.deepl.formality);
            settingsUI[Selectors.deepl.modelType] = Utils.domQuery(Selectors.deepl.modelType);
            settingsUI[Selectors.deepl.ignoreTags] = Utils.domQuery(Selectors.deepl.ignoreTags);
            settingsUI[Selectors.deepl.nonSplittingTags] = Utils.domQuery(Selectors.deepl.nonSplittingTags);
            settingsUI[Selectors.deepl.outlineDetection] = Utils.domQuery(Selectors.deepl.outlineDetection);
            settingsUI[Selectors.deepl.preserveFormatting] = Utils.domQuery(Selectors.deepl.preserveFormatting);
            settingsUI[Selectors.deepl.splitSentences] = Utils.domQuery(Selectors.deepl.splitSentences);
            settingsUI[Selectors.deepl.splittingTags] = Utils.domQuery(Selectors.deepl.splittingTags);
            settingsUI[Selectors.deepl.tagHandling] = Utils.domQuery(Selectors.deepl.tagHandling);
            if (!config.isfree) {
                settingsUI[Selectors.deepl.toneorstyle] = Utils.domQuery(Selectors.deepl.toneorstyle);
            }
            settingsUI[Selectors.actions.escapeLatex] = Utils.domQuery(Selectors.actions.escapeLatex);
            settingsUI[Selectors.actions.escapePre] = Utils.domQuery(Selectors.actions.escapePre);
        } catch (e) {
            if (config.debug) {
                Log.error(e.message);
            }
        }
        };

        /**
         * Get the stored settings for this course and lang pair.
         */
        const fetchCookies = () => {
            if (!config.targetlang) {
                return;
            }
            const glossaryCookie = Utils.getCookie(config);
            const newCookie = Utils.getEncodedCookie(config);
            if (newCookie !== null) {
                const settingsCookie = JSON.parse(newCookie);
                for (const selector in settingsUI) {
                    if (settingsCookie[selector] !== undefined) {
                        switch (settingsUI[selector].type) {
                            case 'select-one' :
                                setOptionFromCookie(settingsCookie[selector]);
                                break;
                            case 'checkbox' :
                                settingsUI[selector].checked = settingsCookie[selector];
                                break;
                            case 'radio' :
                                Utils.domQuery(selector + `[value="${settingsCookie[selector]}"]`).checked = true;
                                break;
                            default:
                                settingsUI[selector].value = settingsCookie[selector];
                                break;
                        }

                    }
                }
            }
            if (glossaryCookie !== null) {
                // Legacy cookie.
                settingsUI[Selectors.deepl.glossaryId].value = glossaryCookie;
            }
        };
        /**
         * Parse the advanced settings UI and map the values for cookies and Deepl.
         *
         * @returns {[{},{}]}
         */
        const prepareSettingsAndCookieValues = () => {
            let settings = {};
            let cookie = {};
            for (const selector in settingsUI) {
                if (settingsUI[selector] === null) {
                    Log.warn(`prepareSettingsAndCookieValues. Could not find selector ${selector}`);
                    Log.warn(settingsUI);
                } else {
                    switch (settingsUI[selector].type) {
                        case 'select-one':
                            cookie[selector] = settingsUI[selector].value;
                            settings[selector] = settingsUI[selector].value;
                            break;
                        case 'textarea':
                            cookie[selector] = settingsUI[selector].value;
                            // Deepl needs an array.
                            settings[selector] = Utils.toJsonArray(cookie[selector]);
                            break;
                        case 'checkbox':
                            if (selector === Selectors.deepl.tagHandling) {
                                cookie[selector] = settingsUI[selector].checked;
                                // Exception for tag_handling that checkbox but not boolean value for Deepl.
                                settings[selector] = settingsUI[selector].checked ? 'html' : 'xml';
                            } else {
                                settings[selector] = cookie[selector] = settingsUI[selector].checked;
                            }
                            break;
                        case 'radio':
                            cookie[selector] = Utils.domQuery(Selectors.actions.radioValues.replace("<RADIO>", selector)).value;
                            settings[selector] = cookie[selector];
                            break;
                        default: // Text.
                            settings[selector] = cookie[selector] = settingsUI[selector].value;
                            break;
                    }
                }
            }
            return [cookie, settings];
        };
        const getValue = (selector)=>{
           return settingsUI[selector].value;
        };
        /**
         * Selects a dd based on its value.
         * @param {string} value
         */
        const setOptionFromCookie = (value)=>{
            let optionToSelect = Utils.domQuery(`option[value="${value}"]`);
            if (optionToSelect) {
                optionToSelect.selected = true;
            }
        };
        const init = (cfg)=>{
            config = cfg;
            registerUI();
            fetchCookies();
        };
        return {
            init: init,
            getValue: getValue,
            prepareSettingsAndCookieValues: prepareSettingsAndCookieValues
        };
    }
);
