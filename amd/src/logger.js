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

const DEBUG_LEVELS = {
    NONE: 0,
    MINIMAL: 5,
    NORMAL: 15,
    ALL: 30719,
    DEVELOPER: 32767
};

class Logger {
    constructor(debugLevel = DEBUG_LEVELS.NONE) {
        this.debugLevel = debugLevel;
        this.initializeMethods();
    }

    /**
     * Init.
     */
    initializeMethods() {
        this.error = this.createMethod('error', DEBUG_LEVELS.MINIMAL);
        this.warn = this.createMethod('warn', DEBUG_LEVELS.NORMAL);
        this.info = this.createMethod('info', DEBUG_LEVELS.ALL);
        this.log = this.createMethod('log', DEBUG_LEVELS.DEVELOPER);
    }

    /**
     * Create wrappers.
     *
     * @param {string} methodName
     * @param {int} requiredLevel
     * @returns {(function(...[*]): *[])|*}
     */
    createMethod(methodName, requiredLevel) {
        if (this.debugLevel >= requiredLevel) {
            return window.console[methodName].bind(window.console);
        }
        return (...args) => args; // No-op function that just returns its arguments
    }

    /**
     * Setting debug level.
     *
     * @param {int} level
     */
    setDebugLevel(level) {
        this.debugLevel = level;
        this.initializeMethods();
    }
}

export {Logger, DEBUG_LEVELS};
