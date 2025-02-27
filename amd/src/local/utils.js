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
     * @param {string} k
     * @returns {`${*}[${*}][${*}]`}
     */
    const keyidToKey = (k) => {
        let m = k.match(/^(.+)-(.+)-(.+)-(.+)$/i);
        return `${m[1]}[${m[2]}][${m[3]}][${m[4]}]`;
    };
    /**
     * Json helper
     * @param {string} s
     * @param {string} sep
     * @returns {string}
     * utils.js
     */
    const toJsonArray = (s, sep = ",") => {
        return JSON.stringify(s.split(sep));
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
     * Helper function for the decode html escaped content.
     * @param {string} encodedStr
     * @returns {string}

     */
    const decodeHTML = (encodedStr) => {
            const parser = new DOMParser();
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
     * Api to be used by the other modules.
     */
    return {
        replaceKey: replaceKey,
        keyidToKey: keyidToKey,
        decodeHTML: decodeHTML,
        stripHTMLTags: stripHTMLTags,
        fromBase64: fromBase64,
        toJsonArray: toJsonArray
    };
});
