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
 * Settings modal reactive component for local_deepler.
 *
 * @module     local_deepler/local/reactive/components/settings_modal
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import BaseComponent from 'core/local/reactive/basecomponent';

export default class extends BaseComponent {
    /**
     * Component create lifecycle method.
     *
     * @param {object} descriptor Component descriptor.
     */
    create(descriptor) {
        this.name = 'local_deepler_settings_modal';
        this.rowId = descriptor.rowId || null;
        this.selectors = {
            form: 'form',
            saveBtn: '[data-action="save"]',
            resetBtn: '[data-action="reset"]',
        };
    }

    /**
     * Watchers definition.
     *
     * @returns {Array} List of watchers.
     */
    getWatchers() {
        if (!this.rowId) {
            return [];
        }
        return [
            {watch: `rows[${this.rowId}].customSettings:updated`, handler: this._settingsUpdated},
        ];
    }

    /**
     * Handler when settings are updated.
     *
     * @param {object} args Watcher arguments.
     * @param {object} args.element The updated element.
     */
    _settingsUpdated({element}) {
        this._updateFormValues(element.customSettings);
    }

    /**
     * Update form fields with values.
     *
     * @param {object|null} customSettings Settings object or null if default.
     */
    _updateFormValues(customSettings) {
        if (!customSettings) {
            return;
        }
        // Iterate form elements and update matching fields.
        const form = this.getElement(this.selectors.form) || this.element;
        if (!form) {
            return;
        }

        Object.entries(customSettings).forEach(([key, val]) => {
            const input = form.querySelector(`[name="${key}"], [name="local_deepler/${key}"]`);
            if (input) {
                if (input.type === 'checkbox') {
                    input.checked = Boolean(val);
                } else if (input.type === 'radio') {
                    const radio = form.querySelector(`[name="${input.name}"][value="${val}"]`);
                    if (radio) {
                        radio.checked = true;
                    }
                } else {
                    input.value = val;
                }
            }
        });
    }
}
