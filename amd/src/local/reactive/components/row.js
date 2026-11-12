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
 * Row reactive component for local_deepler.
 *
 * @module     local_deepler/local/reactive/components/row
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import BaseComponent from 'core/local/reactive/basecomponent';
import SettingsModal from 'local_deepler/local/reactive/components/settings_modal';

export default class extends BaseComponent {
    /**
     * Component create lifecycle method.
     *
     * @param {object} descriptor Component descriptor.
     */
    create(descriptor) {
        this.name = 'local_deepler_row';
        this.rowId = descriptor.element.dataset.rowId;
        this.selectors = {
            checkbox: `input[type="checkbox"][data-key="${this.rowId}"]`,
            statusBadge: `[data-status-key="${this.rowId}"]`,
            customSettingsBadge: `[data-indicator-key="${this.rowId}"]`,
            settingsBtn: `[data-action="row-settings"]`,
            resultContainer: `div[data-result-key="${this.rowId}"]`,
            wordCountBadge: `[data-wordcount-key="${this.rowId}"]`,
            sourceSelect: `select[data-action="local_deepler/sourceselect"][data-key="${this.rowId}"]`,
        };

        const settingsBtn = this.element.querySelector(this.selectors.settingsBtn);
        if (settingsBtn) {
            this.addEventListener(settingsBtn, 'click', (e) => {
                e.preventDefault();
                SettingsModal.open(this.reactive, this.rowId);
            });
        }
    }

    /**
     * Initial state ready handler.
     *
     * @param {object} state Full state.
     */
    stateReady(state) {
        const row = state.rows?.get(this.rowId);
        if (row) {
            this._renderRow(row);
        }
    }

    /**
     * Watchers definition.
     *
     * @returns {Array} List of watchers.
     */
    getWatchers() {
        return [
            {watch: `rows[${this.rowId}]:updated`, handler: this._rowUpdated},
            {watch: `rows[${this.rowId}].selected:updated`, handler: this._selectedUpdated},
            {watch: `rows[${this.rowId}].status:updated`, handler: this._statusUpdated},
            {watch: `rows[${this.rowId}].customSettings:updated`, handler: this._customSettingsUpdated},
            {watch: `rows[${this.rowId}].sourceSelectDisabled:updated`, handler: this._sourceSelectDisabledUpdated},
            {watch: `rows[${this.rowId}].detectedSourceLang:updated`, handler: this._detectedSourceLangUpdated},
            {watch: `rows[${this.rowId}].sourceLang:updated`, handler: this._sourceLangUpdated},
        ];
    }

    /**
     * Handler when sourceSelectDisabled changes.
     *
     * @param {object} args Watcher arguments.
     * @param {object} args.element The updated row element.
     */
    _sourceSelectDisabledUpdated({element}) {
        const select = this.getElement(this.selectors.sourceSelect);
        if (select) {
            select.disabled = Boolean(element.sourceSelectDisabled);
        }
    }

    /**
     * Handler when detectedSourceLang changes.
     *
     * @param {object} args Watcher arguments.
     * @param {object} args.element The updated row element.
     */
    _detectedSourceLangUpdated({element}) {
        this._updateSourceSelectUI(element);
    }

    /**
     * Handler when sourceLang changes.
     *
     * @param {object} args Watcher arguments.
     * @param {object} args.element The updated row element.
     */
    _sourceLangUpdated({element}) {
        const select = this.getElement(this.selectors.sourceSelect);
        if (select && element.sourceLang) {
            select.value = element.sourceLang;
        }
        this._updateSourceSelectUI(element);
    }

    /**
     * Helper to update source select dropdown styling and warnings.
     *
     * @param {object} row Row state object.
     */
    _updateSourceSelectUI(row) {
        const select = this.getElement(this.selectors.sourceSelect);
        if (!select) {
            return;
        }

        if (row.sourceSelectDisabled !== undefined) {
            select.disabled = Boolean(row.sourceSelectDisabled);
        }

        const globalSourceLang = this.reactive.state.globalSettings?.sourceLang || '';
        const isDiff = Boolean(
            row.detectedSourceLang &&
            globalSourceLang &&
            row.detectedSourceLang.toLowerCase() !== globalSourceLang.toLowerCase()
        );

        if (isDiff) {
            select.classList.add('is-warning', 'border-warning');
        } else {
            select.classList.remove('is-warning', 'border-warning');
        }
    }

    /**
     * Handler when the row state is updated.
     *
     * @param {object} args Watcher arguments.
     * @param {object} args.element The updated row element.
     */
    _rowUpdated({element}) {
        this._renderRow(element);
    }

    /**
     * Handler when row selection state changes.
     *
     * @param {object} args Watcher arguments.
     * @param {object} args.element The updated row element.
     */
    _selectedUpdated({element}) {
        const checkbox = this.getElement(this.selectors.checkbox);
        if (checkbox) {
            checkbox.checked = Boolean(element.selected);
        }
    }

    /**
     * Handler when row status changes.
     *
     * @param {object} args Watcher arguments.
     * @param {object} args.element The updated row element.
     */
    _statusUpdated({element}) {
        this._updateStatusUI(element);
    }

    /**
     * Handler when row custom settings changes.
     *
     * @param {object} args Watcher arguments.
     * @param {object} args.element The updated row element.
     */
    _customSettingsUpdated({element}) {
        const indicator = this.getElement(this.selectors.customSettingsBadge);
        if (indicator) {
            if (element.customSettings) {
                indicator.classList.remove('d-none');
            } else {
                indicator.classList.add('d-none');
            }
        }
    }

    /**
     * Full row render based on state.
     *
     * @param {object} row Row state object.
     */
    _renderRow(row) {
        const checkbox = this.getElement(this.selectors.checkbox);
        if (checkbox) {
            checkbox.checked = Boolean(row.selected);
        }

        const indicator = this.getElement(this.selectors.customSettingsBadge);
        if (indicator) {
            if (row.customSettings) {
                indicator.classList.remove('d-none');
            } else {
                indicator.classList.add('d-none');
            }
        }

        this._updateStatusUI(row);
    }

    /**
     * Update row status indicators / spinners.
     *
     * @param {object} row Row state object.
     */
    _updateStatusUI(row) {
        const badge = this.getElement(this.selectors.statusBadge);
        if (!badge) {
            return;
        }

        badge.classList.remove(
            'badge-secondary', 'badge-info', 'badge-success', 'badge-danger', 'badge-warning',
            'bg-secondary', 'bg-info', 'bg-success', 'bg-danger', 'bg-warning'
        );

        switch (row.status) {
            case 'translating':
                badge.classList.add('badge-info', 'bg-info');
                break;
            case 'translated':
                badge.classList.add('badge-warning', 'bg-warning');
                break;
            case 'saved':
                badge.classList.add('badge-success', 'bg-success');
                break;
            case 'error':
                badge.classList.add('badge-danger', 'bg-danger');
                if (row.errorMessage) {
                    badge.setAttribute('title', row.errorMessage);
                }
                break;
            default:
                badge.classList.add('badge-secondary', 'bg-secondary');
                break;
        }
    }
}
