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
 * @module     'local_deepler'; // Full name of the plugin (used for diagnostics)./glossary
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    const initCode = ()=> {
        const fileInput = document.getElementById('fileElem');
        const fileNameDisplay = document.getElementById('filename-display');

        fileInput.addEventListener('change', function() {
            // Window.console.log('YO from amd');
            if (fileInput.files.length > 0) {
                fileNameDisplay.textContent = `Selected file: ${fileInput.files[0].name}`;
            } else {
                fileNameDisplay.textContent = '';
            }
        });
    };

    return {
        init: function() {
            if (document.readyState !== 'loading') {
                // DOM is already ready.
                initCode();
            } else {
                // Wait for DOMContentLoaded.
                document.addEventListener('DOMContentLoaded', initCode);
            }
        }
    };
});
