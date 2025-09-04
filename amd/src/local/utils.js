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
     * Shortcut for dom querySelector.
     *
     * @param {string} selector
     * @param {string} key
     * @param {element} target
     * @returns {element}
     */
    const domQuery = (selector, key = '', target = null) => {
        const el = target ?? document;
        const q = key === '' ? selector : selector.replace("<KEY>", key);
        return el.querySelector(q);
    };

    /**
     * Shortcut for dom querySelector.
     *
     * @param {string} selector
     * @param {string} key
     * @param {element} target
     * @returns {NodeList}
     */
    const domQueryAll = (selector, key = '', target = null) => {
        const el = target ?? document;
        const q = key === '' ? selector : selector.replace("<KEY>", key);
        return el.querySelectorAll(q);
    };
    /**
     * Debounce for performance
     *
     * @param {function} fn
     * @param {int} delay
     * @returns {(function(...[*]): void)|*}
     */
    const debounce = (fn, delay) => {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn(...args), delay);
        };
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
            while (c.charAt(0) == ' ') {
                // Strips leading spaces.
                c = c.substring(1, c.length);
            }
            if (c.indexOf(nameEQ) == 0) {
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
     * Api to be used by the other modules.
     */
    return {
        domQuery: domQuery,
        domQueryAll: domQueryAll,
        debounce: debounce,
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
        toJsonArray: toJsonArray
    };
});
