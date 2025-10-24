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
define([], () => {
    /**
     * @type {[{regex: RegExp, type: string},{regex: RegExp, type: string}]}
     */
    const patterns = [
        {regex: /<pre\b[^>]*>(.*?)<\/pre>/gs, type: 'PRETAG'}, // Pre HTML.
        {regex: /\$\$.*?\$\$/g, type: 'LATEX'} // Display math.
    ];


    /**
     * Function to replace expressions with tokens.
     * @param {String} text
     * @param {Object} escapePatterns
     * @returns {Object} {{expressions: *[], tokenizedText}}
     */
    const preprocess = (text, escapePatterns) => {
        const expressions = [];
        let tokenizedText = text;

        // Patterns for different environments.
        // Replace each expression with a token.
        patterns.forEach(pattern => {
            if (escapePatterns[pattern.type]) {
                tokenizedText = tokenizedText.replace(pattern.regex, match => {
                    const token = `__${pattern.type}_${expressions.length}__`;
                    expressions.push({token: token, expression: match});
                    return token;
                });
            }
        });

        return {tokenizedText, expressions: expressions};
    };

    /**
     * Function to replace tokens with original expressions.
     * @param {String} text
     * @param {Array} expressions
     * @returns {String}
     */
    const postprocess = (text, expressions) => {
        expressions.forEach((expr) => {
            const token = new RegExp(expr.token, 'g');
            text = text.replace(token, escapeReplacementString(expr.expression));
        });
        return text;
    };

    /**
     * Escape LaTeX tags.
     * @param {String} str
     * @returns {String}
     */
    const escapeReplacementString = (str) => {
        return str.replace(/\$/g, '$$$$');
    };
    return {
        postprocess: postprocess,
        preprocess: preprocess
    };
});
