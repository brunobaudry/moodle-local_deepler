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
 * @module     local_mlangremover/mlangremover
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Import libs
import Selectors from "./local/selectors";
import ajax from "core/ajax";

// Initialize the temporary translations dictionary @todo make external class
let tempTranslations = {};
let config = {};
let letsdobutton = {};
let checkboxes = [];
// Let mlangContainer = {};
let selectedLanguages = [];
let allMLangs = [];
let removalMethod = '';
let removetag = {};
let log = (...a) => {
    return a;
};
let warn = (...a) => {
    return a;
};
let info = (...a) => {
    return a;
};
let error = (...a) => {
    return a;
};
const debug = {
    NONE: 0,
    MINIMAL: 5,
    NORMAL: 15,
    ALL: 30719,
    DEVELOPER: 32767
};
const registerEventListeners = () => {
    document.addEventListener('change', e => {
        if (e.target.closest(Selectors.actions.removeRadios)) {
            grabSetting();
        }
        if (e.target.matches('input[type="checkbox"]') && e.target.matches(Selectors.statuses.allMLangCkboxesNames)) {
            grabSetting();
        }
    });
    document.addEventListener('click', e => {
        if (e.target.closest(Selectors.actions.letsdobutton)) {
            doTagRemoval();
        }
        if (e.target.closest(Selectors.actions.selectAllBtn)) {
            toggleAllCheckboxes(e);
        }
    });
};
const registerUI = () => {
    try {
        removetag = document.querySelector(Selectors.statuses.removetag);
        document.querySelector(Selectors.actions.selectAllBtn).checked = false;
        allMLangs = Array.from(document.querySelectorAll(Selectors.statuses.allMLangCkboxes));
        log(allMLangs, allMLangs.length);
        // MlangContainer = document.querySelector(Selectors.statuses.selectedMlangsContainer);
        letsdobutton = document.querySelector(Selectors.actions.letsdobutton);
        checkboxes = document.querySelectorAll(Selectors.actions.checkBoxes);
        // Initialise status object.
        checkboxes.forEach((node) => {
            tempTranslations[node.dataset.key] = {};
        });
    } catch (e) {
        if (config.debug) {
            error(e.message);
        }
    }
};
/**
 * Translation Editor UI.
 * @param {Object} cfg JS Config
 */
export const init = (cfg) => {
    log('init');
    config = cfg;
    // Preparing the debugger.
    if (config.debug === debug.MINIMAL) {
        error = window.console.error.bind(window.console);
    } else if (config.debug === debug.NORMAL) {
        error = window.console.error.bind(window.console);
        warn = window.console.warn.bind(window.console);
    } else if (config.debug === debug.ALL) {
        error = window.console.error.bind(window.console);
        warn = window.console.warn.bind(window.console);
        info = window.console.info.bind(window.console);
    } else if (config.debug === debug.DEVELOPER) {
        error = window.console.error.bind(window.console);
        warn = window.console.warn.bind(window.console);
        info = window.console.info.bind(window.console);
        log = window.console.log.bind(window.console);
    }
    info("MLANGREMOVER loaded");
    log(config);
    warn('WARNING MESSAGE ' + config.debug);
    error("testing developper level " + +config.debug);

    // Setup.
    registerUI();
    registerEventListeners();
    toggleAutotranslateButton();
    allMLangs.forEach((ck) => {
        ck.checked = false;
    });
    /**
     * Selection Checkboxes
     */
    checkboxes.forEach((e) => {
        e.addEventListener("click", () => {
            toggleAutotranslateButton();
        });
    });
};

/**
 * Save Translation to Moodle
 * @param  {String} key Data Key
 */
