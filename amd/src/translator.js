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
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 *
 * @param {formData} data
 */
define(['core/ajax'], (Ajax)=>{
    return {
        /**
         *
         * @param {formData} data
         * @param {Function} callback
         */
         translate: (data, callback)=>{
             Ajax.call([
            {
                methodname: "local_deepler_get_translation",
                args: {
                    data: data,
                },
                done: (response)=>{
                    callback(response);
                },
                fail: (jqXHR, status, error) => {
                    window.console.error(jqXHR, status, error);
                }
            }
        ]);
    }
    };
}
);
