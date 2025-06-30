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
 * @module     'local_deepler'; // Full name of the plugin (used for diagnostics)./admin/formvalidation
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    const initCode = ()=> {
        const form = document.querySelector('form.mform');
        if (!form) {
 return;
}

        const attribute = document.getElementById('deepler-attribute');
        const valuefilter = document.getElementById('deepler-valuefilter');
        const token = document.getElementById('deepler-token');
        let errorDiv = document.getElementById('deepler-form-errors');

        // If error div doesn't exist, create it at the top of the form.
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.id = 'deepler-form-errors';
            form.prepend(errorDiv);
        }

        form.addEventListener('submit', function(e) {
            window.console.log('Form submited bro');
            let errors = [];

            // Attribute dropdown must be selected.
            if (!attribute.value) {
                errors.push('Please select an attribute.');
            }

            // Filter value must not be empty.
            if (!valuefilter.value.trim()) {
                errors.push('Filter value cannot be empty.');
            }

            // Token must be a valid UUID v4.
            const uuid = token.value.trim();
            const uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
            if (!uuidRegex.test(uuid)) {
                errors.push('API token must be a valid UUID.');
            }

            if (errors.length > 0) {
                e.preventDefault();
                errorDiv.innerHTML = '<div class="alert alert-danger">' + errors.join('<br>') + '</div>';
            } else {
                errorDiv.innerHTML = '';
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
