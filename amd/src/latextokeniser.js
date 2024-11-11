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

/**
 * Function to replace LaTeX math with tokens
 * @param {String} text
 * @returns {Object} {{latexExpressions: *[], tokenizedText}}
 */
export function preprocess(text) {
    const latexExpressions = [];
    let tokenizedText = text;

    // Patterns for different LaTeX math environments
    const patterns = [
        {regex: /\$\$.*?\$\$/g, type: 'display'} // Display math
    ];

    // Replace each LaTeX math expression with a token
    patterns.forEach(pattern => {
        tokenizedText = tokenizedText.replace(pattern.regex, match => {
            const token = `__LATEX_${latexExpressions.length}__`;
            latexExpressions.push(match);
            return token;
        });
    });

    return {tokenizedText, latexExpressions: latexExpressions};
}

/**
 * Function to replace tokens with original LaTeX math
 * @param {String} text
 * @param {Array} latexExpressions
 * @returns {String}
 */
export function postprocess(text, latexExpressions) {
    latexExpressions.forEach((expr, i) => {
        const token = new RegExp(`__LATEX_${i}__`, 'g');
        text = text.replace(token, escapeReplacementString(expr));
    });
    return text;
}

/**
 * Escape LaTeX tags
 * @param {String} str
 * @returns {String}
 */
export function escapeReplacementString(str) {
    return str.replace(/\$/g, '$$$$');
}