const saveToDB = (key) => {
    warn('WTF ?!?');
    // Return;
    const regex = /(\w+)\[(\d+)\]\[(\w+)\]\[(\d+)\]/;
    const matches = key.match(regex);
    if (!matches) {
        throw `key ${key} did not match `;
    }
    let id = matches[2];
    let table = matches[1];
    let field = matches[3];
    let cmid = matches[4];
    // Get processing vars.
    // Restore the source.
    let updatedtext = tempTranslations[key].new;

    // Get the latest field data
    let fielddata = {};
    fielddata.courseid = config.courseid;
    fielddata.id = parseInt(id);
    fielddata.table = table;
    fielddata.field = field;
    fielddata.cmid = cmid;
    info(fielddata);
    // Warn(sourceTokenised);
    // Get the latest data to parse text against.

    ajax.call([
        {
            methodname: "local_mlangremover_get_field",
            args: {
                data: [fielddata],
            },
            done: (data) => {
                // The latests field text so multiple translators can work at the same time
                // let fieldtext = data[0].text;

                // Field text exists
                if (data.length > 0) {
                    // Updated hidden textarea with updatedtext
                    let textarea = document.querySelector(
                        Selectors.editors.multiples.textAreasResults
                            .replace("<KEY>", key));
                    // Get the updated text
                    // @todo here operate the removals
                    // let updatedtext = getupdatedtext(fieldtext, text, sourceText, tempTranslations[key].sourceLang);
                    // let updatedtext = fieldtext;

                    // Build the data object
                    let tdata = {};
                    tdata.courseid = config.courseid;
                    tdata.id = parseInt(id);
                    // Tdata.tid = tid;
                    tdata.table = table;
                    tdata.field = field;
                    tdata.text = updatedtext;
                    tdata.cmid = cmid;
                    info(tdata);
                    // Success Message
                    const successMessage = () => {
                        log("SUCCES");
                        // Element.classList.add("local_deepler__success");
                        // Add saved indicator
                        // setIconStatus(key, Selectors.statuses.success);
                        // Remove success message after a few seconds
                        /* setTimeout(() => {
                             let multilangPill = document.querySelector(replaceKey(Selectors.statuses.multilang, key));
                             let prevTransStatus = document.querySelector(replaceKey(Selectors.statuses.prevTransStatus, key));
                             prevTransStatus.classList = "badge badge-pill badge-success";
                             if (multilangPill.classList.contains("disabled")) {
                                 multilangPill.classList.remove('disabled');
                             }
                             // setIconStatus(key, Selectors.statuses.saved);
                         });*/
                    };
                    // Error Mesage
                    const errorMessage = (err) => {
                        error(err);
                    };
                    // Submit the request
                    ajax.call([
                        {
                            methodname: "local_mlangremover_update_translation",
                            args: {
                                data: [tdata],
                            },
                            done: (data) => {
                                // Print response to console log
                                info("ws: ", key, data);
                                // Display success message
                                if (data.length > 0) {
                                    successMessage();
                                    textarea.innerHTML = data[0].text;
                                } else {
                                    // Something went wrong with the data
                                    errorMessage();
                                }
                            },
                            fail: (err) => {
                                // An error occurred
                                errorMessage(err);
                            },
                        },
                    ]);
                } else {
                    // Something went wrong with field retrieval
                    warn(data);
                }
            },
            fail: (err) => {
                // An error occurred
                error(err);
            },
        },
    ]);
};
/**
 * @todo blank
 * @param {string} key
 */
const initTempForKey = (key) => {
    // Get the source text
    const sourceSelector = Selectors.sourcetexts.keys.replace("<KEY>", key);
    const sourceTextEncoded = document.querySelector(sourceSelector).getAttribute("data-sourcetext-raw");
    const sourceText = fromBase64(sourceTextEncoded);
    const rmtag = removetag.checked;
    let newText = '';
    switch (removalMethod) {
        case 'all' :
            newText = extractLanguage(sourceText, selectedLanguages.length === 1 ? selectedLanguages[0] : 'other');
            break;
        case 'keepselected' :
            if (rmtag) {
                newText = extractLanguage(sourceText, selectedLanguages[0]);
            } else {
                newText = filterLanguagesWithTags(sourceText, selectedLanguages, true);
            }
            break;
        case 'removeselected' :
            newText = filterLanguagesWithTags(sourceText, selectedLanguages, false);
            break;
    }
    log(removalMethod);
    log(selectedLanguages);
    log(sourceText);
    log(newText);
    // We make sure to initialize the record.
    tempTranslations[key] = {
        'old': sourceText,
        'new': newText,
    };
};
/**
 * Launch autotranslation
 */
const doTagRemoval = () => {
    grabSetting();
    /**/
    document
        .querySelectorAll(Selectors.statuses.checkedCheckBoxes)
        .forEach((ckBox) => {
            let key = ckBox.getAttribute("data-key");
            if (tempTranslations[key].editor !== null) {
                initTempForKey(key);
                saveToDB(key);
            }
        });
    /**/
};

const grabSetting = () => {
    removalMethod = document.querySelector(Selectors.actions.removehow).value;
    info(Selectors.statuses.selectedMLangCkboxes);
    const allMlangSel = document.querySelectorAll(Selectors.statuses.selectedMLangCkboxes);
    info(allMlangSel.length);
    selectedLanguages = Array.from(document.querySelectorAll(Selectors.statuses.selectedMLangCkboxes))
        .map(checkbox => checkbox.value);
    if (allMlangSel.length !== 1 || removalMethod === 'removeselected') {
        removetag.checked = false;
        removetag.disabled = true;
    } else if (allMlangSel.length === 1) {
        removetag.disabled = false;
    }
};

