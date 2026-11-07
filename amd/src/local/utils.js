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
 * @file       amd/src/local/utils.js
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], () => {
    const COOKIE_PREFIX = 'moodle_deepler_glossary_';
    const COOKIE_PREFIX_NEW = 'moodle_deepler_settings_';
    const MAX_INPUT_LENGTH = 256;
    const parser = new DOMParser();
    /**
     * Simple helper to manage selectors
     * @param {string} s
     * @param {string} k
     * @returns {*}
     */
    const replaceKey = (s, k) => {
        return s.replaceAll("<KEY>", k);
    };
    /**
     * Transforms a keyid to a key.
     *
     * @param {string} k
     * @returns {string|null}
     */
    const keyidToKey = (k) => {
        if (typeof k !== 'string' || k.length > MAX_INPUT_LENGTH) {
            return null;
        }
        let m = k.match(/^([^-]+)-([^-]+)-([^-]+)-([^-]+)$/i);
        if (!m) {
            return null;
        }
        return `${m[1]}[${m[2]}][${m[3]}][${m[4]}]`;
    };

    /**
     * Json helper
     * @param {string} s
     * @param {string} sep
     * @returns {string}
     */
    const toJsonArray = (s, sep = ",") => {
        return JSON.stringify(s.split(sep));
    };
    /**
     * Helper function to decode the PHP base64 encoded source.
     *
     * @param {string} encoded
     * @returns {string}
     */
    const fromBase64 = (encoded) => {
        const binString = atob(encoded); // Maybe we should import js-base64 instead.
        const bytes = Uint8Array.from(binString, (m) => m.codePointAt(0));
        return new TextDecoder().decode(bytes);
    };
    /**
     * Helper function for the decode html escaped content.
     *
     * @param {string} encodedStr
     * @returns {string}
     */
    const decodeHTML = (encodedStr) => {

        const doc = parser.parseFromString(encodedStr, 'text/html');
        return doc.documentElement.textContent;
    };
    /**
     * Helper to remove HTML from strings.
     *
     * @param {string} str
     * @returns {string|string}
     * utils.js
     */
    const stripHTMLTags = (str) => {
        let doc = new DOMParser().parseFromString(str, 'text/html');
        return doc.body.textContent || "";
    };
    /**
     * Helps to create a cookie name based on the config.
     *
     * @param {object} config
     * @param {bool} oldWay
     */
    const makeCookieName = (config, oldWay = false) =>{
        if (oldWay) {
            return COOKIE_PREFIX + config.currentlang + config.targetlang + config.courseid;
        }
        return COOKIE_PREFIX_NEW + config.currentlang + config.targetlang + config.courseid;
    };
    /**
     * Cookie setter.
     *
     * @param {object} config
     * @param {string} value
     * @param {int} hours
     */
    const setCookie = (config, value, hours)=>{
        let expires = "";
        if (hours) {
            const date = new Date();
            date.setTime(date.getTime() + (hours * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = makeCookieName(config, true) + "=" + (value || "") + expires + "; path=/";
    };

    /**
     * Cookie Getter.
     *
     * @param {object} config
     * @returns {object}
     */
    const getCookie = (config) => {
        const nameEQ = makeCookieName(config, true) + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.startsWith(' ')) {
                // Strips leading spaces.
                c = c.substring(1, c.length);
            }
            if (c.startsWith(nameEQ)) {
                return c.substring(nameEQ.length, c.length);
            }
        }
        return null;
    };

    /**
     * Wrapper for the setCookie function to encode the value in base64.
     *
     * @param {object} config
     * @param {string} value
     * @param {int} hours
     */
    const setEncodedCookie = (config, value, hours)=>{
       setCookie(makeCookieName(config), btoa(value), hours);
    };
    /**
     * Wrapper for the getCookie function to decode the value from base64.
     *
     * @param {object} config
     * @returns {string}
     */
    const getEncodedCookie = (config) => {
       const cook = getCookie(makeCookieName(config));
       if (cook === null) {
           return null;
       }
       return atob(cook);
    };
    /**
     * Limit the size of a String.
     *
     * @param {string} str
     * @param {int} maxLength
     * @returns {*|string}
     */
    const smartTruncate = (str, maxLength) =>{
        if (str.length <= maxLength || maxLength == 0) {
 return str;
}

        const ellipsis = '…';
        const trimmed = str.slice(0, maxLength - ellipsis.length);

        // Try to cut at the last space within the limit
        const lastSpace = trimmed.lastIndexOf(' ');
        if (lastSpace > 0) {
            return trimmed.slice(0, lastSpace) + ellipsis;
        }

        // If no space found, just hard cut
        return trimmed + ellipsis;
    };
    /**
     * Helper to encode strings to base64 with UTF-8 support.
     *
     * @param {string} str
     * @returns {string}
     */
    const toBase64 = (str) => {
        const bytes = new TextEncoder().encode(str);
        const binString = Array.from(bytes, (byte) => String.fromCodePoint(byte)).join('');
        return btoa(binString);
    };

    /**
     * Extract multilang source text and metadata from raw DB string.
     *
     * @param {string} rawText The unfiltered DB string.
     * @param {string} sourceLang The chosen source language (e.g. 'en', 'fr').
     * @returns {{filteredText: string, hasMlangTags: boolean, mlangTagMatched: boolean,
     *          matchedCode: string|null, isFallbackOther: boolean}}
     */
    const extractMultilangSource = (rawText, sourceLang) => {
        if (!rawText || typeof rawText !== 'string') {
            return {
                filteredText: '',
                hasMlangTags: false,
                mlangTagMatched: false,
                matchedCode: null,
                isFallbackOther: false,
            };
        }

        const mlangPattern = /\{mlang\s+([a-zA-Z0-9_-]+)\}([\s\S]*?)\{mlang\}/gi;
        const matches = [];
        let match;
        while ((match = mlangPattern.exec(rawText)) !== null) {
            matches.push({
                lang: match[1].toLowerCase().trim(),
                content: match[2],
            });
        }

        if (matches.length === 0) {
            return {
                filteredText: rawText,
                hasMlangTags: false,
                mlangTagMatched: false,
                matchedCode: null,
                isFallbackOther: false,
            };
        }

        const targetLang = (sourceLang || '').toLowerCase().trim();

        // 1. Exact match or prefix match (e.g., 'en' matches 'en' or 'en_us')
        const exactMatch = matches.find((m) => (
            m.lang === targetLang ||
            m.lang.startsWith(targetLang + '_') ||
            m.lang.startsWith(targetLang + '-')
        ));
        if (exactMatch) {
            return {
                filteredText: exactMatch.content,
                hasMlangTags: true,
                mlangTagMatched: true,
                matchedCode: exactMatch.lang,
                isFallbackOther: false,
            };
        }

        // 2. Fallback to 'other'
        const otherMatch = matches.find((m) => m.lang === 'other');
        if (otherMatch) {
            return {
                filteredText: otherMatch.content,
                hasMlangTags: true,
                mlangTagMatched: false,
                matchedCode: 'other',
                isFallbackOther: true,
            };
        }

        // 3. Multilang tags exist but neither matching lang nor other is present
        return {
            filteredText: '',
            hasMlangTags: true,
            mlangTagMatched: false,
            matchedCode: null,
            isFallbackOther: false,
        };
    };

    /**
     * Removes the regional variant from a language code (e.g. 'en-US' -> 'en', 'pt-BR' -> 'pt').
     *
     * @param {string} langCode
     * @returns {string}
     */
    const removeRegionalVariant = (langCode) => {
        if (!langCode || typeof langCode !== 'string') {
            return '';
        }
        return langCode.split(/[-_]/)[0].toLowerCase().trim();
    };

    /**
     * Checks if source and target languages share the same root language.
     *
     * @param {string} sourceLang
     * @param {string} targetLang
     * @returns {boolean}
     */
    const isRephrase = (sourceLang, targetLang) => {
        if (!sourceLang || !targetLang) {
            return false;
        }
        return removeRegionalVariant(sourceLang) === removeRegionalVariant(targetLang);
    };

    /**
     * Checks if a language is in the list of rephrasable languages.
     *
     * @param {string} langCode
     * @param {Array|string} supportedLangs
     * @returns {boolean}
     */
    const isRephrasable = (langCode, supportedLangs) => {
        if (!langCode || !supportedLangs) {
            return false;
        }
        const root = removeRegionalVariant(langCode);
        const list = Array.isArray(supportedLangs)
            ? supportedLangs
            : String(supportedLangs).split(',').map((s) => s.trim());
        return list.some((item) => removeRegionalVariant(item) === root);
    };

    /**
     * Strips rephrase symbol prefix from a string.
     *
     * @param {string} text
     * @param {string} symbol
     * @returns {string}
     */
    const stripRephraseSymbol = (text, symbol = '®') => {
        if (!text || typeof text !== 'string') {
            return '';
        }
        return text.replaceAll(symbol, '').trim();
    };

    /**
     * Determines the translation status bullet styling, tooltip, and status name.
     *
     * @param {string} rawFieldText Raw DB field text.
     * @param {string} rowSourceLang Source language code for this row.
     * @param {string} targetLang Selected target language code.
     * @param {object} cfg Configuration object containing canimprove, supportedlangs, rephrasesymbol.
     * @param {object} strings UI language strings.
     * @returns {{buttonClass: string, titleString: string, status: string}}
     */
    const getTranslationStatusInfo = (rawFieldText, rowSourceLang, targetLang, cfg = {}, strings = {}) => {
        const symbol = cfg.rephrasesymbol || '®';
        const cleanTarget = stripRephraseSymbol(targetLang || '', symbol);
        const cleanSource = stripRephraseSymbol(rowSourceLang || '', symbol);

        if (!cleanTarget) {
            return {
                buttonClass: 'badge-dark',
                titleString: strings?.doselecttarget || 'Please select a target language.',
                status: 'needsupdate',
            };
        }

        const same = isRephrase(cleanSource, cleanTarget);
        const canRephrase = Boolean(cfg.canimprove) && isRephrasable(cleanTarget, cfg.supportedlangs);

        if (same && !canRephrase) {
            const defaultCant = "Cannot translate '{$a}' to '{$a}', please select a different target language";
            const template = strings?.canttranslate || defaultCant;
            return {
                buttonClass: 'badge-dark',
                titleString: template.replaceAll('{$a}', cleanTarget),
                status: 'needsupdate',
            };
        }

        const root = removeRegionalVariant(cleanTarget);
        const tagRegex = new RegExp('\\{mlang\\s+(' + root + '([_-][a-zA-Z0-9]+)?)\\s*\\}', 'i');
        const hasTargetTag = tagRegex.test(rawFieldText || '');

        if (hasTargetTag) {
            return {
                buttonClass: 'badge-warning',
                titleString: strings?.needsupdate || 'Needs update',
                status: 'needsupdate',
            };
        }

        if (same && canRephrase) {
            return {
                buttonClass: 'badge-primary',
                titleString: strings?.neverrephrased || 'This text was never improved',
                status: 'needsupdate',
            };
        }

        const neverTrTemplate = strings?.nevertranslated || "No '{$a}' translation yet";
        return {
            buttonClass: 'badge-danger',
            titleString: neverTrTemplate.replaceAll('{$a}', cleanTarget),
            status: 'needsupdate',
        };
    };

    /**
     * Api to be used by the other modules.
     */
    return {
        smartTruncate: smartTruncate,
        getCookie: getCookie,
        getEncodedCookie: getEncodedCookie,
        setCookie: setCookie,
        setEncodedCookie: setEncodedCookie,
        replaceKey: replaceKey,
        keyidToKey: keyidToKey,
        decodeHTML: decodeHTML,
        stripHTMLTags: stripHTMLTags,
        fromBase64: fromBase64,
        toBase64: toBase64,
        extractMultilangSource: extractMultilangSource,
        removeRegionalVariant: removeRegionalVariant,
        isRephrase: isRephrase,
        isRephrasable: isRephrasable,
        stripRephraseSymbol: stripRephraseSymbol,
        getTranslationStatusInfo: getTranslationStatusInfo,
        toJsonArray: toJsonArray
    };
});
