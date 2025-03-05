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
 * @file       amd/src/local/customevents.js
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], () => {
    const events = {};

    return {
        // Register an event handler
        on: (eventName, callback)=> {
            if (!events[eventName]) {
                events[eventName] = [];
            }
            if (!events[eventName].includes(callback)) {
                // Avoid duplication
                events[eventName].push(callback);
            }
        },
        // Trigger an event with multiple parameters
        emit: (eventName, ...args) =>{
            if (events[eventName]) {
                events[eventName].forEach(function(callback) {
                    callback(...args);
                });
            }
        },
        // Remove an event handler
        off: (eventName, callback) =>{
            if (events[eventName]) {
                events[eventName] = events[eventName].filter(function(cb) {
                    return cb !== callback;
                });
            }
        }
    };
});