/**
 * Toggle checkboxes
 * @param {Event} e Event
 */
const toggleAllCheckboxes = (e) => {
    // Check/uncheck checkboxes
    if (e.target.checked) {
        checkboxes.forEach((i) => {
            // Toggle check box upon visibility
            i.checked = !getParentRow(i).classList.contains('d-none');
        });
    } else {
        checkboxes.forEach((i) => {
            i.checked = false;
        });
    }
    toggleAutotranslateButton();
};

const getParentRow = (node) => {
    return node.closest(replaceKey(Selectors.sourcetexts.parentrow, node.getAttribute('data-key')));
};
/**
 * Toggle Autotranslate Button
 */
const toggleAutotranslateButton = () => {
    letsdobutton.disabled = true;
    for (let i in checkboxes) {
        let e = checkboxes[i];
        if (e.checked) {
            letsdobutton.disabled = false;
            break;
        }
    }
};

/**
 * Simple helper to manage selectors
 * @param {string} s
 * @param {string} k
 * @returns {*}
 */
const replaceKey = (s, k) => {
    return s.replace("<KEY>", k);
};

/**
 * Helper function to decode the PHP base64 encoded source.
 * @param {string} encoded
 * @returns {string}
 */
const fromBase64 = (encoded) => {
    const binString = atob(encoded); // Maybe we should import js-base64 instead.
    const bytes = Uint8Array.from(binString, (m) => m.codePointAt(0));
    return new TextDecoder().decode(bytes);
};

/**
 * As the title says.
 *
 * @param {string} t
 * @return {boolean}
 */
const hasMultilang = (t) => {
    return t.includes('{mlang}');
};

/**
 * Extracts the text for a given language code from a multilang string, concatenating multiple instances.
 *
 * @param {string} textContent
 * @param {string} lang The language code to extract (e.g., 'en', 'fr', 'other').
 * @param {boolean} returnAllIfNotFound If true, returns the full text if the specified language is not found.
 * @return {string} The concatenated text for the specified language, or an empty string if not found.
 */
const extractLanguage = (textContent, lang, returnAllIfNotFound = true) => {
    if (!hasMultilang(textContent)) {
        return textContent;
    }
    // Define the pattern to match the specified language's multilang tags.
    const pattern = new RegExp(`\{mlang\\s+${lang}\}(.*?)\{mlang\}`, 'gis');

    // Initialize the result variable.
    let result = '';

    // Use matchAll to find all matches.
    const matches = textContent.matchAll(pattern);
    for (const match of matches) {
        // Concatenate all the matched text segments.
        result += `${match[1]} `;
    }
    // Trim any trailing whitespace.
    result = result.trim();

    // Return the concatenated text or an empty string if not found.
    return result === '' && returnAllIfNotFound ? textContent : result;
};

/**
 * Filters the text to keep or remove specified languages, preserving their multilang tags.
 *
 * @param {string} textContent
 * @param {Array<string>} langs An array of language codes to keep or remove (e.g., ['en', 'fr', 'other']).
 * @param {boolean} keep If true, keeps only the specified languages; if false, removes the specified languages.
 * @return {string} The filtered text with the specified languages either kept or removed, preserving their tags.
 */
const filterLanguagesWithTags = (textContent, langs, keep = true) => {
    if (!hasMultilang(textContent) || langs.length === 0) {
        return textContent;
    }
    info(textContent, langs, keep);
    // Define the pattern to match all multilang tags.
    const pattern = /\{mlang\s+([a-z]{2}|other)\}(.*?)\{mlang\}/gis;

    // Initialize the result variable.
    let result = '';

    // Use matchAll to find all matches.
    const matches = textContent.matchAll(pattern);
    log(matches);
    for (const match of matches) {
        warn(match);
        // Check if the language code is in the array of languages to keep or remove.
        const inArray = langs.includes(match[1]);
        if ((keep && inArray) || (!keep && !inArray)) {
            // Append the text segment with its tags to the result.
            result += `{mlang ${match[1]}}${match[2]}{mlang} `;
        }
    }
    // Trim any trailing whitespace.
    result = result.trim();

    // Return the filtered text with tags.
    return result === '' ? textContent : result;
};
